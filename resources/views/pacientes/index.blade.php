@extends('layouts.sistema')

@section('title', 'Pacientes - Instituto GL')

@section('styles')
  <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
@endsection

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Pacientes</h5>
      <div class="d-flex align-items-center gap-2">
        @if (auth()->user()->isAdmin())
          <form action="{{ route('pacientes.atualizar') }}" method="POST" onsubmit="return confirm('Atualizar pacientes da Feegow agora?');">
            @csrf
            <button type="submit" class="btn btn-outline-primary">
              <i class="ri-refresh-line me-1"></i>Atualizar da Feegow
            </button>
          </form>
        @endif
        <span class="text-muted small">Total: {{ number_format($totalPacientes, 0, ',', '.') }}</span>
      </div>
    </div>
    <div class="card-body">
      @if (session('mensagem'))
        <div class="alert alert-success">{{ session('mensagem') }}</div>
      @endif
      @if (session('mensagem_erro'))
        <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
      @endif

      @if ($ultimaAtualizacao || $ultimasSinc->isNotEmpty())
        <div class="mb-3">
          @if ($ultimaAtualizacao)
            <span class="badge bg-label-info me-2">Última sincronização Feegow: {{ dataDbForm($ultimaAtualizacao) }}</span>
          @endif
          @foreach ($ultimasSinc as $sinc)
            <span class="badge bg-label-secondary me-1">
              {{ $sinc->created_at ? $sinc->created_at->format('d/m/Y H:i') : '-' }} •
              {{ $sinc->criados }} criados / {{ $sinc->atualizados }} atualizados
            </span>
          @endforeach
        </div>
      @endif

      <div class="table-responsive text-nowrap">
        <table class="table table-hover w-100" id="table-pacientes">
          <thead>
            <tr>
              <th>Nome</th>
              <th>CPF</th>
              <th>Nascimento</th>
              <th>Telefone</th>
              <th>Id Feegow</th>
              <th style="width: 90px;">Ações</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script src="{{ asset('templates/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
  <script>
    $(function () {
      $('#table-pacientes').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('pacientes.dados') }}",
        order: [[0, 'asc']],
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
    });
  </script>
@endsection
