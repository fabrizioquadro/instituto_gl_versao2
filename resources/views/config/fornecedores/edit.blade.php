@extends('layouts.sistema')

@section('title', 'Editar Fornecedor - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Editar Fornecedor: {{ $fornecedor->nome }}</h5>
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

      <form method="POST" action="{{ route('config.fornecedores.update', $fornecedor->id) }}">
        @csrf
        @method('PUT')
        <div class="row gy-4">
          <div class="col-md-6">
            <label for="nome" class="form-label">Nome</label>
            <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $fornecedor->nome) }}" required />
            @error('nome')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label for="cnpj" class="form-label">CNPJ</label>
            <input type="text" class="form-control" id="cnpj" name="cnpj" value="{{ old('cnpj', $fornecedor->cnpj) }}" />
          </div>
          <div class="col-md-6">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $fornecedor->email) }}" />
          </div>
          <div class="col-md-3">
            <label for="tel" class="form-label">Telefone</label>
            <input type="text" class="form-control" id="tel" name="tel" value="{{ old('tel', $fornecedor->tel) }}" />
          </div>
          <div class="col-md-3">
            <label for="cel" class="form-label">Celular</label>
            <input type="text" class="form-control" id="cel" name="cel" value="{{ old('cel', $fornecedor->cel) }}" />
          </div>
          <div class="col-md-6">
            <label for="situacao" class="form-label">Situação</label>
            <select class="form-select" id="situacao" name="situacao">
              <option value="Ativo" @selected(old('situacao', $fornecedor->situacao) == 'Ativo')>Ativo</option>
              <option value="Inativo" @selected(old('situacao', $fornecedor->situacao) == 'Inativo')>Inativo</option>
            </select>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary me-2">Salvar</button>
          <a href="{{ route('config.fornecedores.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
@endsection
