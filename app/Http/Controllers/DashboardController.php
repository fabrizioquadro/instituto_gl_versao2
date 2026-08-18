<?php

namespace App\Http\Controllers;

use App\Models\Baixa;
use App\Models\Clinica;
use App\Models\Estoque;
use App\Models\EstoqueSaldo;
use App\Models\FinanceiroParcela;
use App\Models\Medicamento;
use App\Models\Prescricao;
use App\Models\PrescricaoPagamento;
use App\Models\PrescricaoSemana;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Unidade (clínica): admin vê todas (null) ou uma específica; demais, a própria.
        if ($user->isAdmin()) {
            $clinicaId = $request->filled('clinica_id') ? (int) $request->clinica_id : null;
        } else {
            $clinicaId = $user->clinica_id;
        }
        $clinicas = \App\Models\Clinica::orderBy('nome')->get();

        // Período (filtro quantitativo)
        $dtInc = $request->filled('dt_inc') ? \Carbon\Carbon::parse($request->dt_inc)->startOfDay() : null;
        $dtFn = $request->filled('dt_fn') ? \Carbon\Carbon::parse($request->dt_fn)->endOfDay() : null;

        $medicamentoFiltro = $request->filled('medicamento_id') ? (int) $request->medicamento_id : null;

        // ===== Prescrições ativas =====
        $prescricaoBase = fn () => Prescricao::query()
            ->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId))
            ->where('situacao', '<>', 'Cancelada');

        $totalPrescricoes = $prescricaoBase()->count();
        $totalReceita = (float) $prescricaoBase()->sum('valor_tratamento');
        $saldoDevedor = (float) $prescricaoBase()->sum('credito_em_aberto');

        // Total recebido (respeita o período quando informado)
        $totalPagoQuery = PrescricaoPagamento::whereHas('prescricao', function ($q) use ($clinicaId) {
            $q->when($clinicaId, fn ($q2) => $q2->where('clinica_id', $clinicaId))->where('situacao', '<>', 'Cancelada');
        });
        if ($dtInc) {
            $totalPagoQuery->where('dt_pagamento', '>=', $dtInc->toDateString());
        }
        if ($dtFn) {
            $totalPagoQuery->where('dt_pagamento', '<=', $dtFn->toDateString());
        }
        $totalPago = (float) $totalPagoQuery->sum('vl_total');

        // ===== Semanas com aplicação (operações) =====
        $semanaBase = fn () => PrescricaoSemana::query()
            ->where('tem_aplicacao', true)
            ->whereHas('prescricao', function ($q) use ($clinicaId) {
                $q->when($clinicaId, fn ($q2) => $q2->where('clinica_id', $clinicaId))->where('situacao', '<>', 'Cancelada');
            });

        $fila = $semanaBase()->where('situacao', 'Fila de Aplicação')->count();
        $atendimento = $semanaBase()->where('situacao', 'Atendimento')->count();
        $aplicadasHoje = $semanaBase()
            ->where('situacao', 'Aplicado')
            ->when($dtInc, fn ($q) => $q->where('dt_hr_finalizacao', '>=', $dtInc))
            ->when($dtFn, fn ($q) => $q->where('dt_hr_finalizacao', '<=', $dtFn))
            ->when(! $dtInc && ! $dtFn, fn ($q) => $q->whereDate('dt_hr_finalizacao', today()))
            ->count();
        $pendentes = $semanaBase()->where('situacao', 'Pendente')->count();

        // ===== Próximas aplicações (agendadas, até 7 dias) =====
        $proximas = $semanaBase()
            ->with(['prescricao.paciente', 'financeiroParcela'])
            ->where('situacao', 'Agendada')
            ->whereDate('data_prevista', '>=', today())
            ->whereDate('data_prevista', '<=', today()->addDays(7))
            ->orderBy('data_prevista')
            ->get();

        // ===== Aplicações atrasadas =====
        $atrasadas = $semanaBase()
            ->with(['prescricao.paciente', 'financeiroParcela'])
            ->where('situacao', 'Agendada')
            ->whereDate('data_prevista', '<', today())
            ->orderBy('data_prevista')
            ->get();

        // ===== Medicações próximas ao vencimento (90 dias) =====
        $vencimentos = EstoqueSaldo::with('medicamento')
            ->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId))
            ->where('saldo', '>', 0)
            ->whereNotNull('dt_vencimento')
            ->where('dt_vencimento', '<=', today()->addDays(90))
            ->orderBy('dt_vencimento')
            ->get();

        // ===== Estoque em alerta =====
        $saldos = EstoqueSaldo::with('medicamento')
            ->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId))
            ->where('saldo', '>', 0)
            ->get();

        $estoqueAlerta = [];
        foreach ($saldos->groupBy('medicamento_id') as $linhas) {
            $med = $linhas->first()->medicamento;
            if (! $med) {
                continue;
            }
            $total = (float) $linhas->sum('saldo');
            if ($med->estoque_minimo && $total < $med->estoque_minimo) {
                $estoqueAlerta[] = ['medicamento' => $med, 'total' => $total, 'nivel' => 'critico'];
            } elseif ($med->estoque_medio && $total < $med->estoque_medio) {
                $estoqueAlerta[] = ['medicamento' => $med, 'total' => $total, 'nivel' => 'atencao'];
            }
        }
        usort($estoqueAlerta, fn ($a, $b) => strcmp($a['medicamento']->nome, $b['medicamento']->nome));
        $countAlertaEstoque = count($estoqueAlerta);
        $countVencimentoProximo = $vencimentos->count();

        // ===== Procedimentos recentes =====
        $recentes = $prescricaoBase()
            ->with(['paciente'])
            ->orderByDesc('data_prescricao')
            ->limit(8)
            ->get();

        // ===== GRÁFICOS =====

        // Consumo mensal de estoque (Saídas)
        $consumoQuery = Estoque::where('tipo', 'Saida')
            ->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId));

        if ($medicamentoFiltro) {
            $consumoQuery->where('medicamento_id', $medicamentoFiltro);
        }
        if ($dtInc) {
            $consumoQuery->where('created_at', '>=', $dtInc);
        } else {
            $consumoQuery->where('created_at', '>=', now()->subMonths(5)->startOfMonth());
        }
        if ($dtFn) {
            $consumoQuery->where('created_at', '<=', $dtFn);
        }

        $consumoRaw = (clone $consumoQuery)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, SUM(quantidade) as total")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        // Rótulos de mês (período informado ou últimos 6 meses)
        $inicioMes = $dtInc ? $dtInc->copy()->startOfMonth() : now()->subMonths(5)->startOfMonth();
        $fimMes = ($dtFn ? $dtFn->copy()->endOfMonth() : now())->startOfMonth();
        $consumoMeses = [];
        $consumoValores = [];
        $receitaValores = [];
        $cursor = $inicioMes->copy();
        $i = 0;
        while ($cursor->lte($fimMes) && $i < 12) {
            $chave = $cursor->format('Y-m');
            $consumoMeses[] = ucfirst($cursor->locale('pt_BR')->translatedFormat('M/y'));
            $consumoValores[] = round((float) ($consumoRaw[$chave]->total ?? 0), 2);
            $receitaValores[] = round((float) ($receitaRaw[$chave]->total ?? 0), 2);
            $cursor->addMonth();
            $i++;
        }

        // Top medicamentos consumidos (30 dias ou período)
        $topQuery = Estoque::with('medicamento')
            ->where('tipo', 'Saida')
            ->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId));
        if ($dtInc) {
            $topQuery->where('created_at', '>=', $dtInc);
        } else {
            $topQuery->where('created_at', '>=', now()->subDays(30));
        }
        if ($dtFn) {
            $topQuery->where('created_at', '<=', $dtFn);
        }

        $topConsumo = $topQuery->get()
            ->groupBy('medicamento_id')
            ->map(function ($linhas) {
                return [
                    'nome' => $linhas->first()->medicamento?->nome ?? 'Desconhecido',
                    'total' => round((float) $linhas->sum('quantidade'), 2),
                ];
            })
            ->sortByDesc('total')
            ->take(8)
            ->values()
            ->all();

        // Aplicações por dia (30 dias ou período)
        $apDiaQuery = PrescricaoSemana::where('tem_aplicacao', true)
            ->where('situacao', 'Aplicado')
            ->whereHas('prescricao', function ($q) use ($clinicaId) {
                $q->when($clinicaId, fn ($q2) => $q2->where('clinica_id', $clinicaId))->where('situacao', '<>', 'Cancelada');
            });
        if ($dtInc) {
            $apDiaQuery->where('dt_hr_finalizacao', '>=', $dtInc);
        } else {
            $apDiaQuery->where('dt_hr_finalizacao', '>=', now()->subDays(29)->startOfDay());
        }
        if ($dtFn) {
            $apDiaQuery->where('dt_hr_finalizacao', '<=', $dtFn);
        }

        $aplicacoesDia = (clone $apDiaQuery)
            ->selectRaw('DATE(dt_hr_finalizacao) as dia, COUNT(*) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->keyBy('dia');

        $inicioDia = $dtInc ? $dtInc->copy() : now()->subDays(29)->startOfDay();
        $fimDia = $dtFn ? $dtFn->copy() : now();
        $aplicacaoDias = [];
        $aplicacaoValores = [];
        $cursorDia = $inicioDia->copy();
        $d = 0;
        while ($cursorDia->lte($fimDia) && $d < 61) {
            $aplicacaoDias[] = $cursorDia->format('d/m');
            $aplicacaoValores[] = (int) ($aplicacoesDia[$cursorDia->format('Y-m-d')]->total ?? 0);
            $cursorDia->addDay();
            $d++;
        }

        // Receita recebida por mês
        $receitaRaw = PrescricaoPagamento::whereHas('prescricao', function ($q) use ($clinicaId) {
            $q->when($clinicaId, fn ($q2) => $q2->where('clinica_id', $clinicaId))->where('situacao', '<>', 'Cancelada');
        });
        if ($dtInc) {
            $receitaRaw->where('dt_pagamento', '>=', $dtInc->toDateString());
        } else {
            $receitaRaw->where('dt_pagamento', '>=', now()->subMonths(5)->startOfMonth());
        }
        if ($dtFn) {
            $receitaRaw->where('dt_pagamento', '<=', $dtFn->toDateString());
        }
        $receitaRaw = $receitaRaw->selectRaw("DATE_FORMAT(dt_pagamento, '%Y-%m') as mes, SUM(vl_total) as total")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        // Recalcula receitaValores já que receitaRaw agora é definido depois do loop de meses
        $consumoMeses = [];
        $consumoValores = [];
        $receitaValores = [];
        $cursor = $inicioMes->copy();
        $i = 0;
        while ($cursor->lte($fimMes) && $i < 12) {
            $chave = $cursor->format('Y-m');
            $consumoMeses[] = ucfirst($cursor->locale('pt_BR')->translatedFormat('M/y'));
            $consumoValores[] = round((float) ($consumoRaw[$chave]->total ?? 0), 2);
            $receitaValores[] = round((float) ($receitaRaw[$chave]->total ?? 0), 2);
            $cursor->addMonth();
            $i++;
        }

        // Donut: procedimentos por situação financeira
        $finSituacao = Prescricao::query()
            ->when($clinicaId, fn ($q) => $q->where('clinica_id', $clinicaId))
            ->where('situacao', '<>', 'Cancelada')
            ->get(['situacao_financeira'])
            ->groupBy('situacao_financeira')
            ->map(fn ($g) => $g->count());
        $donutLabels = ['Pago', 'Parcial', 'Em Aberto'];
        $donutValues = [
            (int) ($finSituacao->get('Pago', 0)),
            (int) ($finSituacao->get('Parcial', 0)),
            (int) ($finSituacao->get('Em Aberto', 0)),
        ];

        // Lista de medicamentos para o filtro do gráfico de consumo
        $medicamentos = Medicamento::orderBy('nome')->get(['id', 'nome']);

        // ===== Baixas por motivo — SOMENTE Núcleo I Dr Gustavo (identificado pela unidade Feegow) =====
        $nucleoFeegowId = (int) config('instituto.nucleo_i_feegow_id', 2);
        $nucleoClinica = Clinica::where('id_unidade_feegow', $nucleoFeegowId)->first();

        $motivoFiltro = $request->filled('motivo_baixa') ? $request->motivo_baixa : null;
        $baixaQuery = Baixa::with('movimentos');
        if ($nucleoClinica) {
            $baixaQuery->where('clinica_id', $nucleoClinica->id);
        }
        if ($dtInc) {
            $baixaQuery->where('data', '>=', $dtInc->toDateString());
        }
        if ($dtFn) {
            $baixaQuery->where('data', '<=', $dtFn->toDateString());
        }
        if ($motivoFiltro) {
            $baixaQuery->where('motivo', 'like', '%'.$motivoFiltro.'%');
        }
        $baixasPorMotivo = [];
        foreach ($baixaQuery->get() as $b) {
            $motivo = $b->motivo ?: 'Sem motivo';
            $qtd = (float) $b->movimentos->sum('quantidade');
            $baixasPorMotivo[$motivo]['qtd'] = round(($baixasPorMotivo[$motivo]['qtd'] ?? 0) + $qtd, 2);
            $baixasPorMotivo[$motivo]['count'] = ($baixasPorMotivo[$motivo]['count'] ?? 0) + 1;
        }
        uasort($baixasPorMotivo, fn ($a, $b) => $b['qtd'] <=> $a['qtd']);
        $baixaMotivosLabels = array_keys($baixasPorMotivo);
        $baixaMotivosValores = array_map(fn ($v) => $v['qtd'], $baixasPorMotivo);
        $baixaMotivosCounts = array_map(fn ($v) => $v['count'], $baixasPorMotivo);
        $motivosBaixa = Baixa::whereNotNull('motivo')->where('motivo', '<>', '')
            ->when($nucleoClinica, fn ($q) => $q->where('clinica_id', $nucleoClinica->id))
            ->distinct()->orderBy('motivo')->pluck('motivo');

        return view('dashboard.index', compact(
            'user', 'clinicas', 'clinicaId', 'dtInc', 'dtFn',
            'totalPrescricoes', 'totalReceita', 'saldoDevedor', 'totalPago',
            'fila', 'atendimento', 'aplicadasHoje', 'pendentes',
            'proximas', 'atrasadas', 'vencimentos', 'estoqueAlerta',
            'countAlertaEstoque', 'countVencimentoProximo', 'recentes',
            'consumoMeses', 'consumoValores', 'topConsumo',
            'aplicacaoDias', 'aplicacaoValores', 'receitaValores',
            'donutLabels', 'donutValues', 'medicamentos', 'medicamentoFiltro',
            'nucleoClinica', 'baixaMotivosLabels', 'baixaMotivosValores', 'baixaMotivosCounts', 'motivosBaixa', 'motivoFiltro'
        ));
    }
}
