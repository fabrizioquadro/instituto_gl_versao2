@extends('layouts.sistema')

@section('title', 'Relatório de Baixas - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header"><h5 class="mb-0">Relatório de Baixas Consolidado</h5></div>
    <div class="card-body">
      <form method="GET" action="{{ route('relatorios.baixas') }}" class="row gy-3 align-items-end">
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
          <label class="form-label">Medicamento</label>
          <select class="form-select" name="medicamento_id">
            <option value="">Todos</option>
            @foreach ($medicamentos as $med)
              <option value="{{ $med->id }}" @selected(request('medicamento_id') == $med->id)>{{ $med->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Início</label>
          <input type="date" class="form-control" name="dt_inc" value="{{ request('dt_inc') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">Final</label>
          <input type="date" class="form-control" name="dt_fn" value="{{ request('dt_fn') }}">
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i>Gerar</button>
        </div>
        @if ($filtrado)
          <div class="col-md-2">
            <a href="{{ route('relatorios.baixas') }}" class="btn btn-outline-secondary w-100">Limpar</a>
          </div>
        @endif
      </form>
    </div>
  </div>

  @if ($filtrado)
    <div class="card mt-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Resultado <span class="text-muted small">({{ count($linhas) }} registro(s))</span></h5>
        <a href="{{ url()->current().'?'.http_build_query(array_merge(request()->query(), ['exportar' => 1])) }}" class="btn btn-sm btn-success">
          <i class="ri-file-excel-2-line me-1"></i>Exportar Excel
        </a>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-sm table-hover">
          <thead class="table-light">
            <tr>
              <th>Data</th><th>Clínica</th><th>Medicamento</th><th>Lote</th><th class="text-end">Quantidade</th><th>Tipo</th><th>Motivo</th><th>Usuário</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($linhas as $l)
              <tr>
                <td>{{ $l['data'] }}</td><td>{{ $l['clinica'] }}</td><td>{{ $l['medicamento'] }}</td><td>{{ $l['lote'] }}</td>
                <td class="text-end">{{ number_format($l['quantidade'], 2, ',', '.') }}</td>
                <td>
                  @if ($l['tipo'] === 'Entrada')
                    <span class="badge bg-label-success">Entrada</span>
                  @else
                    <span class="badge bg-label-danger">Saída</span>
                  @endif
                </td>
                <td>{{ $l['motivo'] }}</td><td>{{ $l['usuario'] }}</td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma baixa encontrada para os filtros.</td></tr>
            @endforelse
          </tbody>
          @if (count($linhas))
            <tfoot class="table-light">
              <tr><th colspan="4" class="text-end">TOTAL GERAL</th><th class="text-end">{{ number_format($totalGeral, 2, ',', '.') }}</th><th colspan="3"></th></tr>
            </tfoot>
          @endif
        </table>

        @if (isset($resumo) && $resumo->isNotEmpty())
          <h6 class="fw-semibold mt-4">Consolidado por Medicamento</h6>
          <table class="table table-sm table-bordered">
            <thead class="table-light">
              <tr><th>Medicamento</th><th class="text-end">Total Baixado</th></tr>
            </thead>
            <tbody>
              @foreach ($resumo as $nome => $total)
                <tr><td>{{ $nome }}</td><td class="text-end">{{ number_format($total, 2, ',', '.') }}</td></tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>
    </div>
  @endif
@endsection
