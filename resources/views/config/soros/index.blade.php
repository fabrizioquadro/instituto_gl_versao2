@extends('layouts.sistema')

@section('title', 'Soros - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Soros</h5>
      <a href="{{ route('config.soros.create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Novo Soro
      </a>
    </div>
    <div class="card-body">
      @if (session('mensagem'))
        <div class="alert alert-success">{{ session('mensagem') }}</div>
      @endif
      @if (session('mensagem_erro'))
        <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
      @endif

      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead>
            <tr>
              <th style="width: 40px;"></th>
              <th>Soro</th>
              <th>Medicamentos</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($soros as $soro)
              <tr>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="ri-more-2-line ri-lg"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('config.soros.show', $soro->id) }}"><i class="ri-eye-line me-1"></i>Visualizar</a>
                      <a class="dropdown-item" href="{{ route('config.soros.edit', $soro->id) }}"><i class="ri-pencil-line me-1"></i>Editar</a>
                      <form action="{{ route('config.soros.destroy', $soro->id) }}" method="POST" onsubmit="return confirm('Excluir este soro?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger"><i class="ri-delete-bin-line me-1"></i>Excluir</button>
                      </form>
                    </div>
                  </div>
                </td>
                <td>{{ $soro->nome }}</td>
                <td>
                  <span class="badge bg-label-primary">{{ $soro->medicamentos_count }}</span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center text-muted py-4">Nenhum soro cadastrado.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
