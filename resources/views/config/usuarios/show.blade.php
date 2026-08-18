@extends('layouts.sistema')

@section('title', 'Usuário - Instituto GL')

@section('content')
  <div class="row">
    <div class="col-md-4">
      <div class="card mb-4">
        <div class="card-body text-center">
          <div class="avatar avatar-xl mb-3">
            @if ($user->imagem)
              <img src="{{ asset('img/usuarios/' . $user->imagem) }}" alt class="rounded-circle" />
            @else
              <span class="avatar-initial rounded-circle bg-label-primary">{{ strtoupper(substr($user->nome, 0, 2)) }}</span>
            @endif
          </div>
          <h5 class="mb-0">{{ $user->nome }}</h5>
          <span class="badge bg-label-primary">{{ ucfirst($user->role) }}</span>
          @if ($user->ativo)
            <span class="badge bg-label-success ms-1">Ativo</span>
          @else
            <span class="badge bg-label-danger ms-1">Inativo</span>
          @endif
        </div>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Dados do Usuário</h5>
          <div>
            <a href="{{ route('config.usuarios.edit', $user->id) }}" class="btn btn-sm btn-primary"><i class="ri-pencil-line me-1"></i>Editar</a>
            <a href="{{ route('config.usuarios.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Voltar</a>
          </div>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">ID</dt>
            <dd class="col-sm-8">{{ $user->id }}</dd>
            <dt class="col-sm-4">E-mail</dt>
            <dd class="col-sm-8">{{ $user->email }}</dd>
            <dt class="col-sm-4">Clínica</dt>
            <dd class="col-sm-8">{{ $user->clinica ? $user->clinica->nome : '-' }}</dd>
            <dt class="col-sm-4">Coren</dt>
            <dd class="col-sm-8">{{ $user->coren ?: '-' }}</dd>
            <dt class="col-sm-4">Criado em</dt>
            <dd class="col-sm-8">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}</dd>
            @if ($user->id_versao1)
              <dt class="col-sm-4">Origem V1</dt>
              <dd class="col-sm-8"><span class="badge bg-label-info">{{ $user->origem_versao1 }}</span> (id {{ $user->id_versao1 }})</dd>
            @endif
          </dl>

          <hr />

          <h6 class="mb-3">Acessos</h6>
          <div class="row">
            @foreach ($opcoes as $opt)
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" disabled id="{{ $opt }}" @checked($user->$opt) />
                  <label class="form-check-label" for="{{ $opt }}" style="text-transform: capitalize">{{ str_replace('_', ' ', $opt) }}</label>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
