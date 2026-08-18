@extends('layouts.sistema')

@section('title', 'Relatório Financeiro - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header"><h5 class="mb-0">Relatório Financeiro</h5></div>
    <div class="card-body">
      <form method="GET" action="{{ route('relatorios.financeiro') }}" class="row gy-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Paciente</label>
          <select class="form-select select2-paciente" name="paciente_id" id="paciente_id">
            <option value="">Todos os pacientes</option>
            @if ($pacienteFiltro)
              @php $pacSelecionado = \App\Models\Paciente::find($pacienteFiltro); @endphp
              @if ($pacSelecionado)
                <option value="{{ $pacSelecionado->id }}" selected>{{ $pacSelecionado->nm_paciente }}</option>
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
          <label class="form-label">Início</label>
          <input type="date" class="form-control" name="dt_inc" value="{{ request('dt_inc') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">Final</label>
          <input type="date" class="form-control" name="dt_fn" value="{{ request('dt_fn') }}">
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i>Gerar</button>
        </div>
        @if ($filtrado)
          <div class="col-md-3">
            <a href="{{ route('relatorios.financeiro') }}" class="btn btn-outline-secondary w-100">Limpar filtros</a>
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
              <th>Data</th><th>Paciente</th><th>CPF</th><th>Código</th><th class="text-end">Valor Tratamento</th>
              <th class="text-end">Crédito em Aberto</th><th class="text-end">Pagamento</th><th class="text-end">Valor Rateio</th><th>Forma</th><th>Nº DOC</th>
              <th>Parcelas</th><th>Clínica</th><th>Médico</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($linhas as $l)
              <tr>
                <td>{{ $l['data'] }}</td><td>{{ $l['paciente'] }}</td><td>{{ $l['cpf'] }}</td><td>{{ $l['codigo'] }}</td>
                <td class="text-end">{{ $l['vl_tratamento'] }}</td><td class="text-end">{{ $l['credito'] }}</td><td class="text-end">{{ $l['pagamento'] }}</td>
                <td class="text-end">{{ $l['rateio'] }}</td><td>{{ $l['forma'] }}</td><td>{{ $l['doc'] }}</td>
                <td>{{ $l['parcelas'] }}</td><td>{{ $l['clinica'] }}</td><td>{{ $l['medico'] }}</td>
              </tr>
            @empty
              <tr><td colspan="12" class="text-center text-muted py-4">Nenhum pagamento encontrado para os filtros.</td></tr>
            @endforelse
          </tbody>
          @if (count($linhas))
            <tfoot class="table-light">
              <tr><th colspan="5" class="text-end">TOTAL</th><th class="text-end">R$ {{ valorDbForm($total) }}</th><th colspan="6"></th></tr>
            </tfoot>
          @endif
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
