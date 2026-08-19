<?php

namespace App\Http\Controllers;

use App\Models\Anexo;
use App\Models\Clinica;
use App\Models\Combo;
use App\Models\FinanceiroParcela;
use App\Models\Medicamento;
use App\Models\Paciente;
use App\Models\PagamentoParcela;
use App\Models\Prescricao;
use App\Models\PrescricaoLog;
use App\Models\PrescricaoObservacao;
use App\Models\PrescricaoPagamento;
use App\Models\PrescricaoPagamentoForma;
use App\Models\PrescricaoSemana;
use App\Models\PrescricaoSemanaMedicamento;
use App\Models\Soro;
use App\Services\FeegowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcedimentoSistemaController extends Controller
{
    // =====================================================================
    // LISTAGEM
    // =====================================================================

    /**
     * Lista as prescrições (procedimentos). Renderização via DataTables
     * server-side (rota procedimentos.dados).
     */
    public function index(Request $request)
    {
        $clinicas = Clinica::orderBy('nome')->get();

        $paciente = null;
        if ($request->filled('paciente_id')) {
            $paciente = Paciente::find($request->input('paciente_id'));
        }

        return view('procedimentos.index', compact('clinicas', 'paciente'));
    }

    /**
     * Endpoint server-side do DataTables.
     */
    public function datatable(Request $request)
    {
        $colunas = ['', 'nm_paciente', 'medico', 'data_prescricao', 'qt_semanas', 'qt_semanas_aplicacao', 'valor_tratamento', 'situacao', 'situacao_financeira'];

        $query = Prescricao::query()
            ->with(['paciente', 'semanas'])
            ->join('pacientes', 'pacientes.id', '=', 'prescricaos.paciente_id')
            ->select('prescricaos.*');

        // Filtro por clínica
        if ($request->filled('clinica_id')) {
            $query->where('prescricaos.clinica_id', $request->input('clinica_id'));
        }

        // Filtro por paciente (todos os procedimentos/prescrições do paciente)
        if ($request->filled('paciente_id')) {
            $query->where('prescricaos.paciente_id', (int) $request->input('paciente_id'));
        }

        // Filtro por situação
        if ($request->filled('situacao')) {
            $query->where('prescricaos.situacao', $request->input('situacao'));
        }

        // Filtro por período (data_prescricao)
        if ($request->filled('data_inicio')) {
            $query->whereDate('prescricaos.data_prescricao', '>=', Carbon::createFromFormat('d/m/Y', $request->input('data_inicio'))->format('Y-m-d'));
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('prescricaos.data_prescricao', '<=', Carbon::createFromFormat('d/m/Y', $request->input('data_fim'))->format('Y-m-d'));
        }

        $recordsTotal = (clone $query)->count();

        if ($request->filled('search.value')) {
            $busca = trim($request->input('search.value'));
            $query->where(function ($q) use ($busca) {
                $q->where('pacientes.nm_paciente', 'like', '%'.$busca.'%')
                    ->orWhere('pacientes.cpf', 'like', '%'.$busca.'%')
                    ->orWhere('prescricaos.medico', 'like', '%'.$busca.'%')
                    ->orWhere('prescricaos.codigo_versao1', 'like', '%'.$busca.'%');
            });
        }
        $recordsFiltered = (clone $query)->count();

        $indiceColuna = (int) $request->input('order.0.column', 0);
        $direcao = strtolower($request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (isset($colunas[$indiceColuna]) && $colunas[$indiceColuna] !== '') {
            $col = $colunas[$indiceColuna];
            $query->orderBy($col === 'nm_paciente' ? 'pacientes.nm_paciente' : 'prescricaos.'.$col, $direcao);
        } else {
            $query->orderBy('prescricaos.data_prescricao', 'desc');
        }

        $inicio = (int) $request->input('start', 0);
        $tamanho = (int) $request->input('length', 25);
        if ($tamanho < 0) {
            $tamanho = $recordsFiltered ?: 25;
        }

        $prescricoes = $query->offset($inicio)->limit($tamanho)->get();

        $data = $prescricoes->map(function ($p) {
            // Semana de aplicação atual: 1ª semana com aplicação ainda não aplicada
            $semanasAplicacao = $p->semanas->where('tem_aplicacao', true)->sortBy('nr_semana')->values();
            $aplicadas = $semanasAplicacao->where('situacao', 'Aplicado')->count();
            $atual = $semanasAplicacao->first(fn ($s) => ! in_array($s->situacao, ['Aplicado', 'Cancelada', 'Pendente']));

            if ($atual) {
                $semanaAplicacao = '<span class="fw-semibold">Aplicação '.($aplicadas + 1).'/'.max(1, (int) $p->qt_semanas_aplicacao).'</span>'
                    .'<span class="text-muted small ms-1">· Semana '.$atual->nr_semana.'</span>';
            } else {
                $semanaAplicacao = $p->qt_semanas_aplicacao > 0
                    ? '<span class="badge bg-label-success">Todas aplicadas</span>'
                    : '<span class="text-muted">—</span>';
            }

            return [
                '<a href="'.route('procedimentos.show', $p->id).'" class="btn btn-sm btn-icon btn-outline-secondary" title="Visualizar">'
                    .'<i class="ri-eye-line"></i></a>',
                $p->paciente?->nm_paciente ?? '-',
                $p->medico ?: '-',
                $p->data_prescricao ? $p->data_prescricao->format('d/m/Y') : '-',
                (string) $p->qt_semanas,
                $semanaAplicacao,
                valorDbForm($p->valor_tratamento),
                '<span class="badge rounded-pill bg-label-primary">'.$p->situacao.'</span>',
                '<span class="badge rounded-pill bg-label-success">'.$p->situacao_financeira.'</span>',
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    // =====================================================================
    // CADASTRO
    // =====================================================================

    /**
     * Formulário de cadastro de prescrição (cards por semana + financeiro).
     */
    public function create()
    {
        $clinicas = Clinica::orderBy('nome')->get();
        $medicamentos = Medicamento::orderBy('nome')->get(['id', 'nome', 'tipo', 'aplicacao']);

        $combos = Combo::with('medicamentos.medicamento')->orderBy('nome')->get()->each(function ($c) {
            $c->gera_aplicacao = $c->medicamentos->contains(fn ($cm) => $cm->medicamento && strtolower(trim((string) $cm->medicamento->aplicacao)) === 'sim');
        });
        $soros = Soro::with('medicamentos.medicamento')->orderBy('nome')->get()->each(function ($s) {
            $s->gera_aplicacao = $s->medicamentos->contains(fn ($sm) => $sm->medicamento && strtolower(trim((string) $sm->medicamento->aplicacao)) === 'sim');
        });

        // Médicos NÃO são buscados aqui (a Feegow é lenta e travaria a página).
        // Eles carregam via AJAX (rota procedimentos.medicos), com cache.
        return view('procedimentos.create', compact('clinicas', 'medicamentos', 'combos', 'soros'));
    }

    /**
     * Lista os médicos da Feegow em JSON (carregado via AJAX no cadastro,
     * com cache de 1h e timeout curto, para não travar a página).
     */
    public function medicos()
    {
        try {
            $medicos = Cache::remember('feegow.medicos', now()->addMinutes(60), function () {
                return app(FeegowService::class)->medicos();
            });
        } catch (\Throwable $e) {
            // Se a Feegow falhar, responde 503 (o JS mostra o aviso no select).
            return response()->json([], 503);
        }

        return response()->json(
            collect($medicos)->map(fn ($m) => [
                'id' => $m['profissional_nome'],
                'text' => $m['profissional_nome'],
            ])->values()
        );
    }

    /**
     * Grava a prescrição + semanas + medicações + parcelas + anexo + log.
     */
    public function store(Request $request)
    {
        $request->validate([
            'paciente_id' => 'required|integer|exists:pacientes,id',
            'clinica_id' => 'required|integer|exists:clinicas,id',
            'medico' => 'required|string|max:255',
            'data_prescricao' => 'required|date_format:d/m/Y',
            'qt_semanas' => 'required|integer|min:1|max:104',
            'periodicidade_dias' => 'required|integer|min:1|max:90',
            'valor_tratamento' => 'nullable|string',
            'credito_em_aberto' => 'nullable|string',
            'tipo_atendimento' => 'required|string|max:100',
            'agendamento' => 'nullable|string|max:255',
            'obs' => 'nullable|string|max:5000',
        ]);

        $dataInicial = Carbon::createFromFormat('d/m/Y', $request->data_prescricao);
        $qtSemanas = (int) $request->qt_semanas;
        $periodicidade = max(1, (int) ($request->periodicidade_dias ?: 7));

        // Bloqueia data retroativa (anterior a hoje)
        if ($dataInicial->startOfDay() < now()->startOfDay()) {
            return back()->withInput()->withErrors(['data_prescricao' => 'A data da prescrição não pode ser retroativa (anterior a hoje).']);
        }

        // Itens adicionados (tipo | id | qtds por semana | semanas[])
        $itensTipo = $request->input('item_tipo', []);
        $itensId = $request->input('item_id', []);
        $itensQtd = $request->input('item_qtd', []); // [itemIdx => [semana => qtd]]
        $itensSemanas = $request->input('item_semanas', []);

        $itens = [];
        for ($i = 0; $i < count($itensTipo); $i++) {
            $semanas = array_values(array_filter(array_map('intval', (array) ($itensSemanas[$i] ?? []))));
            if (! $semanas) {
                continue;
            }
            $qtds = [];
            foreach ((array) ($itensQtd[$i] ?? []) as $semana => $qtd) {
                $qtds[(int) $semana] = (float) str_replace(',', '.', $qtd);
            }
            $itens[] = [
                'tipo' => $itensTipo[$i],
                'id' => (int) ($itensId[$i] ?? 0),
                'qtds' => $qtds,
                'semanas' => $semanas,
            ];
        }

        // Resolve gera_aplicacao e requer_anexo por item
        foreach ($itens as &$item) {
            $item['gera_aplicacao'] = $this->itemGeraAplicacao($item['tipo'], $item['id']);
            $item['requer_anexo'] = $this->itemRequerAnexo($item['tipo'], $item['id']);
        }
        unset($item);

        // Semanas que terão aplicação
        $semanasComAplicacao = [];
        for ($w = 1; $w <= $qtSemanas; $w++) {
            foreach ($itens as $item) {
                if (in_array($w, $item['semanas']) && $item['gera_aplicacao']) {
                    $semanasComAplicacao[$w] = true;
                    break;
                }
            }
        }
        $qtSemanasAplicacao = count($semanasComAplicacao);

        // Semanas que exigem anexo (aplicação "Sim" de medicamento NÃO-Procedimento)
        $semanasRequerAnexo = [];
        for ($w = 1; $w <= $qtSemanas; $w++) {
            foreach ($itens as $item) {
                if (in_array($w, $item['semanas']) && $item['requer_anexo']) {
                    $semanasRequerAnexo[$w] = true;
                    break;
                }
            }
        }
        $qtSemanasRequerAnexo = count($semanasRequerAnexo);

        // Anexo prescrição obrigatório quando há aplicação de medicamento que exige (R3/D7)
        if ($qtSemanasRequerAnexo > 0 && ! $request->hasFile('anexo_prescricao')) {
            return back()->withInput()->withErrors(['anexo_prescricao' => 'Anexe a prescrição do médico (obrigatório quando há aplicação de medicamento).']);
        }

        // FERRO: dupla checagem obrigatória na recepção (R10)
        $temFerro = false;
        foreach ($itens as $item) {
            if ($item['tipo'] === 'medicamento' && $item['id']) {
                $med = Medicamento::find($item['id']);
                if ($med && $med->ehFerro()) {
                    $temFerro = true;
                    break;
                }
            }
        }
        if ($temFerro && ! $request->boolean('confirmar_ferro')) {
            return back()->withInput()->withErrors(['confirmar_ferro' => 'Confirme a dupla checagem do FERRO para aprovar o cadastro.']);
        }

        $valorTratamento = $request->filled('valor_tratamento') ? (float) valorFormDb($request->valor_tratamento) : 0;
        $creditoEmAberto = $request->filled('credito_em_aberto') ? (float) valorFormDb($request->credito_em_aberto) : 0;

        // Parcelas informadas (valor_parcela[] / dt_vencimento[] / obs_parcela[]) na ordem das semanas com aplicação
        $parcelasInformadas = array_values(array_filter($request->input('valor_parcela', []), fn ($v) => $v !== null && $v !== ''));
        $parcelasInformadas = array_map(fn ($v) => (float) valorFormDb($v), $parcelasInformadas);
        $vencimentos = array_values($request->input('dt_vencimento', []));
        $obsParcelas = array_values($request->input('obs_parcela', []));

        $totalAParcelar = max(0, $valorTratamento - $creditoEmAberto);

        $prescricaoId = null;
        DB::transaction(function () use (
            $request, $dataInicial, $qtSemanas, $periodicidade, $itens, $semanasComAplicacao, $qtSemanasAplicacao,
            $valorTratamento, $creditoEmAberto, $totalAParcelar, $parcelasInformadas, $vencimentos, $obsParcelas,
            &$prescricaoId
        ) {
            // 1) Cabeçalho
            $prescricao = Prescricao::create([
                'paciente_id' => $request->paciente_id,
                'clinica_id' => $request->clinica_id,
                'user_id_cadastro' => auth()->id(),
                'medico' => $request->medico,
                'tipo_atendimento' => $request->tipo_atendimento,
                'agendamento' => $request->agendamento,
                'data_prescricao' => $dataInicial->format('Y-m-d'),
                'qt_semanas' => $qtSemanas,
                'qt_semanas_aplicacao' => $qtSemanasAplicacao,
                'qt_parcelas' => $qtSemanasAplicacao,
                'periodicidade_dias' => $periodicidade,
                'semana_atual' => 0,
                'valor_tratamento' => $valorTratamento,
                'credito_em_aberto' => $creditoEmAberto,
                'situacao' => 'Agendada',
                'situacao_financeira' => $qtSemanasAplicacao > 0 ? 'Em Aberto' : 'Pago',
                'obs' => $request->obs ?: null,
            ]);
            $prescricaoId = $prescricao->id;

            // 2) Semanas + medicações
            $semanasModel = [];
            for ($w = 1; $w <= $qtSemanas; $w++) {
                $dataPrevista = $dataInicial->copy()->addDays(($w - 1) * $periodicidade);

                $semana = PrescricaoSemana::create([
                    'prescricao_id' => $prescricao->id,
                    'nr_semana' => $w,
                    'data_prevista' => $dataPrevista->format('Y-m-d'),
                    'tem_aplicacao' => isset($semanasComAplicacao[$w]),
                    'situacao' => 'Agendada',
                ]);
                $semanasModel[$w] = $semana;

                foreach ($itens as $item) {
                    if (! in_array($w, $item['semanas'])) {
                        continue;
                    }
                    PrescricaoSemanaMedicamento::create([
                        'prescricao_semana_id' => $semana->id,
                        'medicamento_id' => $item['tipo'] === 'medicamento' ? $item['id'] : null,
                        'combo_id' => $item['tipo'] === 'combo' ? $item['id'] : null,
                        'soro_id' => $item['tipo'] === 'soro' ? $item['id'] : null,
                        'is_soro' => $item['tipo'] === 'soro',
                        'gera_aplicacao' => $item['gera_aplicacao'],
                        'quantidade' => $item['qtds'][$w] ?? 1,
                        'situacao' => 'Aberta',
                        'data_prevista' => $dataPrevista->format('Y-m-d'),
                    ]);
                }
            }

            // 3) Parcelas (1 por semana com aplicação, na ordem)
            $nrParcela = 0;
            $ordemParcela = 0;
            for ($w = 1; $w <= $qtSemanas; $w++) {
                if (! isset($semanasComAplicacao[$w])) {
                    continue;
                }
                $nrParcela++;
                $valorParcela = $parcelasInformadas[$ordemParcela] ?? 0;
                if ($valorParcela <= 0 && $qtSemanasAplicacao > 0) {
                    // default: total ÷ nº parcelas (diferença de centavos na última)
                    $base = $totalAParcelar / $qtSemanasAplicacao;
                    $valorParcela = $nrParcela === $qtSemanasAplicacao
                        ? round($totalAParcelar - $base * ($qtSemanasAplicacao - 1), 2)
                        : round($base, 2);
                }
                FinanceiroParcela::create([
                    'prescricao_id' => $prescricao->id,
                    'prescricao_semana_id' => $semanasModel[$w]->id,
                    'nr_parcela' => $nrParcela,
                    'valor_parcela' => $valorParcela,
                    'valor_pago' => 0,
                    'situacao' => 'Em Aberto',
                    'dt_vencimento' => $vencimentos[$ordemParcela] ?? $semanasModel[$w]->data_prevista,
                    'obs' => $obsParcelas[$ordemParcela] ?? null,
                ]);
                $ordemParcela++;
            }

            // 4) Anexo prescrição
            if ($request->hasFile('anexo_prescricao')) {
                $arquivo = $request->file('anexo_prescricao');
                $nomeOriginal = $arquivo->getClientOriginalName();
                $caminho = $arquivo->store('anexos/prescricoes/'.$prescricao->id, 'public');

                Anexo::create([
                    'tipo' => 'prescricao',
                    'prescricao_id' => $prescricao->id,
                    'user_id' => auth()->id(),
                    'nm_anexo' => $nomeOriginal,
                    'arquivo' => $caminho,
                    'mime' => $arquivo->getMimeType(),
                    'extensao' => $arquivo->getClientOriginalExtension(),
                ]);
            }

            // 5) Log
            PrescricaoLog::create([
                'prescricao_id' => $prescricao->id,
                'entidade' => 'prescricao',
                'entidade_id' => $prescricao->id,
                'user_id' => auth()->id(),
                'acao' => 'criado',
                'descricao' => 'Prescrição criada com '.$qtSemanas.' semana(s) e '.$qtSemanasAplicacao.' parcela(s).',
            ]);
        });

        return redirect()->route('procedimentos.show', $prescricaoId)
            ->with('mensagem', 'Procedimento cadastrado com sucesso.');
    }

    /**
     * Detalhe da prescrição (resumo + semanas + financeiro + anexos + log).
     */
    public function show($id)
    {
        $prescricao = Prescricao::with([
            'paciente', 'clinica', 'userCadastro', 'semanas.medicamentos.medicamento',
            'semanas.medicamentos.combo', 'semanas.medicamentos.soro', 'semanas.medicamentos.clinicaAplicacao',
            'semanas.medicamentos.userAplicacao', 'financeiroParcelas.semana',
            'financeiroParcelas.pagamentos.pagamento.formas',
            'financeiroParcelas.pagamentos.pagamento.user',
            'financeiroParcelas.pagamentos.pagamento.anexos',
            'pagamentos.formas', 'pagamentos.parcelasPagas.financeiroParcela.semana',
            'pagamentos.anexos', 'pagamentos.user', 'anexos', 'logs.user', 'observacoes.user',
        ])->findOrFail($id);

        $medicamentos = Medicamento::orderBy('nome')->get(['id', 'nome', 'aplicacao']);

        $combos = Combo::with('medicamentos.medicamento')->orderBy('nome')->get()->each(function ($c) {
            $c->gera_aplicacao = $c->medicamentos->contains(fn ($cm) => $cm->medicamento && strtolower(trim((string) $cm->medicamento->aplicacao)) === 'sim');
        });
        $soros = Soro::with('medicamentos.medicamento')->orderBy('nome')->get()->each(function ($s) {
            $s->gera_aplicacao = $s->medicamentos->contains(fn ($sm) => $sm->medicamento && strtolower(trim((string) $sm->medicamento->aplicacao)) === 'sim');
        });

        return view('procedimentos.show', compact('prescricao', 'medicamentos', 'combos', 'soros'));
    }

    /**
     * Página de impressão com todas as informações da prescrição + histórico
     * de alterações (usado por "Imprimir Detalhes" e "Prontuário Completo").
     */
    public function imprimirDetalhes($id)
    {
        $prescricao = Prescricao::with([
            'paciente', 'clinica', 'userCadastro', 'semanas.medicamentos.medicamento',
            'semanas.medicamentos.combo', 'semanas.medicamentos.soro',
            'semanas.medicamentos.userAplicacao', 'semanas.medicamentos.lotes',
            'financeiroParcelas.semana', 'pagamentos.formas', 'pagamentos.user',
            'anexos', 'logs.user', 'observacoes.user',
        ])->findOrFail($id);

        $totalParcelas = (float) $prescricao->financeiroParcelas->sum('valor_parcela');
        $saldo = (float) $prescricao->financeiroParcelas->sum('valor_parcela') - (float) $prescricao->financeiroParcelas->sum('valor_pago');

        return view('procedimentos.imprimir_detalhes', compact('prescricao', 'totalParcelas', 'saldo'));
    }

    /**
     * Gera o prontuário completo (imprimir detalhes) como um único PDF (R19).
     */
    public function imprimirDetalhesPdf($id)
    {
        $prescricao = Prescricao::with([
            'paciente', 'clinica', 'userCadastro', 'semanas.medicamentos.medicamento',
            'semanas.medicamentos.combo', 'semanas.medicamentos.soro',
            'semanas.medicamentos.userAplicacao', 'semanas.medicamentos.lotes',
            'financeiroParcelas.semana', 'pagamentos.formas', 'pagamentos.user',
            'anexos', 'logs.user', 'observacoes.user',
        ])->findOrFail($id);

        $totalParcelas = (float) $prescricao->financeiroParcelas->sum('valor_parcela');
        $saldo = (float) $prescricao->financeiroParcelas->sum('valor_parcela') - (float) $prescricao->financeiroParcelas->sum('valor_pago');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('procedimentos.imprimir_detalhes_pdf', compact('prescricao', 'totalParcelas', 'saldo'));

        return $pdf->download('prontuario-'.$prescricao->id.'.pdf');
    }

    /**
     * Adiciona uma observação livre à prescrição (guia Observações).
     */
    public function adicionarObservacao(Request $request, $id)
    {
        $request->validate([
            'observacao' => 'required|string|max:2000',
        ]);

        $prescricao = Prescricao::findOrFail($id);
        PrescricaoObservacao::create([
            'prescricao_id' => $prescricao->id,
            'user_id' => auth()->id(),
            'observacao' => trim($request->observacao),
        ]);

        return back()->with('mensagem', 'Observação adicionada.');
    }

    /**
     * Exclui uma observação da prescrição.
     */
    public function excluirObservacao(Request $request, $id, $observacaoId)
    {
        $prescricao = Prescricao::findOrFail($id);
        $obs = PrescricaoObservacao::where('id', $observacaoId)->where('prescricao_id', $prescricao->id)->firstOrFail();
        $obs->delete();

        return back()->with('mensagem', 'Observação excluída.');
    }

    /**
     * Adiciona medicamento(s)/combo(s)/soro(s) a uma semana NÃO aplicada e
     * distribui o valor informado: somente na parcela da semana OU rateado
     * entre todas as parcelas da prescrição.
     */
    public function adicionarMedicamentoSemana(Request $request, $id)
    {
        $request->validate([
            'semana_id' => 'required|integer',
            'item_tipo' => 'required|array|min:1',
            'item_tipo.*' => 'required|in:medicamento,combo,soro',
            'item_id' => 'required|array|min:1',
            'item_id.*' => 'required|integer',
            'item_qtd' => 'required|array|min:1',
            'item_qtd.*' => 'required|numeric|min:0.01',
            'valor' => 'nullable|string',
            'distribuicao' => 'required|in:semana,parcelas',
        ]);

        if ($request->distribuicao === 'parcelas' && ! $request->filled('parcelas')) {
            return back()->with('mensagem_erro', 'Selecione pelo menos uma parcela para ratear o valor.');
        }

        $prescricao = Prescricao::findOrFail($id);
        $semana = PrescricaoSemana::where('id', $request->semana_id)->where('prescricao_id', $prescricao->id)->firstOrFail();

        // Não permitir em semanas já aplicadas
        if (in_array($semana->situacao, ['Aplicado', 'Aplicação Parcial', 'Pendente'])) {
            return back()->with('mensagem_erro', 'Não é possível adicionar medicamento em semana já aplicada.');
        }

        $valor = $request->filled('valor') ? (float) valorFormDb($request->valor) : 0;

        DB::transaction(function () use ($request, $prescricao, $semana, $valor) {
            $semanaGanhouAplicacao = false;
            $itensDescricao = [];

            foreach ($request->item_tipo as $i => $tipo) {
                $itemId = (int) $request->item_id[$i];
                $qtd = (float) str_replace(',', '.', $request->item_qtd[$i]);
                $gera = $this->itemGeraAplicacao($tipo, $itemId);

                PrescricaoSemanaMedicamento::create([
                    'prescricao_semana_id' => $semana->id,
                    'medicamento_id' => $tipo === 'medicamento' ? $itemId : null,
                    'combo_id' => $tipo === 'combo' ? $itemId : null,
                    'soro_id' => $tipo === 'soro' ? $itemId : null,
                    'is_soro' => $tipo === 'soro',
                    'gera_aplicacao' => $gera,
                    'quantidade' => $qtd,
                    'situacao' => 'Aberta',
                    'data_prevista' => $semana->data_prevista,
                ]);

                $nomeItem = match ($tipo) {
                    'medicamento' => Medicamento::find($itemId)?->nome,
                    'combo' => Combo::find($itemId)?->nome,
                    default => Soro::find($itemId)?->nome,
                };
                $itensDescricao[] = ($nomeItem ?? "#{$itemId}").' x'.rtrim(rtrim(number_format($qtd, 2, ',', ''), '0'), ',');

                if ($gera && ! $semana->tem_aplicacao) {
                    $semanaGanhouAplicacao = true;
                }
            }

            // A semana passou a ter aplicação?
            if ($semanaGanhouAplicacao) {
                $semana->tem_aplicacao = true;
                $prescricao->qt_semanas_aplicacao = (int) $prescricao->qt_semanas_aplicacao + 1;
                $prescricao->qt_parcelas = (int) $prescricao->qt_parcelas + 1;

                if (! $semana->financeiroParcela) {
                    $nrParcela = (int) $prescricao->financeiroParcelas()->max('nr_parcela') + 1;
                    FinanceiroParcela::create([
                        'prescricao_id' => $prescricao->id,
                        'prescricao_semana_id' => $semana->id,
                        'nr_parcela' => $nrParcela,
                        'valor_parcela' => 0,
                        'valor_pago' => 0,
                        'situacao' => 'Em Aberto',
                        'dt_vencimento' => $semana->data_prevista,
                    ]);
                }
            }
            $semana->save();

            // Distribui o valor informado
            if ($valor > 0) {
                if ($request->distribuicao === 'semana') {
                    $parcela = $semana->financeiroParcela;
                    if (! $parcela) {
                        $nrParcela = (int) $prescricao->financeiroParcelas()->max('nr_parcela') + 1;
                        $parcela = FinanceiroParcela::create([
                            'prescricao_id' => $prescricao->id,
                            'prescricao_semana_id' => $semana->id,
                            'nr_parcela' => $nrParcela,
                            'valor_parcela' => 0,
                            'valor_pago' => 0,
                            'situacao' => 'Em Aberto',
                            'dt_vencimento' => $semana->data_prevista,
                        ]);
                    }
                    $parcela->valor_parcela = round((float) $parcela->valor_parcela + $valor, 2);
                    $parcela->save();
                    $this->atualizarSituacaoParcela($parcela);
                } else {
                    $parcelaIds = array_values(array_filter((array) $request->input('parcelas', [])));
                    $parcelas = $prescricao->financeiroParcelas()->whereIn('id', $parcelaIds)->orderBy('nr_parcela')->get();
                    if ($parcelas->isNotEmpty()) {
                        $base = $valor / $parcelas->count();
                        $total = $parcelas->count();
                        $parcelas->each(function ($p, $idx) use ($base, $valor, $total) {
                            $parte = $idx === $total - 1
                                ? round($valor - $base * ($total - 1), 2)
                                : round($base, 2);
                            $p->valor_parcela = round((float) $p->valor_parcela + $parte, 2);
                            $p->save();
                            $this->atualizarSituacaoParcela($p);
                        });
                    }
                }

                $prescricao->valor_tratamento = round((float) $prescricao->valor_tratamento + $valor, 2);
            }

            $prescricao->save();
            $this->atualizarSituacaoFinanceira($prescricao);

            PrescricaoLog::create([
                'prescricao_id' => $prescricao->id,
                'entidade' => 'prescricao_semana',
                'entidade_id' => $semana->id,
                'user_id' => auth()->id(),
                'acao' => 'medicamento_adicionado',
                'descricao' => 'Adicionado(s) à semana '.$semana->nr_semana.': '.implode(', ', $itensDescricao)
                    .' — valor R$ '.valorDbForm($valor).' ('.($request->distribuicao === 'semana' ? 'somente na semana' : 'rateado nas parcelas selecionadas').').',
            ]);
        });

        return redirect()->route('procedimentos.show', $prescricao->id)
            ->with('mensagem', 'Medicamento(s) adicionado(s) à semana '.$semana->nr_semana.'.');
    }

    /**
     * Exclui um medicamento de uma semana, desde que ainda NÃO tenha sido
     * aplicado. A parcela/valor da semana permanece como está (ajuste manual
     * se necessário).
     */
    public function excluirMedicamentoSemana(Request $request, $id, $medicamentoId)
    {
        $prescricao = Prescricao::findOrFail($id);
        $med = PrescricaoSemanaMedicamento::with('semana')->where('id', $medicamentoId)->firstOrFail();

        // o medicamento precisa pertencer a uma semana desta prescrição
        if (! $med->semana || $med->semana->prescricao_id !== $prescricao->id) {
            abort(404);
        }

        if ($med->aplicado_em) {
            return back()->with('mensagem_erro', 'Não é possível excluir um medicamento que já foi aplicado.');
        }

        $nome = $med->soro?->nome ?? $med->combo?->nome ?? $med->medicamento?->nome ?? '-';

        DB::transaction(function () use ($prescricao, $med, $nome) {
            $semana = $med->semana;
            $med->delete();

            PrescricaoLog::create([
                'prescricao_id' => $prescricao->id,
                'entidade' => 'prescricao_semana_medicamento',
                'entidade_id' => $med->id,
                'user_id' => auth()->id(),
                'acao' => 'medicamento_excluido',
                'descricao' => 'Medicamento removido da semana '.$semana->nr_semana.': '.$nome,
            ]);
        });

        return back()->with('mensagem', 'Medicamento removido da semana.');
    }

    /**
     * Edita um medicamento de uma semana (quantidade, data prevista e obs),
     * desde que ainda NÃO tenha sido aplicado. Não altera o financeiro.
     */
    public function atualizarMedicamentoSemana(Request $request, $id, $medicamentoId)
    {
        $request->validate([
            'quantidade' => 'required|string',
            'data_prevista' => 'nullable|date_format:d/m/Y',
            'obs' => 'nullable|string|max:1000',
        ]);

        $prescricao = Prescricao::findOrFail($id);
        $med = PrescricaoSemanaMedicamento::with('semana')->where('id', $medicamentoId)->firstOrFail();

        // o medicamento precisa pertencer a uma semana desta prescrição
        if (! $med->semana || $med->semana->prescricao_id !== $prescricao->id) {
            abort(404);
        }

        if ($med->aplicado_em) {
            return back()->with('mensagem_erro', 'Não é possível editar um medicamento que já foi aplicado.');
        }

        $qtd = (float) str_replace(',', '.', trim($request->quantidade));
        if ($qtd <= 0) {
            return back()->with('mensagem_erro', 'Quantidade inválida.');
        }

        $nome = $med->soro?->nome ?? $med->combo?->nome ?? $med->medicamento?->nome ?? '-';
        $qtdAntes = $med->quantidade;

        $med->quantidade = round($qtd, 3);
        if ($request->filled('data_prevista')) {
            $med->data_prevista = Carbon::createFromFormat('d/m/Y', $request->data_prevista)->format('Y-m-d');
        }
        $med->obs = $request->obs ?: null;
        $med->save();

        PrescricaoLog::create([
            'prescricao_id' => $prescricao->id,
            'entidade' => 'prescricao_semana_medicamento',
            'entidade_id' => $med->id,
            'user_id' => auth()->id(),
            'acao' => 'medicamento_atualizado',
            'descricao' => 'Medicamento atualizado na semana '.$med->semana->nr_semana.': '.$nome.' (qtd '.$qtdAntes.' → '.$med->quantidade.').',
        ]);

        return back()->with('mensagem', 'Medicamento atualizado.');
    }

    /**
     * Salva a observação particular de uma semana.
     */
    public function atualizarObsSemana(Request $request, $id, $semanaId)
    {
        $request->validate([
            'obs' => 'nullable|string|max:2000',
        ]);

        $prescricao = Prescricao::findOrFail($id);
        $semana = PrescricaoSemana::where('id', $semanaId)->where('prescricao_id', $prescricao->id)->firstOrFail();

        $semana->obs = $request->obs ?: null;
        $semana->save();

        PrescricaoLog::create([
            'prescricao_id' => $prescricao->id,
            'entidade' => 'prescricao_semana',
            'entidade_id' => $semana->id,
            'user_id' => auth()->id(),
            'acao' => 'semana_obs_atualizada',
            'descricao' => 'Observação da semana '.$semana->nr_semana.' atualizada.',
        ]);

        return back()->with('mensagem', 'Observação da semana '.$semana->nr_semana.' salva.');
    }

    /**
     * Página para adicionar semanas ao final da prescrição (como o cadastro):
     * medicações por semana + anexo + valor rateado nas novas parcelas.
     */
    public function adicionarSemanasView($id)
    {
        $prescricao = Prescricao::with('paciente', 'clinica')->findOrFail($id);
        $ultima = PrescricaoSemana::where('prescricao_id', $prescricao->id)->orderBy('nr_semana', 'desc')->first();
        $dataBase = $ultima?->data_prevista ? Carbon::parse($ultima->data_prevista) : Carbon::today();

        $medicamentos = Medicamento::orderBy('nome')->get(['id', 'nome', 'aplicacao']);
        $combos = Combo::with('medicamentos.medicamento')->orderBy('nome')->get()->each(function ($c) {
            $c->gera_aplicacao = $c->medicamentos->contains(fn ($cm) => $cm->medicamento && strtolower(trim((string) $cm->medicamento->aplicacao)) === 'sim');
        });
        $soros = Soro::with('medicamentos.medicamento')->orderBy('nome')->get()->each(function ($s) {
            $s->gera_aplicacao = $s->medicamentos->contains(fn ($sm) => $sm->medicamento && strtolower(trim((string) $sm->medicamento->aplicacao)) === 'sim');
        });

        return view('procedimentos.adicionar_semanas', compact('prescricao', 'medicamentos', 'combos', 'soros', 'dataBase'));
    }

    /**
     * Grava as semanas adicionadas ao final da prescrição, com as medicações
     * por semana (item_tipo/item_id/item_qtd/item_semanas), anexo e o valor
     * rateado somente entre as novas parcelas.
     */
    public function adicionarSemanasStore(Request $request, $id)
    {
        $request->validate([
            'qt_semanas_adicionar' => 'required|integer|min:1|max:52',
            'item_tipo' => 'nullable|array',
            'item_id' => 'nullable|array',
            'item_qtd' => 'nullable|array',
            'item_semanas' => 'nullable|array',
            'valor' => 'nullable|string',
        ]);

        $prescricao = Prescricao::findOrFail($id);
        $qtAdicionar = (int) $request->qt_semanas_adicionar;

        // Parse dos itens (mesmo padrão do store do cadastro)
        $itensTipo = $request->input('item_tipo', []);
        $itensId = $request->input('item_id', []);
        $itensQtd = $request->input('item_qtd', []);
        $itensSemanas = $request->input('item_semanas', []);

        $itens = [];
        for ($i = 0; $i < count($itensTipo); $i++) {
            $semanas = array_values(array_filter(array_map('intval', (array) ($itensSemanas[$i] ?? []))));
            if (! $semanas) {
                continue;
            }
            $qtds = [];
            foreach ((array) ($itensQtd[$i] ?? []) as $semana => $qtd) {
                $qtds[(int) $semana] = (float) str_replace(',', '.', $qtd);
            }
            $itens[] = ['tipo' => $itensTipo[$i], 'id' => (int) ($itensId[$i] ?? 0), 'qtds' => $qtds, 'semanas' => $semanas];
        }
        foreach ($itens as &$item) {
            $item['gera_aplicacao'] = $this->itemGeraAplicacao($item['tipo'], $item['id']);
        }
        unset($item);

        $valor = $request->filled('valor') ? (float) valorFormDb($request->valor) : 0;

        DB::transaction(function () use ($prescricao, $qtAdicionar, $itens, $valor, $request) {
            $ultima = PrescricaoSemana::where('prescricao_id', $prescricao->id)->orderBy('nr_semana', 'desc')->first();
            $baseNr = $ultima ? (int) $ultima->nr_semana : 0;
            $dataBase = $ultima?->data_prevista ? Carbon::parse($ultima->data_prevista) : Carbon::today();

            $novasParcelas = [];
            $nrParcela = (int) $prescricao->financeiroParcelas()->max('nr_parcela') + 1;
            $qtComAplicacao = 0;
            $periodicidade = max(1, (int) ($prescricao->periodicidade_dias ?: 7));

            for ($w = 1; $w <= $qtAdicionar; $w++) {
                $dataPrevista = $dataBase->copy()->addDays($periodicidade * $w);
                $temAplicacao = false;

                $semana = PrescricaoSemana::create([
                    'prescricao_id' => $prescricao->id,
                    'nr_semana' => $baseNr + $w,
                    'data_prevista' => $dataPrevista->format('Y-m-d'),
                    'tem_aplicacao' => false,
                    'situacao' => 'Agendada',
                ]);

                foreach ($itens as $item) {
                    if (! in_array($w, $item['semanas'])) {
                        continue;
                    }
                    PrescricaoSemanaMedicamento::create([
                        'prescricao_semana_id' => $semana->id,
                        'medicamento_id' => $item['tipo'] === 'medicamento' ? $item['id'] : null,
                        'combo_id' => $item['tipo'] === 'combo' ? $item['id'] : null,
                        'soro_id' => $item['tipo'] === 'soro' ? $item['id'] : null,
                        'is_soro' => $item['tipo'] === 'soro',
                        'gera_aplicacao' => $item['gera_aplicacao'],
                        'quantidade' => $item['qtds'][$w] ?? 1,
                        'situacao' => 'Aberta',
                        'data_prevista' => $dataPrevista->format('Y-m-d'),
                    ]);
                    if ($item['gera_aplicacao']) {
                        $temAplicacao = true;
                    }
                }
                $semana->tem_aplicacao = $temAplicacao;
                $semana->save();
                if ($temAplicacao) {
                    $qtComAplicacao++;
                }

                // Nova parcela SOMENTE para semanas com aplicação (igual ao cadastro).
                // Semanas sem aplicação não recebem parcela nem participam do rateio.
                if ($temAplicacao) {
                    $parcela = FinanceiroParcela::create([
                        'prescricao_id' => $prescricao->id,
                        'prescricao_semana_id' => $semana->id,
                        'nr_parcela' => $nrParcela,
                        'valor_parcela' => 0,
                        'valor_pago' => 0,
                        'situacao' => 'Em Aberto',
                        'dt_vencimento' => $dataPrevista->format('Y-m-d'),
                    ]);
                    $novasParcelas[] = $parcela;
                    $nrParcela++;
                }
            }

            $prescricao->qt_semanas = (int) $prescricao->qt_semanas + $qtAdicionar;
            $prescricao->qt_semanas_aplicacao = (int) $prescricao->qt_semanas_aplicacao + $qtComAplicacao;
            $prescricao->qt_parcelas = (int) $prescricao->qt_parcelas + $qtComAplicacao;

            // Rateia o valor SOMENTE nas novas parcelas
            if ($valor > 0 && count($novasParcelas) > 0) {
                $base = $valor / count($novasParcelas);
                $total = count($novasParcelas);
                foreach ($novasParcelas as $idx => $p) {
                    $parte = $idx === $total - 1 ? round($valor - $base * ($total - 1), 2) : round($base, 2);
                    $p->valor_parcela = round((float) $p->valor_parcela + $parte, 2);
                    $p->save();
                    $this->atualizarSituacaoParcela($p);
                }
                $prescricao->valor_tratamento = round((float) $prescricao->valor_tratamento + $valor, 2);
            }

            $prescricao->save();
            $this->atualizarSituacaoFinanceira($prescricao);

            // Anexo (opcional)
            if ($request->hasFile('anexo_prescricao')) {
                $arquivo = $request->file('anexo_prescricao');
                $caminho = $arquivo->store('anexos/prescricoes/'.$prescricao->id, 'public');
                Anexo::create([
                    'tipo' => 'prescricao',
                    'prescricao_id' => $prescricao->id,
                    'user_id' => auth()->id(),
                    'nm_anexo' => $arquivo->getClientOriginalName(),
                    'arquivo' => $caminho,
                    'mime' => $arquivo->getMimeType(),
                    'extensao' => $arquivo->getClientOriginalExtension(),
                ]);
            }

            PrescricaoLog::create([
                'prescricao_id' => $prescricao->id,
                'entidade' => 'prescricao',
                'entidade_id' => $prescricao->id,
                'user_id' => auth()->id(),
                'acao' => 'semanas_adicionadas',
                'descricao' => 'Adicionadas '.$qtAdicionar.' semana(s) ao final ('.$qtComAplicacao.' com aplicação). Valor rateado nas novas parcelas: R$ '.valorDbForm($valor).'.',
            ]);
        });

        return redirect()->route('procedimentos.show', $prescricao->id)
            ->with('mensagem', $qtAdicionar.' semana(s) adicionada(s) à prescrição.');
    }

    // =====================================================================
    // CANCELAMENTO
    // =====================================================================

    public function cancelar(Request $request, $id)
    {
        $prescricao = Prescricao::findOrFail($id);

        $request->validate(['motivo' => 'required|string|max:2000']);

        if ($prescricao->situacao_financeira === 'Pago') {
            return back()->with('mensagem_erro', 'Não é possível cancelar: prescrição totalmente paga. Cancele os pagamentos antes.');
        }

        // Semanas já aplicadas?
        $aplicadas = $prescricao->semanas()->whereIn('situacao', ['Aplicada', 'Aplicação Parcial'])->count();
        if ($aplicadas > 0) {
            return back()->with('mensagem_erro', 'Não é possível cancelar: há semana(s) já aplicada(s). Cancele a aplicação antes.');
        }

        $prescricao->update(['situacao' => 'Cancelada', 'situacao_financeira' => 'Cancelado']);
        Prescricao::where('id', $prescricao->id)->update(['situacao' => 'Cancelada', 'situacao_financeira' => 'Cancelado']);

        PrescricaoLog::create([
            'prescricao_id' => $prescricao->id,
            'entidade' => 'prescricao',
            'entidade_id' => $prescricao->id,
            'user_id' => auth()->id(),
            'acao' => 'cancelado',
            'descricao' => $request->motivo,
        ]);

        return back()->with('mensagem', 'Prescrição cancelada.');
    }

    // =====================================================================
    // FINANCEIRO — PAGAMENTOS
    // =====================================================================

    /**
     * Lança um pagamento (evento + formas + distribuição por parcela).
     * Regra: soma das formas = soma da distribuição = vl_total (não permite
     * pagar mais do que o devido — bloqueia sobrepagamento).
     */
    public function salvarPagamento(Request $request, $id)
    {
        $prescricao = Prescricao::findOrFail($id);

        $request->validate([
            'dt_pagamento' => 'required|date_format:d/m/Y',
            'forma_pagamento' => 'required|array|min:1',
            'forma_pagamento.*' => 'required|string',
            'vl_pagamento' => 'required|array|min:1',
            'vl_pagamento.*' => 'required|string',
            'obs' => 'nullable|string|max:5000',
        ]);

        $dataPagamento = Carbon::createFromFormat('d/m/Y', $request->dt_pagamento);

        $formas = [];
        $totalFormas = 0;
        foreach ($request->forma_pagamento as $i => $forma) {
            $vl = $this->valorPagamentoForm($request->vl_pagamento[$i] ?? '0');
            if ($vl <= 0) {
                continue;
            }
            $formas[] = [
                'forma' => $forma,
                'vl' => $vl,
                'parcelas' => $forma === 'Cartão de Crédito' ? (int) ($request->forma_parcelas[$i] ?? 1) : 1,
                'id_transacao' => $request->forma_id_transacao[$i] ?? null,
            ];
            $totalFormas += $vl;
        }

        if (! $formas) {
            return back()->withInput()->withErrors(['vl_pagamento' => 'Informe pelo menos um valor de pagamento.']);
        }

        // Distribuição: parcela_id => valor
        $distribuicao = [];
        foreach ($request->input('dist_parcela_id', []) as $i => $parcelaId) {
            $vl = (float) valorFormDb($request->input('dist_valor.'.$i, '0'));
            if ($vl > 0) {
                $distribuicao[(int) $parcelaId] = $vl;
            }
        }

        $totalDistribuicao = array_sum($distribuicao);

        // Bloqueio de sobrepagamento
        if (abs($totalFormas - $totalDistribuicao) > 0.009) {
            return back()->withInput()->withErrors(['dist_valor' => 'A soma da distribuição (R$ '.valorDbForm($totalDistribuicao).') não bate com o total das formas (R$ '.valorDbForm($totalFormas).').']);
        }

        $parcelas = FinanceiroParcela::where('prescricao_id', $prescricao->id)->with('prescricao')->get()->keyBy('id');

        DB::transaction(function () use ($request, $prescricao, $dataPagamento, $formas, $totalFormas, $distribuicao, $parcelas) {
            $pagamento = PrescricaoPagamento::create([
                'prescricao_id' => $prescricao->id,
                'dt_pagamento' => $dataPagamento->format('Y-m-d'),
                'vl_total' => $totalFormas,
                'obs' => $request->obs ?: null,
                'user_id' => auth()->id(),
            ]);

            foreach ($formas as $f) {
                PrescricaoPagamentoForma::create([
                    'pagamento_id' => $pagamento->id,
                    'forma_pagamento' => $f['forma'],
                    'vl_pagamento' => $f['vl'],
                    'parcelas' => $f['parcelas'],
                    'id_transacao' => $f['id_transacao'],
                ]);
            }

            // Aplica na parcela (não deixa ultrapassar o valor da parcela)
            foreach ($distribuicao as $parcelaId => $valor) {
                $parcela = $parcelas->get($parcelaId);
                if (! $parcela) {
                    continue;
                }
                $restante = max(0, (float) $parcela->valor_parcela - (float) $parcela->valor_pago);
                $aplicar = min($valor, $restante);

                PagamentoParcela::create([
                    'pagamento_id' => $pagamento->id,
                    'financeiro_parcela_id' => $parcela->id,
                    'valor' => $aplicar,
                ]);

                $novoPago = (float) $parcela->valor_pago + $aplicar;
                $situacao = $novoPago >= (float) $parcela->valor_parcela - 0.009 ? 'Paga' : ($novoPago > 0 ? 'Parcial' : 'Em Aberto');
                $parcela->update(['valor_pago' => $novoPago, 'situacao' => $situacao]);
            }

            // Atualiza situação financeira da prescrição
            $this->atualizarSituacaoFinanceira($prescricao);

            // Anexos comprovante (múltiplos)
            foreach ((array) $request->file('anexos_comprovante', []) as $arquivo) {
                $caminho = $arquivo->store('anexos/comprovantes/'.$prescricao->id, 'public');

                Anexo::create([
                    'tipo' => 'comprovante_pagamento',
                    'prescricao_id' => $prescricao->id,
                    'pagamento_id' => $pagamento->id,
                    'user_id' => auth()->id(),
                    'nm_anexo' => $arquivo->getClientOriginalName(),
                    'arquivo' => $caminho,
                    'mime' => $arquivo->getMimeType(),
                    'extensao' => $arquivo->getClientOriginalExtension(),
                ]);
            }

            // Demonstrativo de pagamento (não vai para o imprimir do cadastro)
            $this->salvarDemonstrativoPagamento($request, $prescricao->id, $pagamento->id);

            PrescricaoLog::create([
                'prescricao_id' => $prescricao->id,
                'entidade' => 'pagamento',
                'entidade_id' => $pagamento->id,
                'user_id' => auth()->id(),
                'acao' => 'pago',
                'descricao' => 'Pagamento de R$ '.valorDbForm($totalFormas).' ('.count($formas).' forma(s)).',
            ]);
        });

        return back()->with('mensagem', 'Pagamento lançado com sucesso.');
    }

    /**
     * Pagamento individual de uma parcela (botão na tabela financeiro).
     * Pode pagar o valor integral ou parcial, mas NUNCA mais que o saldo
     * da parcela. Aceita múltiplas formas e múltiplos comprovantes.
     */
    public function pagarParcela(Request $request, $id, $parcelaId)
    {
        $prescricao = Prescricao::findOrFail($id);
        $parcela = FinanceiroParcela::where('id', $parcelaId)->where('prescricao_id', $prescricao->id)->firstOrFail();

        $request->validate([
            'dt_pagamento' => 'required|date_format:d/m/Y',
            'forma_pagamento' => 'required|array|min:1',
            'forma_pagamento.*' => 'required|string',
            'vl_pagamento' => 'required|array|min:1',
            'vl_pagamento.*' => 'required|string',
            'obs' => 'nullable|string|max:5000',
        ]);

        $dataPagamento = Carbon::createFromFormat('d/m/Y', $request->dt_pagamento);

        $saldo = max(0, (float) $parcela->valor_parcela - (float) $parcela->valor_pago);
        if ($saldo <= 0) {
            return back()->with('mensagem_erro', 'A parcela '.$parcela->nr_parcela.' já está totalmente paga.');
        }

        $formas = [];
        $totalFormas = 0;
        foreach ($request->forma_pagamento as $i => $forma) {
            $vl = $this->valorPagamentoForm($request->vl_pagamento[$i] ?? '0');
            if ($vl <= 0) {
                continue;
            }
            $formas[] = [
                'forma' => $forma,
                'vl' => $vl,
                'parcelas' => $forma === 'Cartão de Crédito' ? (int) ($request->forma_parcelas[$i] ?? 1) : 1,
                'id_transacao' => $request->forma_id_transacao[$i] ?? null,
            ];
            $totalFormas += $vl;
        }

        if (! $formas) {
            return back()->withInput()->withErrors(['vl_pagamento' => 'Informe pelo menos um valor de pagamento.']);
        }

        if ($totalFormas > $saldo + 0.009) {
            return back()->with('mensagem_erro', 'O total do pagamento (R$ '.valorDbForm($totalFormas).') não pode ser maior que o saldo da parcela (R$ '.valorDbForm($saldo).').');
        }

        DB::transaction(function () use ($request, $prescricao, $parcela, $dataPagamento, $formas, $totalFormas) {
            $pagamento = PrescricaoPagamento::create([
                'prescricao_id' => $prescricao->id,
                'dt_pagamento' => $dataPagamento->format('Y-m-d'),
                'vl_total' => $totalFormas,
                'obs' => $request->obs ?: null,
                'user_id' => auth()->id(),
            ]);

            foreach ($formas as $f) {
                PrescricaoPagamentoForma::create([
                    'pagamento_id' => $pagamento->id,
                    'forma_pagamento' => $f['forma'],
                    'vl_pagamento' => $f['vl'],
                    'parcelas' => $f['parcelas'],
                    'id_transacao' => $f['id_transacao'],
                ]);
            }

            PagamentoParcela::create([
                'pagamento_id' => $pagamento->id,
                'financeiro_parcela_id' => $parcela->id,
                'valor' => $totalFormas,
            ]);

            $novoPago = (float) $parcela->valor_pago + $totalFormas;
            $situacao = $novoPago >= (float) $parcela->valor_parcela - 0.009 ? 'Paga' : 'Parcial';
            $parcela->update(['valor_pago' => $novoPago, 'situacao' => $situacao]);

            $this->atualizarSituacaoFinanceira($prescricao);

            // Anexos comprovante (múltiplos)
            foreach ((array) $request->file('anexos_comprovante', []) as $arquivo) {
                $caminho = $arquivo->store('anexos/comprovantes/'.$prescricao->id, 'public');

                Anexo::create([
                    'tipo' => 'comprovante_pagamento',
                    'prescricao_id' => $prescricao->id,
                    'pagamento_id' => $pagamento->id,
                    'user_id' => auth()->id(),
                    'nm_anexo' => $arquivo->getClientOriginalName(),
                    'arquivo' => $caminho,
                    'mime' => $arquivo->getMimeType(),
                    'extensao' => $arquivo->getClientOriginalExtension(),
                ]);
            }

            // Demonstrativo de pagamento (não vai para o imprimir do cadastro)
            $this->salvarDemonstrativoPagamento($request, $prescricao->id, $pagamento->id);

            PrescricaoLog::create([
                'prescricao_id' => $prescricao->id,
                'entidade' => 'pagamento',
                'entidade_id' => $pagamento->id,
                'user_id' => auth()->id(),
                'acao' => 'pago',
                'descricao' => 'Pagamento individual da Parcela '.$parcela->nr_parcela.' de R$ '.valorDbForm($totalFormas).' ('.count($formas).' forma(s)).',
            ]);
        });

        return back()->with('mensagem', 'Pagamento da Parcela '.$parcela->nr_parcela.' lançado com sucesso.');
    }

    /**
     * Edição manual da parcela (valor, vencimento e obs), recalculando a
     * situação da parcela e da prescrição.
     */
    public function atualizarParcela(Request $request, $id, $parcelaId)
    {
        $request->validate([
            'valor_parcela' => 'required|string',
            'dt_vencimento' => 'nullable|date_format:d/m/Y',
            'obs' => 'nullable|string|max:1000',
        ]);

        $prescricao = Prescricao::findOrFail($id);
        $parcela = FinanceiroParcela::where('id', $parcelaId)->where('prescricao_id', $prescricao->id)->firstOrFail();

        $novoValor = (float) valorFormDb($request->valor_parcela);
        if ($novoValor < 0) {
            return back()->with('mensagem_erro', 'Valor da parcela inválido.');
        }

        $parcela->valor_parcela = round($novoValor, 2);
        if ($request->filled('dt_vencimento')) {
            $parcela->dt_vencimento = Carbon::createFromFormat('d/m/Y', $request->dt_vencimento)->format('Y-m-d');
        }
        $parcela->obs = $request->obs ?: null;
        $parcela->save();

        // o valor_tratamento da prescrição acompanha a soma das parcelas
        $novoTotal = round((float) $prescricao->financeiroParcelas()->sum('valor_parcela'), 2);
        $prescricao->valor_tratamento = $novoTotal;
        $prescricao->save();

        $this->atualizarSituacaoParcela($parcela);
        $this->atualizarSituacaoFinanceira($prescricao);

        PrescricaoLog::create([
            'prescricao_id' => $prescricao->id,
            'entidade' => 'financeiro_parcela',
            'entidade_id' => $parcela->id,
            'user_id' => auth()->id(),
            'acao' => 'parcela_atualizada',
            'descricao' => 'Parcela '.$parcela->nr_parcela.' atualizada manualmente (valor R$ '.valorDbForm($parcela->valor_parcela).'); valor_tratamento recalculado para R$ '.valorDbForm($novoTotal).'.',
        ]);

        return back()->with('mensagem', 'Parcela '.$parcela->nr_parcela.' atualizada.');
    }

    /**
     * Atualiza o "Crédito em Aberto" da prescrição (valor que o paciente
     * ainda tem de crédito pago para usar em próximos protocolos).
     */
    public function atualizarCreditoEmAberto(Request $request, $id)
    {
        $request->validate([
            'credito_em_aberto' => 'required|string',
        ]);

        $prescricao = Prescricao::findOrFail($id);
        $novoValor = round((float) valorFormDb($request->credito_em_aberto), 2);
        if ($novoValor < 0) {
            return back()->with('mensagem_erro', 'Crédito em aberto inválido.');
        }

        $prescricao->credito_em_aberto = $novoValor;
        $prescricao->save();

        PrescricaoLog::create([
            'prescricao_id' => $prescricao->id,
            'entidade' => 'prescricao',
            'entidade_id' => $prescricao->id,
            'user_id' => auth()->id(),
            'acao' => 'credito_em_aberto_atualizado',
            'descricao' => 'Crédito em aberto atualizado para R$ '.valorDbForm($novoValor).'.',
        ]);

        return back()->with('mensagem', 'Crédito em aberto atualizado para R$ '.valorDbForm($novoValor).'.');
    }

    /**
     * Salva o anexo "demonstrativo de pagamento" do lançamento (R8).
     * Fica apenas no financeiro do paciente e não vai para o imprimir.
     */
    private function salvarDemonstrativoPagamento(Request $request, int $prescricaoId, int $pagamentoId): void
    {
        if (! $request->hasFile('demonstrativo_pagamento')) {
            return;
        }
        $arquivo = $request->file('demonstrativo_pagamento');
        $caminho = $arquivo->store('anexos/demonstrativos/'.$prescricaoId, 'public');

        Anexo::create([
            'tipo' => 'demonstrativo_pagamento',
            'prescricao_id' => $prescricaoId,
            'pagamento_id' => $pagamentoId,
            'user_id' => auth()->id(),
            'nm_anexo' => $arquivo->getClientOriginalName(),
            'arquivo' => $caminho,
            'mime' => $arquivo->getMimeType(),
            'extensao' => $arquivo->getClientOriginalExtension(),
        ]);
    }

    /**
     * Pagamento Extra: lança um pagamento sobre o saldo devedor e aplica
     * de duas formas (modo_extra):
     *  - "proxima": paga a próxima parcela em aberto e recalcula (divide
     *    igualmente) o saldo devedor nas parcelas restantes;
     *  - "ordem": paga parcela por parcela em ordem crescente.
     * O valor não pode ultrapassar o saldo devedor total.
     */
    public function pagamentoExtra(Request $request, $id)
    {
        $prescricao = Prescricao::findOrFail($id);

        $request->validate([
            'dt_pagamento' => 'required|date_format:d/m/Y',
            'forma_pagamento' => 'required|array|min:1',
            'forma_pagamento.*' => 'required|string',
            'vl_pagamento' => 'required|array|min:1',
            'vl_pagamento.*' => 'required|string',
            'modo_extra' => 'required|in:proxima,ordem',
            'obs' => 'nullable|string|max:5000',
        ]);

        $dataPagamento = Carbon::createFromFormat('d/m/Y', $request->dt_pagamento);
        $modo = $request->modo_extra;

        $formas = [];
        $totalFormas = 0;
        foreach ($request->forma_pagamento as $i => $forma) {
            $vl = $this->valorPagamentoForm($request->vl_pagamento[$i] ?? '0');
            if ($vl <= 0) {
                continue;
            }
            $formas[] = [
                'forma' => $forma,
                'vl' => $vl,
                'parcelas' => $forma === 'Cartão de Crédito' ? (int) ($request->forma_parcelas[$i] ?? 1) : 1,
                'id_transacao' => $request->forma_id_transacao[$i] ?? null,
            ];
            $totalFormas += $vl;
        }

        if (! $formas) {
            return back()->withInput()->withErrors(['vl_pagamento' => 'Informe pelo menos um valor de pagamento.']);
        }

        $abertas = $prescricao->financeiroParcelas()
            ->whereIn('situacao', ['Em Aberto', 'Parcial'])
            ->orderBy('nr_parcela')
            ->get();

        $saldoTotal = $abertas->sum(fn ($p) => max(0, (float) $p->valor_parcela - (float) $p->valor_pago));

        if ($saldoTotal <= 0) {
            return back()->with('mensagem_erro', 'Não há parcelas em aberto para receber o pagamento extra.');
        }
        if ($totalFormas > $saldoTotal + 0.009) {
            return back()->with('mensagem_erro', 'O valor do pagamento extra (R$ '.valorDbForm($totalFormas).') não pode ser maior que o saldo devedor (R$ '.valorDbForm($saldoTotal).').');
        }

        DB::transaction(function () use ($request, $prescricao, $dataPagamento, $modo, $formas, $totalFormas, $abertas) {
            $pagamento = PrescricaoPagamento::create([
                'prescricao_id' => $prescricao->id,
                'dt_pagamento' => $dataPagamento->format('Y-m-d'),
                'vl_total' => $totalFormas,
                'obs' => $request->obs ?: null,
                'user_id' => auth()->id(),
            ]);

            foreach ($formas as $f) {
                PrescricaoPagamentoForma::create([
                    'pagamento_id' => $pagamento->id,
                    'forma_pagamento' => $f['forma'],
                    'vl_pagamento' => $f['vl'],
                    'parcelas' => $f['parcelas'],
                    'id_transacao' => $f['id_transacao'],
                ]);
            }

            if ($modo === 'ordem') {
                $restante = $totalFormas;
                foreach ($abertas as $parcela) {
                    if ($restante <= 0.001) {
                        break;
                    }
                    $saldo = max(0, (float) $parcela->valor_parcela - (float) $parcela->valor_pago);
                    $aplicar = min($restante, $saldo);
                    if ($aplicar <= 0) {
                        continue;
                    }
                    PagamentoParcela::create([
                        'pagamento_id' => $pagamento->id,
                        'financeiro_parcela_id' => $parcela->id,
                        'valor' => $aplicar,
                    ]);
                    $novoPago = (float) $parcela->valor_pago + $aplicar;
                    $situacao = $novoPago >= (float) $parcela->valor_parcela - 0.009 ? 'Paga' : 'Parcial';
                    $parcela->update(['valor_pago' => $novoPago, 'situacao' => $situacao]);
                    $restante -= $aplicar;
                }
            } else {
                // próxima parcela em aberto: ela assume o valor total do pagamento e fica paga.
                // Se o pagamento for menor que o valor da parcela, paga parcialmente mantendo o valor.
                $primeira = $abertas->first();
                $valorOriginalPrimeira = (float) $primeira->valor_parcela;

                if ($totalFormas >= $valorOriginalPrimeira) {
                    PagamentoParcela::create([
                        'pagamento_id' => $pagamento->id,
                        'financeiro_parcela_id' => $primeira->id,
                        'valor' => $totalFormas,
                    ]);
                    $primeira->update([
                        'valor_parcela' => $totalFormas,
                        'valor_pago' => $totalFormas,
                        'situacao' => 'Paga',
                    ]);

                    $sobra = $totalFormas - $valorOriginalPrimeira;
                    $restantes = $abertas->slice(1)->values();
                    if ($sobra > 0.001 && $restantes->isNotEmpty()) {
                        $totalRestantesAtual = $restantes->sum(fn ($p) => (float) $p->valor_parcela);
                        $novoTotalRestantes = max(0, $totalRestantesAtual - $sobra);
                        $n = $restantes->count();
                        $soma = 0;
                        foreach ($restantes as $i => $parcela) {
                            $novoValor = $i === $n - 1
                                ? max(0, round($novoTotalRestantes - $soma, 2))
                                : max(0, round($novoTotalRestantes / $n, 2));
                            if ($i < $n - 1) {
                                $soma += $novoValor;
                            }
                            $novoPago = (float) $parcela->valor_pago;
                            $situacao = $novoPago >= $novoValor - 0.009 ? 'Paga' : ($novoPago > 0 ? 'Parcial' : 'Em Aberto');
                            $parcela->update(['valor_parcela' => $novoValor, 'situacao' => $situacao]);
                        }
                        // total de parcelas permanece o mesmo (valor_tratamento não muda)
                    }
                } else {
                    PagamentoParcela::create([
                        'pagamento_id' => $pagamento->id,
                        'financeiro_parcela_id' => $primeira->id,
                        'valor' => $totalFormas,
                    ]);
                    $novoPago = (float) $primeira->valor_pago + $totalFormas;
                    $situacao = $novoPago >= (float) $primeira->valor_parcela - 0.009 ? 'Paga' : 'Parcial';
                    $primeira->update(['valor_pago' => $novoPago, 'situacao' => $situacao]);
                }
            }

            $this->atualizarSituacaoFinanceira($prescricao);

            // Anexos comprovante (múltiplos)
            foreach ((array) $request->file('anexos_comprovante', []) as $arquivo) {
                $caminho = $arquivo->store('anexos/comprovantes/'.$prescricao->id, 'public');

                Anexo::create([
                    'tipo' => 'comprovante_pagamento',
                    'prescricao_id' => $prescricao->id,
                    'pagamento_id' => $pagamento->id,
                    'user_id' => auth()->id(),
                    'nm_anexo' => $arquivo->getClientOriginalName(),
                    'arquivo' => $caminho,
                    'mime' => $arquivo->getMimeType(),
                    'extensao' => $arquivo->getClientOriginalExtension(),
                ]);
            }

            // Demonstrativo de pagamento (não vai para o imprimir do cadastro)
            $this->salvarDemonstrativoPagamento($request, $prescricao->id, $pagamento->id);

            PrescricaoLog::create([
                'prescricao_id' => $prescricao->id,
                'entidade' => 'pagamento',
                'entidade_id' => $pagamento->id,
                'user_id' => auth()->id(),
                'acao' => 'pago',
                'descricao' => 'Pagamento extra de R$ '.valorDbForm($totalFormas).' ('.($modo === 'ordem' ? 'parcela por parcela em ordem' : 'próxima parcela + recálculo do saldo devedor').').',
            ]);
        });

        return back()->with('mensagem', 'Pagamento extra lançado com sucesso.');
    }

    /**
     * Exclui um pagamento e reverte a distribuição nas parcelas.
     */
    public function excluirPagamento($id)
    {
        $pagamento = PrescricaoPagamento::with('parcelasPagas.financeiroParcela')->findOrFail($id);
        $prescricaoId = $pagamento->prescricao_id;

        DB::transaction(function () use ($pagamento) {
            foreach ($pagamento->parcelasPagas as $pp) {
                $parcela = $pp->financeiroParcela;
                if ($parcela) {
                    $novoPago = max(0, (float) $parcela->valor_pago - (float) $pp->valor);
                    $situacao = $novoPago >= (float) $parcela->valor_parcela - 0.009 ? 'Paga' : ($novoPago > 0 ? 'Parcial' : 'Em Aberto');
                    $parcela->update(['valor_pago' => $novoPago, 'situacao' => $situacao]);
                }
            }
            $pagamento->formas()->delete();
            $pagamento->parcelasPagas()->delete();
            $pagamento->anexos()->delete();
            $pagamento->delete();
        });

        $prescricao = Prescricao::find($prescricaoId);
        if ($prescricao) {
            $this->atualizarSituacaoFinanceira($prescricao);

            PrescricaoLog::create([
                'prescricao_id' => $prescricao->id,
                'entidade' => 'pagamento',
                'entidade_id' => $id,
                'user_id' => auth()->id(),
                'acao' => 'excluido',
                'descricao' => 'Pagamento excluído (R$ '.valorDbForm($pagamento->vl_total).').',
            ]);
        }

        return back()->with('mensagem', 'Pagamento excluído.');
    }

    /**
     * Reajuste / Redivisão: recalcula as parcelas EM ABERTO.
     * Pagas nunca mudam. Grava log (o que era, o que ficou, motivo).
     */
    public function recalcularParcelas(Request $request, $id)
    {
        $prescricao = Prescricao::findOrFail($id);

        $request->validate([
            'novo_valor' => 'required|string',
            'motivo' => 'required|string|max:2000',
        ]);

        $novoTotal = (float) valorFormDb($request->novo_valor);

        DB::transaction(function () use ($prescricao, $novoTotal, $request) {
            $abertas = $prescricao->financeiroParcelas()
                ->whereIn('situacao', ['Em Aberto', 'Parcial'])
                ->orderBy('nr_parcela')
                ->get();

            $pago = (float) $prescricao->financeiroParcelas()->sum('valor_pago');
            $saldo = max(0, $novoTotal - $pago);
            $n = $abertas->count();

            $antes = $abertas->map(fn ($p) => ['nr' => $p->nr_parcela, 'valor' => (float) $p->valor_parcela])->all();

            $nr = 0;
            foreach ($abertas as $parcela) {
                $nr++;
                // arredondamento: a última parcela fica com o resto
                if ($nr === $n) {
                    $soma = 0;
                    foreach ($abertas->take($n - 1) as $p) {
                        $soma += (float) $p->valor_parcela;
                    }
                    $novoValor = round($saldo - $soma, 2);
                } else {
                    $novoValor = round($saldo / $n, 2);
                }
                $parcela->update(['valor_parcela' => $novoValor]);
            }

            $prescricao->update(['valor_tratamento' => $novoTotal]);

            PrescricaoLog::create([
                'prescricao_id' => $prescricao->id,
                'entidade' => 'reajuste',
                'entidade_id' => $prescricao->id,
                'user_id' => auth()->id(),
                'acao' => 'reajuste',
                'descricao' => $request->motivo,
                'dados_antigos' => ['parcelas_abertas' => $antes, 'valor_tratamento' => (float) $prescricao->getOriginal('valor_tratamento')],
                'dados_novos' => ['parcelas_abertas' => $abertas->map(fn ($p) => ['nr' => $p->nr_parcela, 'valor' => (float) $p->valor_parcela])->all(), 'valor_tratamento' => $novoTotal],
            ]);
        });

        return back()->with('mensagem', 'Parcelas recalculadas com sucesso.');
    }

    // =====================================================================
    // ANEXOS
    // =====================================================================

    public function downloadAnexo($id)
    {
        $anexo = Anexo::findOrFail($id);

        return Storage::disk('public')->download($anexo->arquivo, $anexo->nm_anexo);
    }

    public function visualizarAnexo($id)
    {
        $anexo = Anexo::findOrFail($id);

        if (! $anexo->visualizado_em) {
            $anexo->update(['visualizado_em' => now(), 'visualizado_por' => auth()->id()]);

            PrescricaoLog::create([
                'prescricao_id' => $anexo->prescricao_id,
                'entidade' => 'anexo',
                'entidade_id' => $anexo->id,
                'user_id' => auth()->id(),
                'acao' => 'visualizado',
                'descricao' => 'Anexo "'.$anexo->nm_anexo.'" visualizado.',
            ]);
        }

        return Storage::disk('public')->response($anexo->arquivo);
    }

    // =====================================================================
    // UTILITÁRIOS
    // =====================================================================

    /**
     * Define se um item (medicamento/combo/soro) gera aplicação,
     * derivado de medicamento.aplicacao == 'Sim'.
     */
    private function itemGeraAplicacao(string $tipo, int $id): bool
    {
        if ($tipo === 'medicamento') {
            $med = Medicamento::find($id);

            return $med && strtolower(trim((string) $med->aplicacao)) === 'sim';
        }

        if ($tipo === 'combo') {
            return Combo::whereHas('medicamentos.medicamento', fn ($q) => $q->whereRaw("LOWER(TRIM(aplicacao)) = 'sim'"))
                ->where('id', $id)->exists();
        }

        if ($tipo === 'soro') {
            return Soro::whereHas('medicamentos.medicamento', fn ($q) => $q->whereRaw("LOWER(TRIM(aplicacao)) = 'sim'"))
                ->where('id', $id)->exists();
        }

        return false;
    }

    /**
     * Indica se o item exige anexo da prescrição: aplicação "Sim" de
     * medicamento que NÃO é do tipo Procedimento (ex.: implante/procedimento
     * não precisa de anexo).
     */
    private function itemRequerAnexo(string $tipo, int $id): bool
    {
        if ($tipo === 'medicamento') {
            $med = Medicamento::find($id);

            return $med
                && strtolower(trim((string) $med->aplicacao)) === 'sim'
                && $med->tipo !== 'Procedimento';
        }

        if ($tipo === 'combo') {
            return Combo::whereHas('medicamentos.medicamento', fn ($q) => $q
                ->whereRaw("LOWER(TRIM(aplicacao)) = 'sim'")
                ->where('tipo', '<>', 'Procedimento'))
                ->where('id', $id)->exists();
        }

        if ($tipo === 'soro') {
            return Soro::whereHas('medicamentos.medicamento', fn ($q) => $q
                ->whereRaw("LOWER(TRIM(aplicacao)) = 'sim'")
                ->where('tipo', '<>', 'Procedimento'))
                ->where('id', $id)->exists();
        }

        return false;
    }

    /**
     * Converte um valor de forma de pagamento (pode vir mascarado como
     * "R$ 1.234,56") para float. Remove caracteres não numéricos (mantém
     * . e ,) antes do valorFormDb.
     */
    private function valorPagamentoForm(string $valor): float
    {
        $limpo = preg_replace('/[^0-9.,]/', '', $valor);

        return (float) valorFormDb($limpo);
    }

    /**
     * Recalcula a situacao_financeira da prescrição.
     */
    private function atualizarSituacaoFinanceira(Prescricao $prescricao): void
    {
        $total = (float) $prescricao->financeiroParcelas()->sum('valor_parcela');
        $pago = (float) $prescricao->financeiroParcelas()->sum('valor_pago');

        if ($total <= 0 || $pago >= $total - 0.009) {
            $situacao = 'Pago';
        } elseif ($pago > 0) {
            $situacao = 'Parcial';
        } else {
            $situacao = 'Em Aberto';
        }

        $prescricao->update(['situacao_financeira' => $situacao]);
    }

    /**
     * Recalcula a situação de uma parcela individual (Paga / Parcial / Em Aberto)
     * com base no valor_parcela vs valor_pago. Usado quando o valor da parcela
     * muda (ex.: adicionar medicamento), para não manter 'Paga' com saldo em aberto.
     */
    private function atualizarSituacaoParcela(FinanceiroParcela $parcela): void
    {
        $saldo = (float) $parcela->valor_parcela - (float) $parcela->valor_pago;

        if ($saldo <= 0.009) {
            $situacao = 'Paga';
        } elseif ((float) $parcela->valor_pago > 0) {
            $situacao = 'Parcial';
        } else {
            $situacao = 'Em Aberto';
        }

        if ($parcela->situacao !== $situacao) {
            $parcela->situacao = $situacao;
            $parcela->save();
        }
    }
}
