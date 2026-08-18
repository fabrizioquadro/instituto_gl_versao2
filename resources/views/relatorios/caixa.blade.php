@extends('layouts.sistema')

@section('title', 'Relatório de Caixa - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header"><h5 class="mb-0">Relatório de Caixa Geral</h5></div>
    <div class="card-body">
      <form method="GET" action="{{ route('relatorios.caixa') }}" class="row gy-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Clínica</label>
          <select class="form-select" name="clinica_id">
            <option value="">@if (auth()->user()->isAdmin()) Todas as clínicas @else {{ auth()->user()->clinica?->nome ?? 'Minha clínica' }} @endif</option>
            @if (auth()->user()->isAdmin())
              @foreach ($clinicas as $clinica)
                <option value="{{ $clinica->id }}" @selected($clinicaId == $clinica->id)>{{ $clinica->nome }}</option>
              @endforeach
            @endif
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Colaborador(a)</label>
          <select class="form-select" name="user_id">
            <option value="">Todos</option>
            @foreach ($usuarios as $usuario)
              <option value="{{ $usuario->id }}" @selected(request('user_id') == $usuario->id)>{{ $usuario->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Início</label>
          <input type="date" class="form-control" name="dt_inc" value="{{ request('dt_inc', date('Y-m-d')) }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">Final</label>
          <input type="date" class="form-control" name="dt_fn" value="{{ request('dt_fn', date('Y-m-d')) }}">
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i>Gerar</button>
        </div>
        @if ($filtrado)
          <div class="col-md-2">
            <a href="{{ route('relatorios.caixa') }}" class="btn btn-outline-secondary w-100">Limpar</a>
          </div>
        @endif
      </form>
    </div>
  </div>

  @if ($filtrado)
    <div class="card mt-3" id="caixa-relatorio">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Relatório de Caixa Geral</h5>
          <span class="text-muted small">
            Período: {{ request('dt_inc') }} até {{ request('dt_fn') }} |
            Colaborador(a): {{ $request->filled('user_id') ? (\App\Models\User::find((int) $request->user_id)?->nome ?? 'Todos') : 'Todos' }}
          </span>
        </div>
        <div class="d-flex gap-2">
          <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="ri-printer-line me-1"></i>Imprimir</button>
          <a href="{{ url()->current().'?'.http_build_query(array_merge(request()->query(), ['exportar' => 1])) }}" class="btn btn-sm btn-success">
            <i class="ri-file-excel-2-line me-1"></i>Exportar Excel
          </a>
        </div>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-sm table-hover">
          <thead class="table-light">
            <tr>
              <th>Data/Hora</th><th>Colaborador</th><th>Paciente</th><th class="text-end">Valor Recebido</th><th>Forma de Pagamento</th><th>Nº DOC</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($linhas as $l)
              <tr>
                <td>{{ $l['data'] }}</td><td>{{ $l['colaborador'] }}</td><td>{{ $l['paciente'] }}</td>
                <td class="text-end">R$ {{ valorDbForm($l['valor']) }}</td><td>{{ $l['forma'] }}</td><td>{{ $l['doc'] }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-4">Nenhum pagamento encontrado no período.</td></tr>
            @endforelse
          </tbody>
          @if (count($linhas))
            <tfoot class="table-light">
              <tr>
                <th colspan="3" class="text-end">TOTAL GERAL</th>
                <th class="text-end">R$ {{ valorDbForm($total) }}</th>
                <th colspan="2"></th>
              </tr>
            </tfoot>
          @endif
        </table>

        @if (count($linhas))
          <div class="row mt-5 pt-5">
            <div class="col-6 text-center">
              <hr style="width: 80%; margin: auto; border-top: 1px solid #000;">
              <p class="mb-0">Assinatura do Colaborador (Entrega)</p>
            </div>
            <div class="col-6 text-center">
              <hr style="width: 80%; margin: auto; border-top: 1px solid #000;">
              <p class="mb-0">Assinatura do Responsável (Recebimento)</p>
            </div>
          </div>
        @endif
      </div>
    </div>

    <style>
      @media print {
        .layout-menu, .layout-navbar, .footer, form, .btn, .card-header a, .card-header button { display: none !important; }
        .content-wrapper { padding: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
      }
    </style>
  @endif
@endsection
