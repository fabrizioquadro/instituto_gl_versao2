@extends('layouts.sistema')

@section('title', 'Procedimentos - Instituto GL')

@section('styles')
  <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
  <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/flatpickr/flatpickr.css') }}" />
@endsection

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Procedimentos</h5>
      <a href="{{ route('procedimentos.create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Novo Procedimento
      </a>
    </div>
    <div class="card-body">
      @if (session('mensagem'))
        <div class="alert alert-success">{{ session('mensagem') }}</div>
      @endif
      @if (session('mensagem_erro'))
        <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
      @endif

      @if ($paciente)
        <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <i class="ri-user-heart-line me-1"></i>
            Procedimentos de: <strong>{{ $paciente->nm_paciente }}</strong>
            <span class="text-muted small">(CPF: {{ $paciente->cpf ?: '-' }} • Id Feegow: {{ $paciente->paciente_id_feegow ?: '-' }})</span>
          </div>
          <a href="{{ route('procedimentos.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="ri-close-line me-1"></i>Limpar filtro
          </a>
        </div>
      @endif

      {{-- Filtros --}}
      <div class="row g-2 mb-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Clínica</label>
          <select id="filtro_clinica" class="form-select">
            <option value="">Todas</option>
            @foreach ($clinicas as $clinica)
              <option value="{{ $clinica->id }}">{{ $clinica->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Situação</label>
          <select id="filtro_situacao" class="form-select">
            <option value="">Todas</option>
            <option value="Agendada">Agendada</option>
            <option value="Em Andamento">Em Andamento</option>
            <option value="Concluída">Concluída</option>
            <option value="Cancelada">Cancelada</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Data inicial</label>
          <input type="text" id="filtro_data_inicio" class="form-control flatpickr-date" placeholder="dd/mm/aaaa" />
        </div>
        <div class="col-md-2">
          <label class="form-label">Data final</label>
          <input type="text" id="filtro_data_fim" class="form-control flatpickr-date" placeholder="dd/mm/aaaa" />
        </div>
        <div class="col-md-3">
          <button type="button" id="btn_filtrar" class="btn btn-outline-primary w-100">
            <i class="ri-filter-3-line me-1"></i>Filtrar
          </button>
        </div>
      </div>

      <div class="table-responsive text-nowrap">
        <table class="table table-hover w-100" id="table-procedimentos">
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Médico</th>
              <th>Data Prescrição</th>
              <th>Nº Semanas</th>
              <th>Semana de Aplicação</th>
              <th>Valor Tratamento</th>
              <th>Situação</th>
              <th>Financeiro</th>
              <th style="width: 80px;">Ações</th>
            </tr>
          </thead>
        </table>
        <input type="hidden" id="paciente_filtro_id" value="{{ request('paciente_id') }}" />
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script src="{{ asset('templates/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
  <script src="{{ asset('templates/assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
  <script>
    $(function () {
      flatpickr('.flatpickr-date', {
        locale: {
          firstDayOfWeek: 0,
          weekdays: { shorthand: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'], longhand: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'] },
          months: { shorthand: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'], longhand: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'] }
        },
        dateFormat: 'd/m/Y',
        allowInput: true
      });

      const tabela = $('#table-procedimentos').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: "{{ route('procedimentos.dados') }}",
          data: function (d) {
            d.clinica_id = $('#filtro_clinica').val();
            d.situacao = $('#filtro_situacao').val();
            d.data_inicio = $('#filtro_data_inicio').val();
            d.data_fim = $('#filtro_data_fim').val();
            d.paciente_id = $('#paciente_filtro_id').val();
          }
        },
        order: [[2, 'desc']],
        pageLength: 25,
        language: {
          sEmptyTable: 'Nenhum registro encontrado',
          sInfo: 'Mostrando de _START_ até _END_ de _TOTAL_ registros',
          sInfoEmpty: 'Mostrando 0 até 0 de 0 registros',
          sInfoFiltered: '(filtrados de _MAX_ registros)',
          sInfoThousands: '.',
          sLengthMenu: '_MENU_ resultados por página',
          sLoadingRecords: 'Carregando...',
          sProcessing: 'Processando...',
          sZeroRecords: 'Nenhum registro encontrado',
          sSearch: 'Pesquisar:',
          oPaginate: {
            sNext: 'Próximo',
            sPrevious: 'Anterior',
            sFirst: 'Primeiro',
            sLast: 'Último'
          },
          oAria: {
            sSortAscending: ': Ordenar colunas de forma ascendente',
            sSortDescending: ': Ordenar colunas de forma descendente'
          }
        }
      });

      $('#btn_filtrar').on('click', function () {
        tabela.ajax.reload();
      });
    });
  </script>
@endsection
