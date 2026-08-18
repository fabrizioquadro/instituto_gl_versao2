@extends('layouts.sistema')

@section('title', 'Baixas - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Baixas</h5>
      <a href="{{ route('estoque.baixas.create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Nova Baixa
      </a>
    </div>
    <div class="card-body">
      @if (session('mensagem'))
        <div class="alert alert-success">{{ session('mensagem') }}</div>
      @endif
      @if (session('mensagem_erro'))
        <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
      @endif

      <form method="GET" action="{{ route('estoque.baixas.index') }}" class="row g-2 mb-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label small mb-1">Medicamento</label>
          <select class="form-select form-select-sm" name="medicamento_id">
            <option value="">Todos</option>
            @foreach ($medicamentos as $med)
              <option value="{{ $med->id }}" @selected(request('medicamento_id') == $med->id)>{{ $med->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-sm btn-primary w-100"><i class="ri-filter-line me-1"></i>Filtrar</button>
        </div>
        @if (request('medicamento_id'))
          <div class="col-md-2">
            <a href="{{ route('estoque.baixas.index') }}" class="btn btn-sm btn-outline-secondary w-100">Limpar</a>
          </div>
        @endif
      </form>

      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead>
            <tr>
              <th style="width: 40px;"></th>
              <th>Data</th>
              <th>Motivo</th>
              <th>Usuário</th>
              <th class="text-end">Valor</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($baixas as $baixa)
              <tr>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="ri-more-2-line ri-lg"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('estoque.baixas.show', $baixa->id) }}"><i class="ri-eye-line me-1"></i>Visualizar</a>
                      <a class="dropdown-item" href="{{ route('estoque.baixas.espelho', $baixa->id) }}" target="_blank"><i class="ri-printer-line me-1"></i>Espelho</a>
                      @if (auth()->user()->isAdmin())
                        <form action="{{ route('estoque.baixas.destroy', $baixa->id) }}" method="POST" onsubmit="return confirm('Excluir esta baixa? Os movimentos serão estornados.');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="dropdown-item text-danger"><i class="ri-delete-bin-line me-1"></i>Excluir</button>
                        </form>
                      @endif
                    </div>
                  </div>
                </td>
                <td>{{ $baixa->created_at ? $baixa->created_at->format('d/m/Y H:i') : dataDbForm($baixa->data) }}</td>
                <td>{{ $baixa->motivo }}</td>
                <td>{{ $baixa->user ? $baixa->user->nome : '-' }}</td>
                <td class="text-end">R$ {{ valorDbForm($baixa->valor) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-4">Nenhuma baixa cadastrada.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
