@extends('layouts.sistema')

@section('title', 'Alterar Senha - Instituto GL')

@section('content')
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Alterar Senha</h5>
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

          <form method="POST" action="{{ route('alterar_senha.update') }}">
            @csrf
            <div class="mb-3">
              <label for="senha_atual" class="form-label">Senha atual</label>
              <input type="password" class="form-control @error('senha_atual') is-invalid @enderror" id="senha_atual" name="senha_atual" required />
              @error('senha_atual')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Nova senha</label>
              <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required />
              @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <div class="form-text">Mínimo de 8 caracteres.</div>
            </div>
            <div class="mb-3">
              <label for="password_confirmation" class="form-label">Confirmar nova senha</label>
              <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required />
            </div>
            <button type="submit" class="btn btn-primary">Alterar senha</button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
