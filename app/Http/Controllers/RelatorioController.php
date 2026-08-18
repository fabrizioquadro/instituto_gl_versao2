<?php

namespace App\Http\Controllers;

use App\Models\Baixa;
use App\Models\Clinica;
use App\Models\EstoqueSaldo;
use App\Models\Medicamento;
use App\Models\Prescricao;
use App\Models\PrescricaoPagamentoForma;
use App\Models\PrescricaoSemanaMedicamento;
use App\Models\Transferencia;
use App\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RelatorioController extends Controller
{
    // =====================================================================
    // LISTA DE RELATÓRIOS
    // =====================================================================
    public function index()
    {
        $relatorios = [
            ['rota' => 'financeiro', 'titulo' => 'Financeiro', 'desc' => 'Pagamentos com rateio por parcela, por período.', 'icone' => 'ri-money-dollar-circle-line'],
            ['rota' => 'financeiro_simplificado', 'titulo' => 'Financeiro Simplificado', 'desc' => 'Visão resumida dos pagamentos por período.', 'icone' => 'ri-bank-card-line'],
            ['rota' => 'vendas', 'titulo' => 'Vendas', 'desc' => 'Medicamentos/procedimentos vendidos ou aplicados.', 'icone' => 'ri-shopping-cart-line'],
            ['rota' => 'enfermagem', 'titulo' => 'Enfermagem', 'desc' => 'Aplicações realizadas pelas enfermeiras.', 'icone' => 'ri-nurse-line'],
            ['rota' => 'transferencias', 'titulo' => 'Transferências', 'desc' => 'Transferências de medicamentos entre clínicas.', 'icone' => 'ri-swap-box-line'],
            ['rota' => 'baixas', 'titulo' => 'Baixas', 'desc' => 'Baixas de estoque consolidadas.', 'icone' => 'ri-delete-back-2-line'],
            ['rota' => 'recepcao', 'titulo' => 'Recepção', 'desc' => 'Tempo de atendimento por recepcionista.', 'icone' => 'ri-customer-service-line'],
            ['rota' => 'caixa', 'titulo' => 'Caixa Geral', 'desc' => 'Pagamentos recebidos por colaborador (impressão).', 'icone' => 'ri-cash-line'],
            ['rota' => 'estoque', 'titulo' => 'Estoque', 'desc' => 'Saldo de estoque por clínica e medicamento.', 'icone' => 'ri-stack-line'],
            ['rota' => 'pacientes', 'titulo' => 'Pacientes e Protocolos', 'desc' => 'Todos os pacientes com protocolos inseridos (monitoramento).', 'icone' => 'ri-user-heart-line'],
        ];

        return view('relatorios.index', compact('relatorios'));
    }

    // =====================================================================
    // HELPERS
    // =====================================================================
    private function escopoClinica(Request $request): ?int
    {
        $user = auth()->user();

        // Admin: null = todas as clínicas; senão a escolhida.
        if ($user->isAdmin()) {
            return $request->filled('clinica_id') ? (int) $request->clinica_id : null;
        }

        return $user->clinica_id;
    }

    private function dataFiltro(Request $request): array
    {
        $dtInc = $request->filled('dt_inc') ? \Carbon\Carbon::parse($request->dt_inc)->startOfDay() : null;
        $dtFn = $request->filled('dt_fn') ? \Carbon\Carbon::parse($request->dt_fn)->endOfDay() : null;

        return [$dtInc, $dtFn];
    }

    private function temFiltro(Request $request, array $campos): bool
    {
        foreach ($campos as $campo) {
            if ($request->filled($campo)) {
                return true;
            }
        }

        return false;
    }

    private function exportarExcel(string $titulo, array $cabecalhos, array $linhas, string $nomeArquivo)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($titulo, 0, 31));

        foreach ($cabecalhos as $c => $nome) {
            $sheet->setCellValueByColumnAndRow($c + 1, 1, $nome);
            $sheet->getStyleByColumnAndRow($c + 1, 1)->getFont()->setBold(true);
        }

        foreach ($linhas as $i => $linha) {
            foreach ($linha as $c => $valor) {
                $sheet->setCellValueByColumnAndRow($c + 1, $i + 2, (string) $valor);
            }
        }

        foreach (range(1, count($cabecalhos)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $nomeArquivo.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // =====================================================================
    // FINANCEIRO
    // =====================================================================
    public function financeiro(Request $request)
    {
        $clinicaId = $this->escopoClinica($request);
        [$dtInc, $dtFn] = $this->dataFiltro($request);
        $filtrado = $this->temFiltro($request, ['paciente_id', 'clinica_id', 'dt_inc', 'dt_fn']);

        $clinicas = Clinica::orderBy('nome')->get();
        $pacienteFiltro = $request->filled('paciente_id') ? (int) $request->paciente_id : null;
        $linhas = [];
        $total = 0.0;

        if ($filtrado) {
            $formas = PrescricaoPagamentoForma::with([
                'pagamento.prescricao.paciente', 'pagamento.prescricao.clinica',
                'pagamento.parcelasPagas',
            ])->whereHas('pagamento.prescricao', function ($q) use ($clinicaId, $pacienteFiltro) {
                if ($clinicaId) {
                    $q->where('clinica_id', $clinicaId);
                }
                if ($pacienteFiltro) {
                    $q->where('paciente_id', $pacienteFiltro);
                }
            });

            if ($dtInc) {
                $formas->where('created_at', '>=', $dtInc);
            }
            if ($dtFn) {
                $formas->where('created_at', '<=', $dtFn);
            }

            foreach ($formas->orderBy('created_at')->get() as $forma) {
                $pagamento = $forma->pagamento;
                $prescricao = $pagamento?->prescricao;
                $rateio = (float) $pagamento?->parcelasPagas->sum('valor');
                $linhas[] = [
                    'data' => $forma->created_at ? $forma->created_at->format('d/m/Y H:i') : '-',
                    'paciente' => $prescricao?->paciente?->nm_paciente ?? '-',
                    'cpf' => $prescricao?->paciente?->cpf ?: '-',
                    'codigo' => $prescricao?->codigo_versao1 ?: $prescricao?->id,
                    'vl_tratamento' => 'R$ '.valorDbForm($prescricao?->valor_tratamento),
                    'credito' => 'R$ '.valorDbForm($prescricao?->credito_em_aberto),
                    'pagamento' => 'R$ '.valorDbForm($forma->vl_pagamento),
                    'rateio' => 'R$ '.valorDbForm($rateio),
                    'forma' => $forma->forma_pagamento,
                    'doc' => $forma->id_transacao ?: '-',
                    'parcelas' => $forma->parcelas ?: '-',
                    'clinica' => $prescricao?->clinica?->nome ?? '-',
                    'medico' => $prescricao?->medico ?: '-',
                ];
                $total += (float) $forma->vl_pagamento;
            }
        }

        if ($request->has('exportar') && $filtrado) {
            $cab = ['Data', 'Paciente', 'CPF', 'Código', 'Valor Tratamento', 'Crédito em Aberto', 'Pagamento', 'Valor Rateio', 'Forma Pagamento', 'Nº DOC', 'Parcelas', 'Clínica', 'Médico'];
            $dados = collect($linhas)->map(fn ($l) => array_values($l))->all();

            return $this->exportarExcel('Relatório Financeiro', $cab, $dados, 'relatorio_financeiro');
        }

        return view('relatorios.financeiro', compact('clinicas', 'filtrado', 'linhas', 'total', 'pacienteFiltro', 'clinicaId', 'request'));
    }

    // =====================================================================
    // FINANCEIRO SIMPLIFICADO
    // =====================================================================
    public function financeiroSimplificado(Request $request)
    {
        $clinicaId = $this->escopoClinica($request);
        [$dtInc, $dtFn] = $this->dataFiltro($request);
        $filtrado = $this->temFiltro($request, ['paciente_id', 'clinica_id', 'dt_inc', 'dt_fn']);

        $clinicas = Clinica::orderBy('nome')->get();
        $pacienteFiltro = $request->filled('paciente_id') ? (int) $request->paciente_id : null;
        $linhas = [];
        $total = 0.0;

        if ($filtrado) {
            $formas = PrescricaoPagamentoForma::with([
                'pagamento.prescricao.paciente', 'pagamento.prescricao.clinica',
            ])->whereHas('pagamento.prescricao', function ($q) use ($clinicaId, $pacienteFiltro) {
                if ($clinicaId) {
                    $q->where('clinica_id', $clinicaId);
                }
                if ($pacienteFiltro) {
                    $q->where('paciente_id', $pacienteFiltro);
                }
            });

            if ($dtInc) {
                $formas->where('created_at', '>=', $dtInc);
            }
            if ($dtFn) {
                $formas->where('created_at', '<=', $dtFn);
            }

            foreach ($formas->orderBy('created_at')->get() as $forma) {
                $prescricao = $forma->pagamento?->prescricao;
                $linhas[] = [
                    'data' => $forma->created_at ? $forma->created_at->format('d/m/Y H:i') : '-',
                    'paciente' => $prescricao?->paciente?->nm_paciente ?? '-',
                    'clinica' => $prescricao?->clinica?->nome ?? '-',
                    'medico' => $prescricao?->medico ?: '-',
                    'valor' => 'R$ '.valorDbForm($forma->vl_pagamento),
                    'forma' => $forma->forma_pagamento,
                    'parcelas' => $forma->parcelas ?: '-',
                ];
                $total += (float) $forma->vl_pagamento;
            }
        }

        if ($request->has('exportar') && $filtrado) {
            $cab = ['Data', 'Paciente', 'Clínica', 'Médico', 'Valor', 'Forma Pagamento', 'Parcelas'];
            $dados = collect($linhas)->map(fn ($l) => array_values($l))->all();

            return $this->exportarExcel('Financeiro Simplificado', $cab, $dados, 'relatorio_financeiro_simplificado');
        }

        return view('relatorios.financeiro_simplificado', compact('clinicas', 'filtrado', 'linhas', 'total', 'pacienteFiltro', 'clinicaId', 'request'));
    }

    // =====================================================================
    // VENDAS
    // =====================================================================
    public function vendas(Request $request)
    {
        $clinicaId = $this->escopoClinica($request);
        [$dtInc, $dtFn] = $this->dataFiltro($request);
        $filtrado = $this->temFiltro($request, ['medicamento_id', 'medico', 'situacao', 'paciente_id', 'clinica_id', 'dt_inc', 'dt_fn']);

        $clinicas = Clinica::orderBy('nome')->get();
        $medicamentos = Medicamento::orderBy('nome')->get(['id', 'nome']);
        $medicos = Prescricao::whereNotNull('medico')->where('medico', '<>', '')->distinct()->orderBy('medico')->pluck('medico');

        $linhas = [];
        $total = 0.0;

        if ($filtrado) {
            $q = PrescricaoSemanaMedicamento::with([
                'medicamento', 'combo', 'soro', 'semana.financeiroParcela',
                'semana.prescricao.paciente', 'semana.prescricao.clinica',
            ])->whereHas('semana.prescricao', function ($q2) use ($clinicaId, $request) {
                if ($clinicaId) {
                    $q2->where('clinica_id', $clinicaId);
                }
                if ($request->filled('medico')) {
                    $q2->where('medico', $request->medico);
                }
                if ($request->filled('paciente_id')) {
                    $q2->where('paciente_id', (int) $request->paciente_id);
                }
            });

            if ($request->filled('medicamento_id')) {
                $q->where('medicamento_id', (int) $request->medicamento_id);
            }
            if ($request->filled('situacao')) {
                $q->where('situacao', $request->situacao);
            }
            if ($dtInc) {
                $q->where('aplicado_em', '>=', $dtInc);
            }
            if ($dtFn) {
                $q->where('aplicado_em', '<=', $dtFn);
            }

            foreach ($q->orderBy('aplicado_em')->get() as $med) {
                $prescricao = $med->semana?->prescricao;
                $parcela = $med->semana?->financeiroParcela;
                $valor = $parcela ? (float) $parcela->valor_parcela : (float) ($prescricao?->valor_tratamento ?? 0);
                $nomeMed = $med->is_soro
                    ? ($med->soro?->nome ? 'Soro '.$med->soro->nome : 'Soro')
                    : ($med->combo_id ? 'Combo '.($med->combo?->nome ?? '') : ($med->medicamento?->nome ?? '-'));
                $linhas[] = [
                    'medicamento' => $nomeMed,
                    'quantidade' => $med->quantidade,
                    'situacao' => $med->situacao,
                    'cadastro' => $prescricao?->data_prescricao?->format('d/m/Y') ?? '-',
                    'aplicacao' => $med->aplicado_em ? $med->aplicado_em->format('d/m/Y H:i') : '-',
                    'valor' => 'R$ '.valorDbForm($valor),
                    'pago' => $prescricao?->situacao_financeira ?? '-',
                    'dt_pagamento' => $prescricao?->pagamentos()->orderByDesc('dt_pagamento')->value('dt_pagamento'),
                    'procedimento' => $prescricao?->codigo_versao1 ?: $prescricao?->id,
                    'paciente' => $prescricao?->paciente?->nm_paciente ?? '-',
                    'medico' => $prescricao?->medico ?: '-',
                ];
                $total += (float) $med->quantidade;
            }
            $linhas = collect($linhas)->map(fn ($l) => ['dt_pagamento' => $l['dt_pagamento'] ? \Carbon\Carbon::parse($l['dt_pagamento'])->format('d/m/Y') : '-'] + $l)->all();
        }

        if ($request->has('exportar') && $filtrado) {
            $cab = ['Medicamento', 'Quantidade', 'Status', 'Cadastro', 'Aplicação', 'Valor', 'Pago', 'Data Pagamento', 'Procedimento', 'Paciente', 'Médico'];
            $dados = collect($linhas)->map(fn ($l) => [$l['medicamento'], $l['quantidade'], $l['situacao'], $l['cadastro'], $l['aplicacao'], $l['valor'], $l['pago'], $l['dt_pagamento'], $l['procedimento'], $l['paciente'], $l['medico']])->all();

            return $this->exportarExcel('Relatório Vendas', $cab, $dados, 'relatorio_vendas');
        }

        return view('relatorios.vendas', compact('clinicas', 'medicamentos', 'medicos', 'filtrado', 'linhas', 'total', 'clinicaId', 'request'));
    }

    // =====================================================================
    // ENFERMAGEM
    // =====================================================================
    public function enfermagem(Request $request)
    {
        $clinicaId = $this->escopoClinica($request);
        [$dtInc, $dtFn] = $this->dataFiltro($request);
        $filtrado = $this->temFiltro($request, ['user_id', 'paciente_id', 'clinica_id', 'dt_inc', 'dt_fn']);

        $clinicas = Clinica::orderBy('nome')->get();
        $enfermeiras = User::where('role', 'enfermagem')->orderBy('nome')->get();
        $linhas = [];

        if ($filtrado) {
            $q = PrescricaoSemanaMedicamento::with([
                'medicamento', 'combo', 'soro', 'lotes', 'userAplicacao',
                'semana.prescricao.paciente', 'semana.prescricao.clinica',
            ])->where('situacao', 'Aplicada')
                ->whereHas('semana.prescricao', function ($q2) use ($clinicaId, $request) {
                    if ($clinicaId) {
                        $q2->where('clinica_id', $clinicaId);
                    }
                    if ($request->filled('paciente_id')) {
                        $q2->where('paciente_id', (int) $request->paciente_id);
                    }
                });

            if ($request->filled('user_id')) {
                $q->where('user_id_aplicacao', (int) $request->user_id);
            }
            if ($dtInc) {
                $q->where('aplicado_em', '>=', $dtInc);
            }
            if ($dtFn) {
                $q->where('aplicado_em', '<=', $dtFn);
            }

            foreach ($q->orderBy('aplicado_em')->get() as $med) {
                $semana = $med->semana;
                $prescricao = $semana?->prescricao;
                $linhas[] = [
                    'chegada' => $semana?->dt_hr_chegada?->format('d/m/Y H:i') ?? '-',
                    'atendimento' => $semana?->dt_hr_atendimento?->format('d/m/Y H:i') ?? '-',
                    'finalizacao' => $semana?->dt_hr_finalizacao?->format('d/m/Y H:i') ?? '-',
                    'aplicacao' => $med->aplicado_em?->format('d/m/Y H:i') ?? '-',
                    'semana' => $semana?->nr_semana ? 'Semana '.$semana->nr_semana.'/'.($prescricao?->qt_semanas ?? $semana->nr_semana) : '-',
                    'paciente' => $prescricao?->paciente?->nm_paciente ?? '-',
                    'enfermeira' => $med->userAplicacao?->nome ?? '-',
                    'clinica' => $prescricao?->clinica?->nome ?? '-',
                    'medicamento' => $med->is_soro
                        ? ($med->soro?->nome ? 'Soro '.$med->soro->nome : 'Soro')
                        : ($med->combo_id ? 'Combo '.($med->combo?->nome ?? '') : ($med->medicamento?->nome ?? '-')),
                    'quantidade' => $med->quantidade,
                    'lote' => $med->lotes->isNotEmpty() ? $med->lotesDisplay() : '-',
                    'codigo' => $med->lotes->isNotEmpty() ? $med->codigosDisplay() : '-',
                    'validade' => $med->lotes->isNotEmpty() ? $med->vencimentosDisplay() : '-',
                    'obs' => $med->obs ?: '-',
                    'procedimento' => $prescricao?->codigo_versao1 ?: $prescricao?->id,
                    'pagamento' => $prescricao?->situacao_financeira ?? '-',
                ];
            }
        }

        if ($request->has('exportar') && $filtrado) {
            $cab = ['Chegada', 'Atendimento', 'Finalização', 'Aplicação', 'Semana', 'Paciente', 'Enfermeira', 'Clínica', 'Medicamento', 'Quantidade', 'Lote', 'C. Barras', 'Validade', 'Obs', 'Procedimento', 'Pagamento'];
            $dados = collect($linhas)->map(function ($l) {
                $val = [];
                foreach (['chegada', 'atendimento', 'finalizacao', 'aplicacao', 'semana', 'paciente', 'enfermeira', 'clinica', 'medicamento', 'quantidade', 'lote', 'codigo', 'validade', 'obs', 'procedimento', 'pagamento'] as $k) {
                    $val[] = is_string($l[$k]) ? strip_tags($l[$k]) : $l[$k];
                }

                return $val;
            })->all();

            return $this->exportarExcel('Relatório Enfermagem', $cab, $dados, 'relatorio_enfermagem');
        }

        return view('relatorios.enfermagem', compact('clinicas', 'enfermeiras', 'filtrado', 'linhas', 'clinicaId', 'request'));
    }

    // =====================================================================
    // TRANSFERÊNCIAS
    // =====================================================================
    public function transferencias(Request $request)
    {
        $user = auth()->user();
        [$dtInc, $dtFn] = $this->dataFiltro($request);
        $filtrado = $this->temFiltro($request, ['dt_inc', 'dt_fn']);
        $linhas = [];

        if ($filtrado) {
            $q = Transferencia::with(['origem', 'destino', 'user', 'movimentos.medicamento']);

            // Não-admin: apenas transferências envolvendo a própria clínica.
            if (! $user->isAdmin()) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('clinica_id', $user->clinica_id)->orWhere('clinica_destino_id', $user->clinica_id);
                });
            }

            if ($dtInc) {
                $q->where('data', '>=', $dtInc->toDateString());
            }
            if ($dtFn) {
                $q->where('data', '<=', $dtFn->toDateString());
            }

            foreach ($q->orderBy('data')->get() as $transf) {
                $responsavel = $transf->administrador_id ? $transf->administrador?->nome : $transf->user?->nome;
                $movimentos = $transf->movimentos->where('tipo', 'Saida');
                if ($movimentos->isEmpty()) {
                    $movimentos = $transf->movimentos;
                }
                foreach ($movimentos as $mov) {
                    $linhas[] = [
                        'data' => $transf->data ? \Carbon\Carbon::parse($transf->data)->format('d/m/Y H:i') : ($transf->created_at?->format('d/m/Y H:i') ?? '-'),
                        'origem' => $transf->origem?->nome ?? '-',
                        'destino' => $transf->destino?->nome ?? '-',
                        'usuario' => $responsavel ?? '-',
                        'medicamento' => $mov->medicamento?->nome ?? '-',
                        'lote' => $mov->lote ?: '-',
                        'codigo' => $mov->codigo_barras ?: '-',
                        'quantidade' => number_format($mov->quantidade, 2, ',', '.'),
                    ];
                }
            }
        }

        if ($request->has('exportar') && $filtrado) {
            $cab = ['Data', 'Origem', 'Destino', 'Usuário', 'Medicamento', 'Lote', 'C. Barras', 'Quantidade'];
            $dados = collect($linhas)->map(fn ($l) => array_values($l))->all();

            return $this->exportarExcel('Relatório Transferências', $cab, $dados, 'relatorio_transferencias');
        }

        return view('relatorios.transferencias', compact('filtrado', 'linhas', 'request'));
    }

    // =====================================================================
    // BAIXAS
    // =====================================================================
    public function baixas(Request $request)
    {
        $clinicaId = $this->escopoClinica($request);
        [$dtInc, $dtFn] = $this->dataFiltro($request);
        $filtrado = $this->temFiltro($request, ['clinica_id', 'medicamento_id', 'dt_inc', 'dt_fn']);

        $clinicas = Clinica::orderBy('nome')->get();
        $medicamentos = Medicamento::orderBy('nome')->get(['id', 'nome']);
        $linhas = [];
        $totalGeral = 0.0;

        if ($filtrado) {
            $q = Baixa::with(['clinica', 'user', 'movimentos.medicamento']);

            if ($clinicaId) {
                $q->where('clinica_id', $clinicaId);
            }
            if ($dtInc) {
                $q->where('data', '>=', $dtInc->toDateString());
            }
            if ($dtFn) {
                $q->where('data', '<=', $dtFn->toDateString());
            }

            foreach ($q->orderBy('data')->get() as $baixa) {
                $movs = $baixa->movimentos;
                if ($request->filled('medicamento_id')) {
                    $movs = $movs->where('medicamento_id', (int) $request->medicamento_id);
                }
                foreach ($movs as $mov) {
                    $linhas[] = [
                        'data' => $baixa->data ? \Carbon\Carbon::parse($baixa->data)->format('d/m/Y H:i') : ($baixa->created_at?->format('d/m/Y H:i') ?? '-'),
                        'clinica' => $baixa->clinica?->nome ?? '-',
                        'medicamento' => $mov->medicamento?->nome ?? '-',
                        'lote' => $mov->lote ?: '-',
                        'quantidade' => (float) $mov->quantidade,
                        'tipo' => $mov->tipo,
                        'motivo' => $baixa->motivo ?: '-',
                        'usuario' => $baixa->user?->nome ?? '-',
                    ];
                    $totalGeral += (float) $mov->quantidade;
                }
            }

            // Consolidado por medicamento
            $resumo = collect($linhas)->groupBy('medicamento')->map(fn ($g) => round((float) $g->sum('quantidade'), 2))->sortKeys();
        }

        if ($request->has('exportar') && $filtrado) {
            $cab = ['Data', 'Clínica', 'Medicamento', 'Lote', 'Quantidade', 'Tipo', 'Motivo', 'Usuário'];
            $dados = collect($linhas)->map(function ($l) {
                return [$l['data'], $l['clinica'], $l['medicamento'], $l['lote'], number_format($l['quantidade'], 2, ',', '.'), $l['tipo'], $l['motivo'], $l['usuario']];
            })->all();

            return $this->exportarExcel('Relatório de Baixas', $cab, $dados, 'relatorio_baixas');
        }

        return view('relatorios.baixas', compact('clinicas', 'medicamentos', 'filtrado', 'linhas', 'totalGeral', 'resumo', 'clinicaId', 'request'));
    }

    // =====================================================================
    // RECEPÇÃO (TEMPO DE ATENDIMENTO)
    // =====================================================================
    public function recepcao(Request $request)
    {
        $clinicaId = $this->escopoClinica($request);
        [$dtInc, $dtFn] = $this->dataFiltro($request);
        $filtrado = $this->temFiltro($request, ['user_id_cadastro', 'clinica_id', 'dt_inc', 'dt_fn']);

        $clinicas = Clinica::orderBy('nome')->get();
        $recepcionistas = User::where('role', 'secretaria')->orderBy('nome')->get();
        $linhas = [];

        if ($filtrado) {
            $q = Prescricao::with(['paciente', 'clinica', 'userCadastro', 'semanas']);

            if ($clinicaId) {
                $q->where('clinica_id', $clinicaId);
            }
            if ($request->filled('user_id_cadastro')) {
                $q->where('user_id_cadastro', (int) $request->user_id_cadastro);
            }
            if ($dtInc) {
                $q->where('data_prescricao', '>=', $dtInc->toDateString());
            }
            if ($dtFn) {
                $q->where('data_prescricao', '<=', $dtFn->toDateString());
            }

            foreach ($q->orderBy('data_prescricao')->get() as $prescricao) {
                $chegada = $prescricao->semanas->sortBy('dt_hr_chegada')->first()?->dt_hr_chegada;
                $atendimento = $prescricao->semanas->sortBy('dt_hr_atendimento')->first()?->dt_hr_atendimento;

                $tempoMin = null;
                $referencia = $chegada ?? $atendimento;
                if ($prescricao->data_prescricao && $referencia) {
                    $tempoMin = (int) $prescricao->data_prescricao->diffInMinutes($referencia);
                }

                $linhas[] = [
                    'paciente' => $prescricao->paciente?->nm_paciente ?? '-',
                    'recepcionista' => $prescricao->userCadastro?->nome ?? '-',
                    'clinica' => $prescricao->clinica?->nome ?? '-',
                    'cadastro' => $prescricao->data_prescricao?->format('d/m/Y H:i') ?? '-',
                    'chegada' => $chegada?->format('d/m/Y H:i') ?? '-',
                    'atendimento' => $atendimento?->format('d/m/Y H:i') ?? '-',
                    'tempo' => $tempoMin !== null ? $tempoMin.' min' : '-',
                ];
            }
        }

        if ($request->has('exportar') && $filtrado) {
            $cab = ['Paciente', 'Recepcionista', 'Clínica', 'Cadastro', 'Chegada', 'Atendimento', 'Tempo (min)'];
            $dados = collect($linhas)->map(function ($l) {
                return [$l['paciente'], $l['recepcionista'], $l['clinica'], $l['cadastro'], $l['chegada'], $l['atendimento'], str_replace(' min', '', $l['tempo'])];
            })->all();

            return $this->exportarExcel('Relatório Recepção', $cab, $dados, 'relatorio_recepcao');
        }

        return view('relatorios.recepcao', compact('clinicas', 'recepcionistas', 'filtrado', 'linhas', 'clinicaId', 'request'));
    }

    // =====================================================================
    // CAIXA GERAL
    // =====================================================================
    public function caixa(Request $request)
    {
        $clinicaId = $this->escopoClinica($request);
        [$dtInc, $dtFn] = $this->dataFiltro($request);
        $filtrado = $this->temFiltro($request, ['user_id', 'clinica_id', 'dt_inc', 'dt_fn']);

        $clinicas = Clinica::orderBy('nome')->get();
        $usuarios = User::orderBy('nome')->get();
        $linhas = [];
        $total = 0.0;

        if ($filtrado) {
            $q = PrescricaoPagamentoForma::with([
                'pagamento.user', 'pagamento.prescricao.paciente', 'pagamento.prescricao.clinica',
            ])->whereHas('pagamento.prescricao', function ($q2) use ($clinicaId) {
                if ($clinicaId) {
                    $q2->where('clinica_id', $clinicaId);
                }
            });

            if ($request->filled('user_id')) {
                $q->whereHas('pagamento', fn ($q2) => $q2->where('user_id', (int) $request->user_id));
            }
            if ($dtInc) {
                $q->where('created_at', '>=', $dtInc);
            }
            if ($dtFn) {
                $q->where('created_at', '<=', $dtFn);
            }

            foreach ($q->orderBy('created_at')->get() as $forma) {
                $pagamento = $forma->pagamento;
                $linhas[] = [
                    'data' => $forma->created_at ? $forma->created_at->format('d/m/Y H:i:s') : '-',
                    'colaborador' => $pagamento?->user?->nome ?? '-',
                    'paciente' => $pagamento?->prescricao?->paciente?->nm_paciente ?? '-',
                    'valor' => (float) $forma->vl_pagamento,
                    'forma' => $forma->forma_pagamento.($forma->parcelas > 1 ? ' ('.$forma->parcelas.'x)' : ''),
                    'doc' => $forma->id_transacao ?: '-',
                ];
                $total += (float) $forma->vl_pagamento;
            }
        }

        if ($request->has('exportar') && $filtrado) {
            $cab = ['Data/Hora', 'Colaborador', 'Paciente', 'Valor Recebido', 'Forma de Pagamento', 'Nº DOC'];
            $dados = collect($linhas)->map(function ($l) {
                return [$l['data'], $l['colaborador'], $l['paciente'], 'R$ '.valorDbForm($l['valor']), $l['forma'], $l['doc']];
            })->all();
            $dados[] = ['', '', 'TOTAL GERAL', 'R$ '.valorDbForm($total), '', ''];

            return $this->exportarExcel('Caixa Geral', $cab, $dados, 'relatorio_caixa');
        }

        return view('relatorios.caixa', compact('clinicas', 'usuarios', 'filtrado', 'linhas', 'total', 'clinicaId', 'request'));
    }

    // =====================================================================
    // ESTOQUE
    // =====================================================================
    public function estoque(Request $request)
    {
        $clinicaId = $this->escopoClinica($request);
        $filtrado = $this->temFiltro($request, ['clinica_id', 'medicamento_id']);

        $clinicas = Clinica::orderBy('nome')->get();
        $medicamentos = Medicamento::orderBy('nome')->get(['id', 'nome']);
        $linhas = [];

        if ($filtrado) {
            $q = EstoqueSaldo::with(['medicamento', 'clinica'])->where('saldo', '>', 0);

            if ($clinicaId) {
                $q->where('clinica_id', $clinicaId);
            }
            if ($request->filled('medicamento_id')) {
                $q->where('medicamento_id', (int) $request->medicamento_id);
            }

            foreach ($q->orderBy('medicamento_id')->orderBy('lote')->get() as $saldo) {
                $venc = $saldo->dt_vencimento ? \Carbon\Carbon::parse($saldo->dt_vencimento) : null;
                $linhas[] = [
                    'clinica' => $saldo->clinica?->nome ?? '-',
                    'medicamento' => $saldo->medicamento?->nome ?? '-',
                    'codigo' => $saldo->codigo_barras ?: '-',
                    'lote' => $saldo->lote,
                    'vencimento' => $venc ? $venc->format('d/m/Y') : '-',
                    'dias' => $venc ? (int) now()->startOfDay()->diffInDays($venc->copy()->startOfDay(), false) : null,
                    'saldo' => number_format($saldo->saldo, 2, ',', '.'),
                ];
            }
        }

        if ($request->has('exportar') && $filtrado) {
            $cab = ['Clínica', 'Medicamento', 'C. Barras', 'Lote', 'Vencimento', 'Saldo Estoque'];
            $dados = collect($linhas)->map(fn ($l) => [$l['clinica'], $l['medicamento'], $l['codigo'], $l['lote'], $l['vencimento'], $l['saldo']])->all();

            return $this->exportarExcel('Relatório Estoque', $cab, $dados, 'relatorio_estoque');
        }

        return view('relatorios.estoque', compact('clinicas', 'medicamentos', 'filtrado', 'linhas', 'clinicaId', 'request'));
    }

    // =====================================================================
    // PACIENTES E PROTOCOLOS
    // =====================================================================
    public function pacientes(Request $request)
    {
        $clinicaId = $this->escopoClinica($request);
        [$dtInc, $dtFn] = $this->dataFiltro($request);
        $filtrado = $this->temFiltro($request, ['clinica_id', 'dt_inc', 'dt_fn']);

        $clinicas = Clinica::orderBy('nome')->get();
        $linhas = [];

        if ($filtrado) {
            $q = Prescricao::with(['paciente', 'clinica', 'semanas'])
                ->where('situacao', '<>', 'Cancelada');

            if ($clinicaId) {
                $q->where('clinica_id', $clinicaId);
            }
            if ($dtInc) {
                $q->where('data_prescricao', '>=', $dtInc->toDateString());
            }
            if ($dtFn) {
                $q->where('data_prescricao', '<=', $dtFn->toDateString());
            }

            foreach ($q->orderBy('data_prescricao')->get() as $p) {
                $emAberto = $p->semanas
                    ->where('tem_aplicacao', true)
                    ->whereNotIn('situacao', ['Aplicado', 'Cancelada', 'Pendente'])
                    ->sortBy('nr_semana')
                    ->first();

                $linhas[] = [
                    'paciente' => $p->paciente?->nm_paciente ?? '-',
                    'cpf' => $p->paciente?->cpf ?: '-',
                    'clinica' => $p->clinica?->nome ?? '-',
                    'medico' => $p->medico ?: '-',
                    'data' => $p->data_prescricao?->format('d/m/Y') ?? '-',
                    'tipo' => $p->tipo_atendimento ?: '-',
                    'semanas' => $p->qt_semanas.' (aplicação: '.$p->qt_semanas_aplicacao.')',
                    'semana_atual' => $emAberto ? 'Semana '.$emAberto->nr_semana : 'Concluído',
                    'situacao' => $p->situacao,
                    'financeiro' => $p->situacao_financeira,
                    'valor' => 'R$ '.valorDbForm($p->valor_tratamento),
                ];
            }
        }

        if ($request->has('exportar') && $filtrado) {
            $cab = ['Paciente', 'CPF', 'Clínica', 'Médico', 'Data', 'Tipo', 'Semanas', 'Semana Atual', 'Situação', 'Financeiro', 'Valor'];
            $dados = collect($linhas)->map(fn ($l) => array_values($l))->all();

            return $this->exportarExcel('Relatório de Pacientes', $cab, $dados, 'relatorio_pacientes');
        }

        return view('relatorios.pacientes', compact('clinicas', 'filtrado', 'linhas', 'clinicaId', 'request'));
    }
}
