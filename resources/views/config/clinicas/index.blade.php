@extends('layouts.sistema')

@section('title', 'Clínicas - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Clínicas</h5>
      <a href="{{ route('config.clinicas.create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Nova Clínica
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
              <th>Nome</th>
              <th>CNPJ</th>
              <th>Unidade Flegow</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($clinicas as $clinica)
              <tr>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="ri-more-2-line ri-lg"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('config.clinicas.show', $clinica->id) }}"><i class="ri-eye-line me-1"></i>Visualizar</a>
                      <a class="dropdown-item" href="{{ route('config.clinicas.edit', $clinica->id) }}"><i class="ri-pencil-line me-1"></i>Editar</a>
                      <form action="{{ route('config.clinicas.destroy', $clinica->id) }}" method="POST" onsubmit="return confirm('Excluir esta clínica?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger"><i class="ri-delete-bin-line me-1"></i>Excluir</button>
                      </form>
                    </div>
                  </div>
                </td>
                <td>{{ $clinica->nome }}</td>
                <td>{{ $clinica->cnpj ?: '-' }}</td>
                <td>{{ $clinica->id_unidade_feegow }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Nenhuma clínica cadastrada.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
