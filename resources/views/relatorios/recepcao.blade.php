@extends('layouts.sistema')

@section('title', 'Relatório Recepção - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header"><h5 class="mb-0">Relatório Recepção — Tempo de Atendimento</h5></div>
    <div class="card-body">
      <form method="GET" action="{{ route('relatorios.recepcao') }}" class="row gy-3 align-items-end">
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
          <label class="form-label">Recepcionista</label>
          <select class="form-select" name="user_id_cadastro">
            <option value="">Todas</option>
            @foreach ($recepcionistas as $rec)
              <option value="{{ $rec->id }}" @selected(request('user_id_cadastro') == $rec->id)>{{ $rec->nome }}</option>
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
            <a href="{{ route('relatorios.recepcao') }}" class="btn btn-outline-secondary w-100">Limpar</a>
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
              <th>Paciente</th><th>Recepcionista</th><th>Clínica</th><th>Cadastro</th><th>Chegada</th><th>Atendimento</th><th class="text-end">Tempo</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($linhas as $l)
              <tr>
                <td>{{ $l['paciente'] }}</td><td>{{ $l['recepcionista'] }}</td><td>{{ $l['clinica'] }}</td>
                <td>{{ $l['cadastro'] }}</td><td>{{ $l['chegada'] }}</td><td>{{ $l['atendimento'] }}</td>
                <td class="text-end">
                  @if ($l['tempo'] !== '-')
                    <span class="badge bg-label-{{ (int) $l['tempo'] > 60 ? 'warning' : 'success' }}">{{ $l['tempo'] }}</span>
                  @else
                    -
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-4">Nenhum cadastro encontrado para os filtros.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif
@endsection
