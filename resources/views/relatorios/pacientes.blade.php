@extends('layouts.sistema')

@section('title', 'Relatório Pacientes e Protocolos - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header"><h5 class="mb-0">Relatório de Pacientes e Protocolos</h5></div>
    <div class="card-body">
      <form method="GET" action="{{ route('relatorios.pacientes') }}" class="row gy-3 align-items-end">
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
            <a href="{{ route('relatorios.pacientes') }}" class="btn btn-outline-secondary w-100">Limpar</a>
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
              <th>Paciente</th><th>CPF</th><th>Clínica</th><th>Médico</th><th>Data</th><th>Tipo</th>
              <th>Semanas</th><th>Semana Atual</th><th>Situação</th><th>Financeiro</th><th class="text-end">Valor</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($linhas as $l)
              <tr>
                <td>{{ $l['paciente'] }}</td><td>{{ $l['cpf'] }}</td><td>{{ $l['clinica'] }}</td><td>{{ $l['medico'] }}</td>
                <td>{{ $l['data'] }}</td><td>{{ $l['tipo'] }}</td><td>{{ $l['semanas'] }}</td>
                <td><span class="badge bg-label-primary">{{ $l['semana_atual'] }}</span></td>
                <td>{{ $l['situacao'] }}</td><td>{{ $l['financeiro'] }}</td><td class="text-end">{{ $l['valor'] }}</td>
              </tr>
            @empty
              <tr><td colspan="11" class="text-center text-muted py-4">Nenhum paciente/protocolo encontrado para os filtros.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif
@endsection
