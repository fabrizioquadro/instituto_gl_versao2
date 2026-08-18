@extends('layouts.sistema')

@section('title', 'Fornecedor - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ $fornecedor->nome }}</h5>
      <div>
        <a href="{{ route('config.fornecedores.edit', $fornecedor->id) }}" class="btn btn-sm btn-primary"><i class="ri-pencil-line me-1"></i>Editar</a>
        <a href="{{ route('config.fornecedores.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Voltar</a>
      </div>
    </div>
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-4">ID</dt>
        <dd class="col-sm-8">{{ $fornecedor->id }}</dd>
        <dt class="col-sm-4">CNPJ</dt>
        <dd class="col-sm-8">{{ $fornecedor->cnpj ?: '-' }}</dd>
        <dt class="col-sm-4">E-mail</dt>
        <dd class="col-sm-8">{{ $fornecedor->email ?: '-' }}</dd>
        <dt class="col-sm-4">Telefone</dt>
        <dd class="col-sm-8">{{ $fornecedor->tel ?: '-' }}</dd>
        <dt class="col-sm-4">Celular</dt>
        <dd class="col-sm-8">{{ $fornecedor->cel ?: '-' }}</dd>
        <dt class="col-sm-4">Situação</dt>
        <dd class="col-sm-8">
          @if ($fornecedor->situacao == 'Ativo')
            <span class="badge bg-label-success">Ativo</span>
          @else
            <span class="badge bg-label-danger">Inativo</span>
          @endif
        </dd>
        <dt class="col-sm-4">Criado em</dt>
        <dd class="col-sm-8">{{ $fornecedor->created_at ? $fornecedor->created_at->format('d/m/Y H:i') : '-' }}</dd>
        @if ($fornecedor->id_versao1)
          <dt class="col-sm-4">Origem V1</dt>
          <dd class="col-sm-8"><span class="badge bg-label-info">id {{ $fornecedor->id_versao1 }}</span></dd>
        @endif
      </dl>
    </div>
  </div>
@endsection
