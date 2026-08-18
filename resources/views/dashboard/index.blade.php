@extends('layouts.sistema')

@section('title', 'Dashboard - Instituto GL')

@section('content')
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h4 class="mb-0">Olá, {{ $user->nome }} 👋</h4>
      <span class="text-muted small">
        {{ now()->locale('pt_BR')->translatedFormat('l, d \d\e F \d\e Y') }}
      </span>
    </div>
    <span class="badge rounded-pill bg-label-primary fs-6">{{ $user->clinica?->nome ?? 'Sem clínica' }}</span>
  </div>

  @if (session('mensagem'))
    <div class="alert alert-success">{{ session('mensagem') }}</div>
  @endif
  @if (session('mensagem_erro'))
    <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
  @endif

  {{-- ==================== FILTROS ==================== --}}
  <div class="card mb-3">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('dashboard') }}" class="row gy-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label small mb-1">Unidade (Clínica)</label>
          <select class="form-select form-select-sm" name="clinica_id">
            @if (auth()->user()->isAdmin())
              <option value="">Todas as clínicas</option>
              @foreach ($clinicas as $clinica)
                <option value="{{ $clinica->id }}" @selected($clinicaId == $clinica->id)>{{ $clinica->nome }}</option>
              @endforeach
            @else
              <option value="">{{ auth()->user()->clinica?->nome ?? 'Minha clínica' }}</option>
            @endif
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">Início</label>
          <input type="date" class="form-control form-control-sm" name="dt_inc" value="{{ request('dt_inc') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">Final</label>
          <input type="date" class="form-control form-control-sm" name="dt_fn" value="{{ request('dt_fn') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label small mb-1">Medicamento (consumo)</label>
          <select class="form-select form-select-sm" name="medicamento_id">
            <option value="">Todos os medicamentos</option>
            @foreach ($medicamentos as $med)
              <option value="{{ $med->id }}" @selected($medicamentoFiltro == $med->id)>{{ $med->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small mb-1">Motivo da baixa</label>
          <select class="form-select form-select-sm" name="motivo_baixa">
            <option value="">Todos os motivos</option>
            @foreach ($motivosBaixa as $motivo)
              <option value="{{ $motivo }}" @selected($motivoFiltro == $motivo)>{{ $motivo }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary w-100"><i class="ri-filter-line me-1"></i>Filtrar</button>
          @if (request()->has('dt_inc') || request()->has('dt_fn') || request()->has('clinica_id') || request()->has('medicamento_id') || request()->has('motivo_baixa'))
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary" title="Limpar filtros"><i class="ri-close-line"></i></a>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- ==================== KPIs ==================== --}}
  <div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-sm flex-shrink-0 bg-label-primary"><span class="avatar-initial rounded-2"><i class="ri-file-list-3-line ri-lg"></i></span></div>
          <div>
            <h5 class="mb-0">{{ $totalPrescricoes }}</h5>
            <small class="text-muted">Procedimentos ativos</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-sm flex-shrink-0 bg-label-info"><span class="avatar-initial rounded-2"><i class="ri-time-line ri-lg"></i></span></div>
          <div>
            <h5 class="mb-0">{{ $fila }}</h5>
            <small class="text-muted">Fila de Aplicação</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-sm flex-shrink-0 bg-label-warning"><span class="avatar-initial rounded-2"><i class="ri-nurse-line ri-lg"></i></span></div>
          <div>
            <h5 class="mb-0">{{ $atendimento }}</h5>
            <small class="text-muted">Em atendimento</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-sm flex-shrink-0 bg-label-success"><span class="avatar-initial rounded-2"><i class="ri-check-double-line ri-lg"></i></span></div>
          <div>
            <h5 class="mb-0">{{ $aplicadasHoje }}</h5>
            <small class="text-muted">Aplicadas hoje</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-sm flex-shrink-0 bg-label-success"><span class="avatar-initial rounded-2"><i class="ri-money-dollar-circle-line ri-lg"></i></span></div>
          <div>
            <h5 class="mb-0">R$ {{ valorDbForm($totalReceita) }}</h5>
            <small class="text-muted">Receita (tratamentos)</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-sm flex-shrink-0 bg-label-info"><span class="avatar-initial rounded-2"><i class="ri-bank-card-line ri-lg"></i></span></div>
          <div>
            <h5 class="mb-0">R$ {{ valorDbForm($totalPago) }}</h5>
            <small class="text-muted">Total pago</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-sm flex-shrink-0 bg-label-danger"><span class="avatar-initial rounded-2"><i class="ri-wallet-3-line ri-lg"></i></span></div>
          <div>
            <h5 class="mb-0">R$ {{ valorDbForm($saldoDevedor) }}</h5>
            <small class="text-muted">Saldo devedor</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-sm flex-shrink-0 bg-label-danger"><span class="avatar-initial rounded-2"><i class="ri-alert-line ri-lg"></i></span></div>
          <div>
            <h5 class="mb-0">{{ $countAlertaEstoque }}</h5>
            <small class="text-muted">Estoque em alerta</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ==================== GRÁFICOS ==================== --}}
  <div class="row g-3 mb-3">
    <div class="col-xl-8">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Consumo de Medicamentos</h5>
          <span class="text-muted small">Saídas de estoque por mês</span>
        </div>
        <div class="card-body">
          <div id="chartConsumo"></div>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Top Medicamentos Consumidos</h5>
          <span class="text-muted small">últimos 30 dias</span>
        </div>
        <div class="card-body">
          @php $maxTop = $topConsumo[0]['total'] ?? 1; @endphp
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead class="table-light">
                <tr><th>Medicamento</th><th class="text-end">Consumo</th></tr>
              </thead>
              <tbody>
                @forelse ($topConsumo as $item)
                  <tr>
                    <td>
                      <div class="mb-1">{{ $item['nome'] }}</div>
                      <div class="progress" style="height: 6px;">
                        <div class="progress-bar" role="progressbar"
                             style="width: {{ $maxTop > 0 ? round(($item['total'] / $maxTop) * 100) : 0 }}%;"></div>
                      </div>
                    </td>
                    <td class="text-end fw-semibold">{{ number_format($item['total'], 2, ',', '.') }}</td>
                  </tr>
                @empty
                  <tr><td colspan="2" class="text-center text-muted py-4">Sem consumo nos últimos 30 dias.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-xl-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Aplicações por Dia</h5>
          <span class="text-muted small">últimos 30 dias</span>
        </div>
        <div class="card-body">
          <div id="chartAplicacoes"></div>
        </div>
      </div>
    </div>
    <div class="col-xl-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Receita Recebida por Mês</h5>
          <span class="text-muted small">últimos 6 meses</span>
        </div>
        <div class="card-body">
          <div id="chartReceita"></div>
        </div>
      </div>
    </div>
  </div>

  {{-- ==================== BAIXAS POR MOTIVO (NÚCLEO I DR GUSTAVO) ==================== --}}
  <div class="row g-3 mb-3">
    <div class="col-12">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="mb-0"><i class="ri-archive-drawer-line me-1 text-danger"></i>Baixas de Estoque por Motivo</h5>
          <span class="text-muted small"><i class="ri-hospital-line me-1"></i>{{ $nucleoClinica?->nome ?? 'Núcleo I Dr Gustavo' }} — quantidades somadas no período filtrado</span>
        </div>
        <div class="card-body">
          <div class="row g-4">
            <div class="col-lg-5">
              <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Motivo</th>
                      <th class="text-center">Qtd baixas</th>
                      <th class="text-end">Quantidade</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($baixaMotivosLabels as $i => $motivo)
                      <tr>
                        <td>{{ $motivo }}</td>
                        <td class="text-center">{{ $baixaMotivosCounts[$i] ?? 0 }}</td>
                        <td class="text-end fw-semibold">{{ number_format($baixaMotivosValores[$i] ?? 0, 2, ',', '.') }}</td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="3" class="text-center text-muted py-3">Nenhuma baixa no período selecionado.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
            <div class="col-lg-7">
              @if (count($baixaMotivosLabels) > 0)
                <div id="chartBaixas"></div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ==================== PRÓXIMAS APLICAÇÕES + ESTOQUE ==================== --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="mb-0">Próximas Aplicações</h5>
          <span class="text-muted small">próximos 7 dias</span>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Data</th>
                  <th>Paciente</th>
                  <th>Semana</th>
                  <th>Parcela</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($proximas as $semana)
                  <tr>
                    <td>{{ $semana->data_prevista?->format('d/m/Y') }}</td>
                    <td>{{ $semana->prescricao->paciente?->nm_paciente ?? '-' }}</td>
                    <td>
                      <span class="badge rounded-pill bg-label-primary">Semana {{ $semana->nr_semana }}</span>
                    </td>
                    <td>
                      @php $parcela = $semana->financeiroParcela; @endphp
                      @if ($parcela)
                        @if ((float) $parcela->valor_pago >= (float) $parcela->valor_parcela)
                          <span class="badge bg-label-success">Paga</span>
                        @else
                          <span class="badge bg-label-warning">Em aberto</span>
                        @endif
                      @else
                        <span class="text-muted small">—</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">Nenhuma aplicação agendada para os próximos 7 dias.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="mb-0">Estoque em Alerta</h5>
          <a href="{{ route('estoque.estoques.index') }}" class="btn btn-sm btn-outline-primary">Ver estoque</a>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Medicamento</th>
                  <th class="text-end">Total</th>
                  <th>Nível</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($estoqueAlerta as $item)
                  <tr>
                    <td>{{ $item['medicamento']->nome }}</td>
                    <td class="text-end fw-semibold">{{ number_format($item['total'], 2, ',', '.') }}</td>
                    <td>
                      @if ($item['nivel'] === 'critico')
                        <span class="badge bg-label-danger">Crítico</span>
                      @else
                        <span class="badge bg-label-warning">Atenção</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted py-4">Estoque dentro do nível normal.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ==================== VENCIMENTOS + ATRASADAS ==================== --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Medicações Próximas ao Vencimento</h5>
          <span class="text-muted small">90 dias ({{ $countVencimentoProximo }} item(ns))</span>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Medicação</th>
                  <th>Lote</th>
                  <th>C. Barras</th>
                  <th>Vencimento</th>
                  <th class="text-end">Saldo</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($vencimentos as $venc)
                  @php
                    $dias = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($venc->dt_vencimento)->startOfDay(), false);
                    $classe = $dias < 0 ? 'text-danger fw-bold' : ($dias <= 30 ? 'text-warning fw-bold' : '');
                    $badge = $dias < 0 ? 'bg-danger' : ($dias <= 30 ? 'bg-warning' : 'bg-info');
                  @endphp
                  <tr>
                    <td class="{{ $classe }}">{{ $venc->medicamento?->nome ?? '-' }}</td>
                    <td>{{ $venc->lote }}</td>
                    <td><code>{{ $venc->codigo_barras ?: '-' }}</code></td>
                    <td class="{{ $classe }}">{{ dataDbForm($venc->dt_vencimento) }}</td>
                    <td class="text-end">{{ number_format($venc->saldo, 2, ',', '.') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">Nenhuma medicação próxima do vencimento.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Aplicações Atrasadas</h5>
          <span class="text-muted small">agendadas com data anterior a hoje</span>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Data prevista</th>
                  <th>Paciente</th>
                  <th>Semana</th>
                  <th>Dias atraso</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($atrasadas as $semana)
                  @php $diasAtraso = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($semana->data_prevista)->startOfDay(), false); @endphp
                  <tr>
                    <td>{{ $semana->data_prevista?->format('d/m/Y') }}</td>
                    <td>{{ $semana->prescricao->paciente?->nm_paciente ?? '-' }}</td>
                    <td><span class="badge rounded-pill bg-label-warning">Semana {{ $semana->nr_semana }}</span></td>
                    <td><span class="badge bg-label-danger">{{ max(0, $diasAtraso) }} dia(s)</span></td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">Nenhuma aplicação atrasada.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ==================== PROCEDIMENTOS RECENTES ==================== --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Procedimentos Recentes</h5>
      <a href="{{ route('procedimentos.index') }}" class="btn btn-sm btn-outline-primary">Ver todos</a>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th>Paciente</th>
              <th>Médico</th>
              <th>Data</th>
              <th>Situação</th>
              <th>Financeiro</th>
              <th class="text-end">Valor</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($recentes as $p)
              <tr style="cursor: pointer" onclick="window.location='{{ route('procedimentos.show', $p->id) }}'">
                <td>{{ $p->paciente?->nm_paciente ?? '-' }}</td>
                <td>{{ $p->medico }}</td>
                <td>{{ $p->data_prescricao?->format('d/m/Y') }}</td>
                <td><span class="badge rounded-pill bg-label-secondary">{{ $p->situacao }}</span></td>
                <td>
                  @if ($p->situacao_financeira === 'Pago')
                    <span class="badge bg-label-success">Pago</span>
                  @elseif ($p->situacao_financeira === 'Parcial')
                    <span class="badge bg-label-warning">Parcial</span>
                  @else
                    <span class="badge bg-label-danger">Em Aberto</span>
                  @endif
                </td>
                <td class="text-end fw-semibold">R$ {{ valorDbForm($p->valor_tratamento) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Nenhum procedimento cadastrado.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script src="{{ asset('templates/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
  <script>
    @php
      $cMeses = $consumoMeses;
      $cValores = $consumoValores;
      $aDias = $aplicacaoDias;
      $aValores = $aplicacaoValores;
      $rValores = $receitaValores;
    @endphp
    const CH_CONSUMO_MESES = @json($cMeses);
    const CH_CONSUMO_VALORES = @json($cValores);
    const CH_APLICACAO_DIAS = @json($aDias);
    const CH_APLICACAO_VALORES = @json($aValores);
    const CH_RECEITA_VALORES = @json($rValores);

    $(function () {
      new ApexCharts(document.querySelector('#chartConsumo'), {
        chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Consumo', data: CH_CONSUMO_VALORES }],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: CH_CONSUMO_MESES },
        yaxis: { labels: { formatter: v => v } },
        colors: ['#666cff'],
        grid: { borderColor: '#e5e5e8' },
        tooltip: { y: { formatter: v => v + ' und' } }
      }).render();

      new ApexCharts(document.querySelector('#chartAplicacoes'), {
        chart: { type: 'area', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Aplicações', data: CH_APLICACAO_VALORES }],
        dataLabels: { enabled: false },
        xaxis: { categories: CH_APLICACAO_DIAS, tickAmount: 6 },
        stroke: { curve: 'smooth', width: 2 },
        colors: ['#72e128'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.05 } },
        grid: { borderColor: '#e5e5e8' }
      }).render();

      new ApexCharts(document.querySelector('#chartReceita'), {
        chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Recebido', data: CH_RECEITA_VALORES }],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: CH_CONSUMO_MESES },
        yaxis: { labels: { formatter: v => 'R$ ' + Number(v).toLocaleString('pt-BR') } },
        colors: ['#26c6f9'],
        grid: { borderColor: '#e5e5e8' },
        tooltip: { y: { formatter: v => 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) } }
      }).render();

      if (document.querySelector('#chartBaixas') && CH_BAIXAS_LABELS.length) {
        new ApexCharts(document.querySelector('#chartBaixas'), {
          chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
          series: [{ name: 'Quantidade', data: CH_BAIXAS_VALORES }],
          plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
          dataLabels: { enabled: false },
          xaxis: { categories: CH_BAIXAS_LABELS },
          yaxis: { labels: { formatter: v => Number(v).toLocaleString('pt-BR') } },
          colors: ['#ff3e1d'],
          grid: { borderColor: '#e5e5e8' },
          tooltip: { y: { formatter: v => v + ' und' } }
        }).render();
      }
    });
  </script>
@endsection
