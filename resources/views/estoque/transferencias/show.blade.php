@extends('layouts.sistema')

@section('title', 'Transferência - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Transferência #{{ $transferencia->id }}</h5>
      <div class="d-flex gap-2">
        <a href="{{ route('estoque.transferencias.etiquetas', $transferencia->id) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="ri-qr-code-line me-1"></i>Etiquetas</a>
        <a href="{{ route('estoque.transferencias.espelho', $transferencia->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="ri-printer-line me-1"></i>Espelho</a>
        <a href="{{ route('estoque.transferencias.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Voltar</a>
      </div>
    </div>
    <div class="card-body">
      <dl class="row mb-4">
        <dt class="col-sm-3">Data</dt>
        <dd class="col-sm-9">{{ dataDbForm($transferencia->data) }}</dd>
        <dt class="col-sm-3">Origem</dt>
        <dd class="col-sm-9">{{ $transferencia->origem ? $transferencia->origem->nome : '-' }}</dd>
        <dt class="col-sm-3">Destino</dt>
        <dd class="col-sm-9">{{ $transferencia->destino ? $transferencia->destino->nome : '-' }}</dd>
        <dt class="col-sm-3">Motivo</dt>
        <dd class="col-sm-9">{{ $transferencia->motivo }}</dd>
        <dt class="col-sm-3">Usuário</dt>
        <dd class="col-sm-9">{{ $transferencia->user ? $transferencia->user->nome : '-' }}</dd>
        <dt class="col-sm-3">Valor Total</dt>
        <dd class="col-sm-9">R$ {{ valorDbForm($transferencia->valor) }}</dd>
        @if ($transferencia->id_versao1)
          <dt class="col-sm-3">Origem V1</dt>
          <dd class="col-sm-9"><span class="badge bg-label-info">id {{ $transferencia->id_versao1 }}</span></dd>
        @endif
      </dl>

      <h6 class="mb-3">Medicamentos transferidos</h6>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Medicamento</th>
              <th>Lote</th>
              <th>Código de Barras</th>
              <th class="text-end">Quantidade</th>
              <th class="text-end">Valor Unit.</th>
              <th class="text-end">Total</th>
              <th>Movimento</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($transferencia->movimentos as $mov)
              <tr>
                <td>{{ $mov->medicamento ? $mov->medicamento->nome : 'Medicamento #'.$mov->medicamento_id }}</td>
                <td>{{ $mov->lote }}</td>
                <td><code>{{ $mov->codigo_barras ?: '-' }}</code></td>
                <td class="text-end">{{ number_format($mov->quantidade, 2, ',', '.') }}</td>
                <td class="text-end">R$ {{ valorDbForm($mov->valor) }}</td>
                <td class="text-end">R$ {{ valorDbForm($mov->total) }}</td>
                <td>
                  @if ($mov->tipo == 'Entrada')
                    <span class="badge bg-label-success">Entrada</span>
                  @else
                    <span class="badge bg-label-danger">Saída</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Transferência sem medicamentos.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @if (session('abrir_etiquetas'))
    <script>
      window.open('{{ route('estoque.transferencias.etiquetas', $transferencia->id) }}', '_blank');
    </script>
  @endif
@endsection
