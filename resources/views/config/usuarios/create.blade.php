@extends('layouts.sistema')

@section('title', 'Novo Usuário - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Novo Usuário</h5>
    </div>
    <div class="card-body">
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $erro)
              <li>{{ $erro }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('config.usuarios.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="row gy-4">
          <div class="col-md-6">
            <label for="nome" class="form-label">Nome</label>
            <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome') }}" required />
            @error('nome')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required />
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label for="password" class="form-label">Senha</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required />
            @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">Mínimo de 8 caracteres.</div>
          </div>
          <div class="col-md-6">
            <label for="role" class="form-label">Papel</label>
            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
              <option value="">Selecione</option>
              <option value="secretaria" @selected(old('role') == 'secretaria')>Secretária</option>
              <option value="enfermagem" @selected(old('role') == 'enfermagem')>Enfermagem</option>
              <option value="admin" @selected(old('role') == 'admin')>Administrador</option>
            </select>
            @error('role')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <x-select-clinica label="Clínica (escala)" />
          </div>
          <div class="col-md-6">
            <label for="ativo" class="form-label">Status</label>
            <select class="form-select @error('ativo') is-invalid @enderror" id="ativo" name="ativo">
              <option value="1" @selected(old('ativo', 1))>Ativo</option>
              <option value="0" @selected(old('ativo') === '0')>Inativo</option>
            </select>
            @error('ativo')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label for="coren" class="form-label">Coren</label>
            <input type="text" class="form-control" id="coren" name="coren" value="{{ old('coren') }}" />
          </div>
          <div class="col-md-6">
            <label for="senha_certificado" class="form-label">Certificado (senha)</label>
            <input type="text" class="form-control" id="senha_certificado" name="senha_certificado" value="{{ old('senha_certificado') }}" />
          </div>
          <div class="col-md-6">
            <label for="imagem" class="form-label">Foto de perfil</label>
            <input type="file" class="form-control" id="imagem" name="imagem" accept="image/jpeg,image/png" />
          </div>
          <div class="col-md-6">
            <label for="imagem_carimbo" class="form-label">Certificado digital (imagem)</label>
            <input type="file" class="form-control" id="imagem_carimbo" name="imagem_carimbo" />
          </div>
          <div class="col-md-12">
            <label class="form-label">Acessos</label>
            <div class="row">
              @foreach ($opcoes as $opt)
                <div class="col-md-3">
                  <div class="form-check form-check-primary">
                    <input class="form-check-input" type="checkbox" value="1" id="{{ $opt }}" name="{{ $opt }}" @checked(old($opt)) />
                    <label class="form-check-label" for="{{ $opt }}" style="text-transform: capitalize">{{ str_replace('_', ' ', $opt) }}</label>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary me-2">Salvar</button>
          <a href="{{ route('config.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
@endsection
