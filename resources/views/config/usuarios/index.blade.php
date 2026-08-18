@extends('layouts.sistema')

@section('title', 'Usuários - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Usuários</h5>
      <a href="{{ route('config.usuarios.create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Novo Usuário
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
              <th>Usuário</th>
              <th>E-mail</th>
              <th>Papel</th>
              <th>Clínica</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($usuarios as $user)
              <tr>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="ri-more-2-line ri-lg"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('config.usuarios.show', $user->id) }}"><i class="ri-eye-line me-1"></i>Visualizar</a>
                      <a class="dropdown-item" href="{{ route('config.usuarios.edit', $user->id) }}"><i class="ri-pencil-line me-1"></i>Editar</a>
                      <form action="{{ route('config.usuarios.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Excluir este usuário?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger"><i class="ri-delete-bin-line me-1"></i>Excluir</button>
                      </form>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm">
                      @if ($user->imagem)
                        <img src="{{ asset('img/usuarios/' . $user->imagem) }}" alt class="rounded-circle" />
                      @else
                        <span class="avatar-initial rounded-circle bg-label-primary">{{ strtoupper(substr($user->nome, 0, 2)) }}</span>
                      @endif
                    </div>
                    <div>{{ $user->nome }}</div>
                  </div>
                </td>
                <td>{{ $user->email }}</td>
                <td><span class="badge bg-label-primary">{{ ucfirst($user->role) }}</span></td>
                <td>{{ $user->clinica ? $user->clinica->nome : '-' }}</td>
                <td>
                  @if ($user->ativo)
                    <span class="badge bg-label-success">Ativo</span>
                  @else
                    <span class="badge bg-label-danger">Inativo</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Nenhum usuário cadastrado.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
