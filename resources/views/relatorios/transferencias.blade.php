@extends('layouts.sistema')

@section('title', 'Relatório Transferências - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header"><h5 class="mb-0">Relatório Transferências</h5></div>
    <div class="card-body">
      <form method="GET" action="{{ route('relatorios.transferencias') }}" class="row gy-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Início</label>
          <input type="date" class="form-control" name="dt_inc" value="{{ request('dt_inc') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Final</label>
          <input type="date" class="form-control" name="dt_fn" value="{{ request('dt_fn') }}">
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i>Gerar</button>
        </div>
        @if ($filtrado)
          <div class="col-md-2">
            <a href="{{ route('relatorios.transferencias') }}" class="btn btn-outline-secondary w-100">Limpar</a>
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
              <th>Data</th><th>Origem</th><th>Destino</th><th>Usuário</th><th>Medicamento</th><th>Lote</th><th>C. Barras</th><th class="text-end">Quantidade</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($linhas as $l)
              <tr>
                <td>{{ $l['data'] }}</td><td>{{ $l['origem'] }}</td><td>{{ $l['destino'] }}</td><td>{{ $l['usuario'] }}</td>
                <td>{{ $l['medicamento'] }}</td><td>{{ $l['lote'] }}</td><td><code>{{ $l['codigo'] }}</code></td><td class="text-end">{{ $l['quantidade'] }}</td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma transferência encontrada no período.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif
@endsection
