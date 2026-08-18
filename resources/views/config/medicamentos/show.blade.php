@extends('layouts.sistema')

@section('title', 'Medicamento - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ $medicamento->nome }}</h5>
      <div>
        <a href="{{ route('config.medicamentos.edit', $medicamento->id) }}" class="btn btn-sm btn-primary"><i class="ri-pencil-line me-1"></i>Editar</a>
        <a href="{{ route('config.medicamentos.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Voltar</a>
      </div>
    </div>
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-4">ID</dt>
        <dd class="col-sm-8">{{ $medicamento->id }}</dd>
        <dt class="col-sm-4">Fabricante</dt>
        <dd class="col-sm-8">{{ $medicamento->fabricante }}</dd>
        <dt class="col-sm-4">Tipo</dt>
        <dd class="col-sm-8"><span class="badge bg-label-secondary">{{ $medicamento->tipo }}</span></dd>
        @if ($medicamento->tipo == 'Vasilhame')
          <dt class="col-sm-4">Tamanho do Vasilhame</dt>
          <dd class="col-sm-8">{{ $medicamento->vasilhame }}</dd>
        @endif
        <dt class="col-sm-4">Último Valor Pago</dt>
        <dd class="col-sm-8">R$ {{ valorDbForm($medicamento->ultimo_valor_pg) }}</dd>
        <dt class="col-sm-4">Valor de Venda</dt>
        <dd class="col-sm-8">R$ {{ valorDbForm($medicamento->vl_venda) }}</dd>
        <dt class="col-sm-4">Estoque Mínimo</dt>
        <dd class="col-sm-8"><span class="text-danger fw-semibold">{{ number_format($medicamento->estoque_minimo, 0, ',', '.') }}</span> <small class="text-muted">(alerta vermelho)</small></dd>
        <dt class="col-sm-4">Estoque Médio</dt>
        <dd class="col-sm-8"><span class="text-warning fw-semibold">{{ number_format($medicamento->estoque_medio, 0, ',', '.') }}</span> <small class="text-muted">(alerta amarelo)</small></dd>
        <dt class="col-sm-4">Situação</dt>
        <dd class="col-sm-8">
          @if ($medicamento->situacao == 'Ativo')
            <span class="badge bg-label-success">Ativo</span>
          @else
            <span class="badge bg-label-danger">Inativo</span>
          @endif
        </dd>
        <dt class="col-sm-4">Gera Aplicação</dt>
        <dd class="col-sm-8">{{ $medicamento->aplicacao }}</dd>
        <dt class="col-sm-4">Feegow Aplicação ID</dt>
        <dd class="col-sm-8">{{ $medicamento->aplicacao_feegow_id ?: '-' }}</dd>
        <dt class="col-sm-4">Grupo</dt>
        <dd class="col-sm-8">{{ $medicamento->grupo ? $medicamento->grupo->nome : '-' }}</dd>
        <dt class="col-sm-4">Criado em</dt>
        <dd class="col-sm-8">{{ $medicamento->created_at ? $medicamento->created_at->format('d/m/Y H:i') : '-' }}</dd>
        @if ($medicamento->id_versao1)
          <dt class="col-sm-4">Origem V1</dt>
          <dd class="col-sm-8"><span class="badge bg-label-info">id {{ $medicamento->id_versao1 }}</span></dd>
        @endif
      </dl>
    </div>
  </div>
@endsection
