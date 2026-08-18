@extends('layouts.sistema')

@section('title', 'Ajuste - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Ajuste de Estoque #{{ $ajuste->id }}</h5>
      <div>
        <a href="{{ route('estoque.ajustes.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Voltar</a>
      </div>
    </div>
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-4">Data</dt>
        <dd class="col-sm-8">{{ $ajuste->created_at ? $ajuste->created_at->format('d/m/Y H:i') : '-' }}</dd>
        <dt class="col-sm-4">Clínica</dt>
        <dd class="col-sm-8">{{ $ajuste->clinica ? $ajuste->clinica->nome : '-' }}</dd>
        <dt class="col-sm-4">Medicamento</dt>
        <dd class="col-sm-8">{{ $ajuste->medicamento ? $ajuste->medicamento->nome : 'Medicamento #'.$ajuste->medicamento_id }}</dd>
        <dt class="col-sm-4">Lote</dt>
        <dd class="col-sm-8">{{ $ajuste->lote }}</dd>
        <dt class="col-sm-4">Código de Barras</dt>
        <dd class="col-sm-8"><code>{{ $ajuste->codigo_barras ?: '-' }}</code></dd>
        <dt class="col-sm-4">Vencimento</dt>
        <dd class="col-sm-8">{{ dataDbForm($ajuste->dt_vencimento) ?: '-' }}</dd>
        <dt class="col-sm-4">Tipo</dt>
        <dd class="col-sm-8">
          @if ($ajuste->tipo == 'Entrada')
            <span class="badge bg-label-success">Entrada (acréscimo)</span>
          @else
            <span class="badge bg-label-danger">Saída (abatimento)</span>
          @endif
        </dd>
        <dt class="col-sm-4">Quantidade</dt>
        <dd class="col-sm-8">{{ number_format($ajuste->quantidade, 2, ',', '.') }}</dd>
        <dt class="col-sm-4">Motivo</dt>
        <dd class="col-sm-8">{{ $ajuste->motivo }}</dd>
        <dt class="col-sm-4">Usuário</dt>
        <dd class="col-sm-8">{{ $ajuste->user ? $ajuste->user->nome : '-' }}</dd>
      </dl>
    </div>
  </div>
@endsection
