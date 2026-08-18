@extends('layouts.sistema')

@section('title', 'Entradas - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Entradas</h5>
      <a href="{{ route('estoque.entradas.create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Nova Entrada
      </a>
    </div>
    <div class="card-body">
      @if (session('mensagem'))
        <div class="alert alert-success">{{ session('mensagem') }}</div>
      @endif
      @if (session('mensagem_erro'))
        <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
      @endif

      <form method="GET" action="{{ route('estoque.entradas.index') }}" class="row g-2 mb-3 align-items-end">
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
            <a href="{{ route('estoque.entradas.index') }}" class="btn btn-sm btn-outline-secondary w-100">Limpar</a>
          </div>
        @endif
      </form>

      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead>
            <tr>
              <th style="width: 40px;"></th>
              <th>Data</th>
              <th>Fornecedor</th>
              <th>Nr. Nota</th>
              <th class="text-end">Valor</th>
              <th>Medicamentos</th>
              <th>Usuário</th>
              <th>Arquivo</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($entradas as $entrada)
              <tr>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="ri-more-2-line ri-lg"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('estoque.entradas.show', $entrada->id) }}"><i class="ri-eye-line me-1"></i>Visualizar</a>
                      <a class="dropdown-item" href="{{ route('estoque.entradas.etiquetas', $entrada->id) }}"><i class="ri-qr-code-line me-1"></i>Etiquetas</a>
                      <a class="dropdown-item" href="{{ route('estoque.entradas.edit', $entrada->id) }}"><i class="ri-pencil-line me-1"></i>Editar</a>
                      <form action="{{ route('estoque.entradas.destroy', $entrada->id) }}" method="POST" onsubmit="return confirm('Excluir esta entrada? Os movimentos serão estornados.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger"><i class="ri-delete-bin-line me-1"></i>Excluir</button>
                      </form>
                    </div>
                  </div>
                </td>
                <td>{{ $entrada->created_at ? $entrada->created_at->format('d/m/Y H:i') : dataDbForm($entrada->data) }}</td>
                <td>{{ $entrada->fornecedor ? $entrada->fornecedor->nome : '-' }}</td>
                <td>{{ $entrada->nota ?: '-' }}</td>
                <td class="text-end">R$ {{ valorDbForm($entrada->valor) }}</td>
                <td><span class="badge bg-label-primary">{{ $entrada->movimentos_count ?? $entrada->movimentos->count() }}</span></td>
                <td>{{ $entrada->user ? $entrada->user->nome : '-' }}</td>
                <td>
                  @if ($entrada->arquivo)
                    <a href="{{ asset('img/entradas/notas/'.$entrada->arquivo) }}" target="_blank" class="badge bg-label-info text-decoration-none">Abrir</a>
                  @else
                    -
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">Nenhuma entrada cadastrada.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
