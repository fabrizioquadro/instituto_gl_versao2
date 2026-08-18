@extends('layouts.sistema')

@section('title', 'Relatório Vendas - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header"><h5 class="mb-0">Relatório Vendas</h5></div>
    <div class="card-body">
      <form method="GET" action="{{ route('relatorios.vendas') }}" class="row gy-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Paciente</label>
          <select class="form-select select2-paciente" name="paciente_id" id="paciente_id">
            <option value="">Todos os pacientes</option>
            @if (request('paciente_id'))
              @php $pacSel = \App\Models\Paciente::find((int) request('paciente_id')); @endphp
              @if ($pacSel)
                <option value="{{ $pacSel->id }}" selected>{{ $pacSel->nm_paciente }}</option>
              @endif
            @endif
          </select>
        </div>
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
          <label class="form-label">Médico</label>
          <select class="form-select" name="medico">
            <option value="">Todos</option>
            @foreach ($medicos as $medico)
              <option value="{{ $medico }}" @selected(request('medico') == $medico)>{{ $medico }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Início</label>
          <input type="date" class="form-control" name="dt_inc" value="{{ request('dt_inc') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">Final</label>
          <input type="date" class="form-control" name="dt_fn" value="{{ request('dt_fn') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Situação</label>
          <select class="form-select" name="situacao">
            <option value="">Todas</option>
            <option value="Aberta" @selected(request('situacao') == 'Aberta')>Aberta</option>
            <option value="Aplicada" @selected(request('situacao') == 'Aplicada')>Aplicada</option>
            <option value="Cancelada" @selected(request('situacao') == 'Cancelada')>Cancelada</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i>Gerar</button>
        </div>
        @if ($filtrado)
          <div class="col-md-2">
            <a href="{{ route('relatorios.vendas') }}" class="btn btn-outline-secondary w-100">Limpar</a>
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
              <th>Medicamento</th><th class="text-end">Qtd</th><th>Status</th><th>Cadastro</th><th>Aplicação</th>
              <th class="text-end">Valor</th><th>Pago</th><th>Data Pagamento</th><th>Procedimento</th><th>Paciente</th><th>Médico</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($linhas as $l)
              <tr>
                <td>{{ $l['medicamento'] }}</td><td class="text-end">{{ $l['quantidade'] }}</td><td>{{ $l['situacao'] }}</td>
                <td>{{ $l['cadastro'] }}</td><td>{{ $l['aplicacao'] }}</td><td class="text-end">{{ $l['valor'] }}</td>
                <td>{{ $l['pago'] }}</td><td>{{ $l['dt_pagamento'] }}</td><td>{{ $l['procedimento'] }}</td>
                <td>{{ $l['paciente'] }}</td><td>{{ $l['medico'] }}</td>
              </tr>
            @empty
              <tr><td colspan="11" class="text-center text-muted py-4">Nenhuma venda encontrada para os filtros.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif
@endsection

@section('scripts')
  <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/select2/select2.css') }}">
  <script src="{{ asset('templates/assets/vendor/libs/select2/select2.js') }}"></script>
  <script>
    $(function () {
      $('#paciente_id').select2({
        placeholder: 'Busque o paciente...',
        allowClear: true,
        ajax: {
          url: '{{ route('pacientes.buscar') }}',
          dataType: 'json',
          delay: 250,
          data: function (params) { return { q: params.term }; },
          processResults: function (data) { return { results: data }; }
        }
      });
    });
  </script>
@endsection
