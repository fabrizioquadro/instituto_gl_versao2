@extends('layouts.sistema')

@section('title', 'Clínica - Instituto GL')

@section('content')
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Clínica: {{ $clinica->nome }}</h5>
          <div>
            <a href="{{ route('config.clinicas.edit', $clinica->id) }}" class="btn btn-sm btn-primary"><i class="ri-pencil-line me-1"></i>Editar</a>
            <a href="{{ route('config.clinicas.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Voltar</a>
          </div>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">ID</dt>
            <dd class="col-sm-8">{{ $clinica->id }}</dd>
            <dt class="col-sm-4">Nome</dt>
            <dd class="col-sm-8">{{ $clinica->nome }}</dd>
            <dt class="col-sm-4">CNPJ</dt>
            <dd class="col-sm-8">{{ $clinica->cnpj ?: '-' }}</dd>
            <dt class="col-sm-4">Unidade Flegow</dt>
            <dd class="col-sm-8">{{ $clinica->id_unidade_feegow }}</dd>
            <dt class="col-sm-4">Criada em</dt>
            <dd class="col-sm-8">{{ $clinica->created_at ? $clinica->created_at->format('d/m/Y H:i') : '-' }}</dd>
          </dl>
        </div>
      </div>
    </div>
  </div>
@endsection
