<?php

namespace App\Http\Controllers;

use App\Models\Medicamento;
use App\Models\PrescricaoSemana;
use App\Models\User;
use App\Services\AplicacaoService;
use App\Services\FeegowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EnfermagemController extends Controller
{
    public function __construct(private AplicacaoService $service)
    {
    }

    /**
     * Fila de Espera + Atendimentos do Dia (menu Enfermagem).
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $clinicaId = $user->clinica_id;

        $base = fn () => PrescricaoSemana::with([
            'prescricao.paciente',
            'prescricao.clinica',
            'financeiroParcela',
            'userAplicacao',
        ])
            ->where('tem_aplicacao', true)
            ->whereHas('prescricao', fn ($q) => $q
                ->where('clinica_id', $clinicaId)
                ->where('situacao', '<>', 'Cancelada'));

        $aguardando = (clone $base)()
            ->where('situacao', 'Fila de Aplicação')
            ->orderBy('dt_hr_chegada')
            ->get();

        // Fila agrupada por paciente (R16): 1 linha por paciente com N semanas,
        // evitando o paciente aparecer várias vezes.
        $aguardandoGrupo = $aguardando
            ->groupBy(fn ($s) => $s->prescricao->paciente_id)
            ->map(function ($semanas) {
                $semanas = $semanas->sortBy('dt_hr_chegada');
                $primeira = $semanas->first();
                $semanasOrdenadas = $semanas->sortBy(fn ($s) => $s->prescricao_id.'.'.str_pad((string) $s->nr_semana, 3, '0', STR_PAD_LEFT));

                return (object) [
                    'paciente' => $primeira->prescricao->paciente,
                    'primeira' => $primeira,
                    'semanas' => $semanasOrdenadas->values(),
                    'chegada' => $semanas->min('dt_hr_chegada'),
                    'medicos' => $semanas->pluck('prescricao.medico')->unique()->filter()->values(),
                    'parcelasPagas' => $semanas->every(fn ($s) => $s->financeiroParcela && (float) $s->financeiroParcela->valor_pago >= (float) $s->financeiroParcela->valor_parcela),
                ];
            })
            ->sortBy('chegada')
            ->values();

        $atendimento = (clone $base)()
            ->where('situacao', 'Atendimento')
            ->orderBy('dt_hr_atendimento')
            ->get();

        $aplicadas = (clone $base)()
            ->where('situacao', 'Aplicado')
            ->whereDate('dt_hr_finalizacao', today())
            ->orderBy('dt_hr_finalizacao')
            ->get();

        // Resumo de atendimentos do dia por enfermeira
        $resumo = [];
        foreach ($aplicadas as $semana) {
            $nome = $semana->userAplicacao?->nome ?? 'Não Identificada';
            $resumo[$nome] = ($resumo[$nome] ?? 0) + 1;
        }
        ksort($resumo);

        $aba = $request->query('aba', 'fila');

        return view('enfermagem.index', compact('aguardando', 'aguardandoGrupo', 'atendimento', 'aplicadas', 'resumo', 'aba', 'user'));
    }

    /**
     * Envia a semana para a Fila de Aplicação (parcela paga OU sem parcela).
     */
    public function enviarFila(Request $request, $semanaId)
    {
        $semana = PrescricaoSemana::findOrFail($semanaId);

        if (! $this->service->semanaLiberada($semana)) {
            return redirect()->route('procedimentos.show', $semana->prescricao_id)
                ->with('mensagem_erro', 'Parcela em aberto. Use "Enviar para Fila com autorização" para liberar sem pagamento.');
        }

        $this->service->enviarFila($semana, auth()->user());

        return redirect()->route('procedimentos.show', $semana->prescricao_id)
            ->with('mensagem', 'Semana enviada para a Fila de Atendimento.');
    }

    /**
     * Envia a semana para a fila SEM pagamento (exige autorização de um admin).
     */
    public function enviarFilaSemPagamento(Request $request)
    {
        $request->validate([
            'semana_id' => 'required|integer',
            'autorizador_email' => 'required|email',
            'autorizador_senha' => 'required|string',
        ]);

        $semana = PrescricaoSemana::findOrFail($request->semana_id);

        $autorizador = User::where('role', 'admin')
            ->where('ativo', true)
            ->where('email', $request->autorizador_email)
            ->first();

        if (! $autorizador || ! Hash::check($request->autorizador_senha, $autorizador->password)) {
            return redirect()->route('procedimentos.show', $semana->prescricao_id)
                ->with('mensagem_erro', 'Autorizador inválido (e-mail ou senha incorretos).');
        }

        $this->service->enviarFila($semana, auth()->user(), $autorizador);

        return redirect()->route('procedimentos.show', $semana->prescricao_id)
            ->with('mensagem', 'Semana enviada para a Fila de Atendimento SEM pagamento (autorizada).');
    }

    /**
     * Tela de aplicação/bipagem de uma semana. Abre o atendimento se for o caso.
     */
    public function aplicacao($semanaId)
    {
        $user = auth()->user();

        $semana = PrescricaoSemana::with([
            'prescricao.paciente',
            'prescricao.clinica',
            'prescricao.anexos',
            'medicamentos.medicamento',
            'medicamentos.combo.medicamentos.medicamento',
            'medicamentos.soro.medicamentos.medicamento',
            'medicamentos.userAplicacao',
            'medicamentos.lotes.estoqueAberto',
            'financeiroParcela',
            'autorizador',
        ])->findOrFail($semanaId);

        // pertence à clínica do usuário
        if ($semana->prescricao->clinica_id != $user->clinica_id && ! $user->isAdmin()) {
            abort(403, 'Esta semana não pertence à sua clínica.');
        }

        // gate de pagamento
        if (! $this->service->semanaLiberada($semana) && ! $semana->autorizador_sem_pagamento) {
            return redirect()->route('enfermagem.index')
                ->with('mensagem_erro', 'Esta semana não está liberada para aplicação (parcela em aberto).');
        }

        $visualizar = false;

        if (in_array($semana->situacao, ['Aplicado', 'Aplicação Parcial', 'Pendente'])) {
            $visualizar = true;
        } elseif ($semana->situacao === 'Atendimento' && $semana->user_id_aplicacao && $semana->user_id_aplicacao != $user->id && ! $user->isAdmin()) {
            abort(403, 'Este atendimento já está em andamento por outra enfermeira.');
        } elseif ($semana->situacao === 'Fila de Aplicação') {
            $this->service->abrirAtendimento($semana, $user);
        }

        // Monta as linhas de bipagem (medicamento único ou componentes de combo/soro)
        $grupos = $this->montarGruposBipagem($semana);

        // FERRO: verifica se a semana contém medicação FERRO (diretamente ou
        // como componente de combo/soro) para alerta evidente na aplicação.
        $temFerro = false;
        foreach ($semana->medicamentos as $sm) {
            if ($sm->medicamento && $sm->medicamento->ehFerro()) {
                $temFerro = true;
                break;
            }
            if ($sm->combo) {
                foreach ($sm->combo->medicamentos as $cm) {
                    if ($cm->medicamento && $cm->medicamento->ehFerro()) {
                        $temFerro = true;
                        break 2;
                    }
                }
            }
            if ($sm->soro) {
                foreach ($sm->soro->medicamentos as $sm2) {
                    if ($sm2->medicamento && $sm2->medicamento->ehFerro()) {
                        $temFerro = true;
                        break 2;
                    }
                }
            }
        }

        // Vasilhames disponíveis na semana (para o modal "Abrir Frasco")
        $vasilhames = [];
        foreach ($grupos as $grupo) {
            foreach ($grupo['linhas'] as $linha) {
                if ($linha['medicamento']->tipo === 'Vasilhame') {
                    $vasilhames[$linha['medicamento']->id] = $linha['medicamento'];
                }
            }
        }

        // Outras semanas do mesmo protocolo do paciente que estão na fila/
        // atendimento (R16): mostra na tela de aplicação para a enfermagem
        // ver as N semanas do paciente.
        $outrasSemanasFila = PrescricaoSemana::with('prescricao.paciente', 'financeiroParcela')
            ->where('prescricao_id', $semana->prescricao_id)
            ->where('id', '<>', $semana->id)
            ->whereIn('situacao', ['Fila de Aplicação', 'Atendimento'])
            ->where('tem_aplicacao', true)
            ->orderBy('nr_semana')
            ->get();

        return view('enfermagem.aplicacao', compact('semana', 'grupos', 'vasilhames', 'visualizar', 'user', 'temFerro', 'outrasSemanasFila'));
    }
    /**
     * Lança a aplicação (set_aplicacao da V1).
     */
    public function lancar(Request $request, $semanaId)
    {
        $semana = PrescricaoSemana::with('prescricao.paciente')->findOrFail($semanaId);

        // Obriga a enfermagem a conferir o pedido médico: precisa marcar a
        // confirmação E abrir o anexo da prescrição (registra visualizado_em).
        $temAnexo = $semana->prescricao->anexos()->exists();
        $anexoVisualizado = $semana->prescricao->anexos()->whereNotNull('visualizado_em')->exists();
        if ($request->confirmacao_pedido_medico !== '1' || ($temAnexo && ! $anexoVisualizado)) {
            return redirect()->route('enfermagem.aplicacao', $semanaId)
                ->with('mensagem_erro', 'Abra e confira o pedido médico (anexo da prescrição) e marque a confirmação antes de salvar a aplicação.');
        }

        try {
            $this->service->lancar($semana, auth()->user(), $request->all());

            // Backup na Feegow (registra um agendamento com as medicações aplicadas)
            $this->backupFeegow($semana);

            return redirect()->route('enfermagem.index')
                ->with('mensagem', 'Aplicação realizada com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('enfermagem.aplicacao', $semanaId)
                ->with('mensagem_erro', $e->getMessage());
        }
    }

    /**
     * Envia para a Feegow um agendamento com as medicações aplicadas na semana
     * (backup dos lotes/medicações usados), replicando o comportamento da V1.
     * Nunca interrompe a aplicação: falhas são apenas registradas em log.
     */
    private function backupFeegow(PrescricaoSemana $semana): void
    {
        try {
            $prescricao = $semana->prescricao;
            $paciente = $prescricao->paciente;

            if (! $paciente || ! $paciente->paciente_id_feegow) {
                return;
            }

            $notas = [];
            $marcarEnviado = [];

            $nomeDe = function ($m) {
                return $m->is_soro
                    ? ($m->soro?->nome ? 'Soro '.$m->soro->nome : 'Soro')
                    : ($m->combo_id ? 'Combo '.($m->combo?->nome ?? '') : ($m->medicamento?->nome ?? 'Item'));
            };

            foreach ($semana->medicamentos as $m) {
                if ($m->situacao === 'Aplicada' && ! $m->enviado_feegow) {
                    $lotesInfo = [];
                    foreach ($m->lotes as $l) {
                        $lotesInfo[] = 'Lote '.$l->lote.' Código '.$l->codigo_barras;
                    }
                    $usuario = $m->userAplicacao?->nome ?? 'Não identificado';
                    $dataHora = $m->aplicado_em ? $m->aplicado_em->format('d/m/Y H:i') : '-';

                    $notas[] = 'APLICADO: '.$nomeDe($m).' '.$m->quantidade
                        .' | '.($lotesInfo ? implode('; ', $lotesInfo) : 'sem lote')
                        .' | Aplicado por: '.$usuario.' em '.$dataHora;
                    $marcarEnviado[] = $m;
                } elseif ($m->situacao === 'Pendente' && ! $m->enviado_feegow) {
                    $notas[] = 'PENDENTE: '.$nomeDe($m).' '.$m->quantidade;
                    $marcarEnviado[] = $m;
                }
            }

            if (empty($notas)) {
                return; // nada novo p/ enviar (evita duplicar agendamentos)
            }

            $localId = match ((int) $prescricao->clinica_id) {
                5 => 2,
                6 => 6,
                default => 1,
            };

            // Prefixo configurável (env FEEGOW_OBS_PREFIX) p/ identificar o ambiente nas obs
            $prefixo = trim((string) config('feegow.obs_prefix'));
            $notasTexto = implode(' | ', $notas);
            if ($prefixo !== '' && $notasTexto !== '') {
                $notasTexto = $prefixo.' | '.$notasTexto;
            } elseif ($prefixo !== '') {
                $notasTexto = $prefixo;
            }

            $agendamentoId = app(FeegowService::class)->novoAgendamento([
                'local_id' => $localId,
                'paciente_id' => $paciente->paciente_id_feegow,
                'profissional_id' => 0,
                'especialidade_id' => 0,
                'procedimento_id' => 0,
                'data' => now()->format('d-m-Y'),
                'horario' => now()->addMinutes(5)->format('H:i:s'),
                'valor' => 0,
                'plano' => 0,
                'notas' => $notasTexto,
            ]);

            // Só marca como enviado se a Feegow confirmou (evita reenvio duplicado)
            if ($agendamentoId !== null) {
                foreach ($marcarEnviado as $m) {
                    $m->enviado_feegow = true;
                    $m->save();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Feegow backup falhou: '.$e->getMessage());
        }
    }

    // =====================================================================
    // AJAX (bipagem)
    // =====================================================================

    public function buscarLote(Request $request)
    {
        $resultado = $this->service->buscarLote(
            (int) $request->medicamento_id,
            (int) ($request->clinica_id ?? auth()->user()->clinica_id),
            (string) $request->codigo,
            (float) ($request->quantidade ?? 0)
        );

        return response()->json($resultado);
    }

    public function buscarFrasco(Request $request)
    {
        $medicamento = Medicamento::findOrFail($request->medicamento_id);

        $resultado = $this->service->buscarFrasco(
            $medicamento,
            (int) ($request->clinica_id ?? auth()->user()->clinica_id),
            (string) $request->codigo,
            (float) ($request->quantidade ?? 0)
        );

        return response()->json($resultado);
    }

    public function getLotesMedicamento(Request $request)
    {
        $codigos = $this->service->listarFrascosParaAbrir(
            (int) $request->medicamento_id,
            (int) auth()->user()->clinica_id
        );

        return response()->json([
            'codigos' => $codigos->map(fn ($s) => [
                'codigo_barras' => $s->codigo_barras,
                'lote' => $s->lote,
                'saldo' => (float) $s->saldo,
                'dt_vencimento' => $s->dt_vencimento ? dataDbForm($s->dt_vencimento) : null,
            ]),
        ]);
    }

    public function abrirFrasco(Request $request)
    {
        $request->validate([
            'medicamento_id' => 'required|integer',
            'codigo_barras' => 'required|string',
            'semana_id' => 'required|integer',
        ]);

        try {
            $this->service->abrirFrasco(
                (int) $request->medicamento_id,
                (int) auth()->user()->clinica_id,
                (string) $request->codigo_barras,
                auth()->user(),
                (int) $request->semana_id
            );

            return redirect()->route('enfermagem.aplicacao', $request->semana_id)
                ->with('mensagem', 'Frasco aberto com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('enfermagem.aplicacao', $request->semana_id)
                ->with('mensagem_erro', $e->getMessage());
        }
    }

    public function keepAlive()
    {
        return response()->json(['ok' => true, 'time' => time()]);
    }

    // =====================================================================
    // HELPERS
    // =====================================================================

    private function montarGruposBipagem(PrescricaoSemana $semana): array
    {
        $grupos = [];

        foreach ($semana->medicamentos as $med) {
            if (! $med->gera_aplicacao) {
                continue;
            }

            $linhas = [];

            if ($med->medicamento_id && $med->medicamento) {
                $linhas[] = [
                    'key' => (string) $med->id,
                    'medicamento' => $med->medicamento,
                    'quantidade' => (float) $med->quantidade,
                    'nome' => $med->medicamento->nome,
                ];
            } elseif ($med->combo_id && $med->combo) {
                foreach ($med->combo->medicamentos as $cm) {
                    if (! $cm->medicamento) {
                        continue;
                    }
                    $linhas[] = [
                        'key' => $med->id.'_'.count($linhas),
                        'medicamento' => $cm->medicamento,
                        'quantidade' => (float) $cm->quantidade,
                        'nome' => $cm->medicamento->nome,
                    ];
                }
            } elseif ($med->soro_id && $med->soro) {
                foreach ($med->soro->medicamentos as $cm) {
                    if (! $cm->medicamento) {
                        continue;
                    }
                    $linhas[] = [
                        'key' => $med->id.'_'.count($linhas),
                        'medicamento' => $cm->medicamento,
                        'quantidade' => (float) $cm->quantidade,
                        'nome' => $cm->medicamento->nome,
                    ];
                }
            }

            $grupos[] = [
                'med' => $med,
                'nome' => $med->is_soro
                    ? ($med->soro?->nome ? 'Soro '.$med->soro->nome : 'Soro')
                    : ($med->combo_id ? 'Combo '.($med->combo?->nome ?? '') : ($med->medicamento?->nome ?? 'Item')),
                'linhas' => $linhas,
            ];
        }

        return $grupos;
    }
}
