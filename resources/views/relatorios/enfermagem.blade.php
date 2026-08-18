@extends('layouts.sistema')

@section('title', 'Relatório Enfermagem - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header"><h5 class="mb-0">Relatório Enfermagem</h5></div>
    <div class="card-body">
      <form method="GET" action="{{ route('relatorios.enfermagem') }}" class="row gy-3 align-items-end">
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
          <label class="form-label">Enfermeira(o)</label>
          <select class="form-select" name="user_id">
            <option value="">Todas</option>
            @foreach ($enfermeiras as $enf)
              <option value="{{ $enf->id }}" @selected(request('user_id') == $enf->id)>{{ $enf->nome }}</option>
            @endforeach
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
            <a href="{{ route('relatorios.enfermagem') }}" class="btn btn-outline-secondary w-100">Limpar</a>
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
              <th>Chegada</th><th>Atendimento</th><th>Finalização</th><th>Aplicação</th><th>Semana</th><th>Paciente</th><th>Enfermeira</th>
              <th>Clínica</th><th>Medicamento</th><th class="text-end">Qtd</th><th>Lote</th><th>C. Barras</th><th>Validade</th>
              <th>Obs</th><th>Procedimento</th><th>Pagamento</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($linhas as $l)
              <tr>
                <td>{{ $l['chegada'] }}</td><td>{{ $l['atendimento'] }}</td><td>{{ $l['finalizacao'] }}</td><td>{{ $l['aplicacao'] }}</td>
                <td><span class="badge bg-label-primary">{{ $l['semana'] }}</span></td>
                <td>{{ $l['paciente'] }}</td><td>{{ $l['enfermeira'] }}</td><td>{{ $l['clinica'] }}</td><td>{{ $l['medicamento'] }}</td>
                <td class="text-end">{{ $l['quantidade'] }}</td><td>{!! $l['lote'] !!}</td><td>{!! $l['codigo'] !!}</td><td>{!! $l['validade'] !!}</td>
                <td>{{ $l['obs'] }}</td><td>{{ $l['procedimento'] }}</td><td>{{ $l['pagamento'] }}</td>
              </tr>
            @empty
              <tr><td colspan="15" class="text-center text-muted py-4">Nenhuma aplicação encontrada para os filtros.</td></tr>
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
