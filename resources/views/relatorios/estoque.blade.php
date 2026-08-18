@extends('layouts.sistema')

@section('title', 'Relatório Estoque - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header"><h5 class="mb-0">Relatório Estoque</h5></div>
    <div class="card-body">
      <form method="GET" action="{{ route('relatorios.estoque') }}" class="row gy-3 align-items-end">
        <div class="col-md-4">
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
        <div class="col-md-4">
          <label class="form-label">Medicamento</label>
          <select class="form-select" name="medicamento_id">
            <option value="">Todos</option>
            @foreach ($medicamentos as $med)
              <option value="{{ $med->id }}" @selected(request('medicamento_id') == $med->id)>{{ $med->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i>Gerar</button>
        </div>
        @if ($filtrado)
          <div class="col-md-2">
            <a href="{{ route('relatorios.estoque') }}" class="btn btn-outline-secondary w-100">Limpar</a>
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
        <table class="table table-sm table-hover table-striped">
          <thead class="table-light">
            <tr>
              <th>Clínica</th><th>Medicamento</th><th>C. Barras</th><th>Lote</th><th>Vencimento</th><th class="text-end">Saldo Estoque</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($linhas as $l)
              @php
                $classe = '';
                if ($l['dias'] !== null) {
                  $classe = $l['dias'] < 0 ? 'text-danger fw-bold' : ($l['dias'] <= 30 ? 'text-warning fw-bold' : '');
                }
              @endphp
              <tr>
                <td>{{ $l['clinica'] }}</td><td>{{ $l['medicamento'] }}</td><td><code>{{ $l['codigo'] }}</code></td>
                <td>{{ $l['lote'] }}</td>
                <td class="{{ $classe }}">
                  {{ $l['vencimento'] }}
                  @if ($l['dias'] !== null)
                    @if ($l['dias'] < 0)
                      <span class="badge bg-danger ms-1">Vencido</span>
                    @elseif ($l['dias'] <= 30)
                      <span class="badge bg-warning ms-1">Vence em {{ $l['dias'] }} dias</span>
                    @endif
                  @endif
                </td>
                <td class="text-end fw-semibold">{{ $l['saldo'] }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-4">Nenhum estoque com saldo positivo para os filtros.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif
@endsection
