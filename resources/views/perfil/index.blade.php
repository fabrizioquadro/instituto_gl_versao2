@extends('layouts.sistema')

@section('title', 'Perfil - Instituto GL')

@section('content')
  <div class="row">
    <div class="col-md-4">
      <div class="card mb-4">
        <div class="card-body text-center">
          <div class="avatar avatar-xl mb-3">
            @if (auth()->user()->imagem)
              <img src="{{ asset('img/usuarios/' . auth()->user()->imagem) }}" alt class="rounded-circle" />
            @else
              <span class="avatar-initial rounded-circle bg-label-primary">{{ strtoupper(substr(auth()->user()->nome, 0, 2)) }}</span>
            @endif
          </div>
          <h5 class="mb-0">{{ auth()->user()->nome }}</h5>
          <span class="badge bg-label-primary">{{ ucfirst(auth()->user()->role) }}</span>
          @if (auth()->user()->clinica)
            <p class="text-muted mt-2 mb-0">{{ auth()->user()->clinica->nome }}</p>
          @endif
        </div>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">Informações do Perfil</h5>
        </div>
        <div class="card-body">
          @if (session('sucesso'))
            <div class="alert alert-success">{{ session('sucesso') }}</div>
          @endif
          @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                  <li>{{ $erro }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form method="POST" action="{{ route('perfil.update') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
              <label for="nome" class="form-label">Nome</label>
              <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', auth()->user()->nome) }}" required />
              @error('nome')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">E-mail</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', auth()->user()->email) }}" required />
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="mb-3">
              <x-select-clinica label="Clínica (escala)" />
            </div>
            <div class="mb-3">
              <label for="imagem" class="form-label">Foto de perfil</label>
              <input type="file" class="form-control @error('imagem') is-invalid @enderror" id="imagem" name="imagem" accept="image/jpeg,image/png" />
              @error('imagem')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <div class="form-text">JPG ou PNG, máximo 2MB. A foto substitui a atual.</div>
            </div>
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
