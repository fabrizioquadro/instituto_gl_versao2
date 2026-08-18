@extends('layouts.sistema')

@section('title', 'Entrada - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Entrada #{{ $entrada->id }}</h5>
      <div>
        <a href="{{ route('estoque.entradas.etiquetas', $entrada->id) }}" class="btn btn-sm btn-outline-primary"><i class="ri-qr-code-line me-1"></i>Etiquetas</a>
        <a href="{{ route('estoque.entradas.espelho', $entrada->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="ri-printer-line me-1"></i>Espelho</a>
        <a href="{{ route('estoque.entradas.edit', $entrada->id) }}" class="btn btn-sm btn-primary"><i class="ri-pencil-line me-1"></i>Editar</a>
        <a href="{{ route('estoque.entradas.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Voltar</a>
      </div>
    </div>
    <div class="card-body">
      <dl class="row mb-4">
        <dt class="col-sm-3">Data</dt>
        <dd class="col-sm-9">{{ dataDbForm($entrada->data) }}</dd>
        <dt class="col-sm-3">Fornecedor</dt>
        <dd class="col-sm-9">{{ $entrada->fornecedor ? $entrada->fornecedor->nome : '-' }}</dd>
        <dt class="col-sm-3">Nr. Nota</dt>
        <dd class="col-sm-9">{{ $entrada->nota ?: '-' }}</dd>
        <dt class="col-sm-3">Valor Total</dt>
        <dd class="col-sm-9">R$ {{ valorDbForm($entrada->valor) }}</dd>
        <dt class="col-sm-3">Arquivo</dt>
        <dd class="col-sm-9">
          @if ($entrada->arquivo)
            <a href="{{ asset('img/entradas/notas/'.$entrada->arquivo) }}" target="_blank" class="btn btn-sm btn-outline-primary">Abrir arquivo</a>
          @else
            -
          @endif
        </dd>
        @if ($entrada->id_versao1)
          <dt class="col-sm-3">Origem V1</dt>
          <dd class="col-sm-9"><span class="badge bg-label-info">id {{ $entrada->id_versao1 }}</span></dd>
        @endif
      </dl>

      <h6 class="mb-3">Medicamentos da entrada</h6>
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
              <th>Vencimento</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($entrada->movimentos as $mov)
              <tr>
                <td>{{ $mov->medicamento ? $mov->medicamento->nome : 'Medicamento #'.$mov->medicamento_id }}</td>
                <td>{{ $mov->lote }}</td>
                <td><code>{{ $mov->codigo_barras ?: '-' }}</code></td>
                <td class="text-end">{{ number_format($mov->quantidade, 2, ',', '.') }}</td>
                <td class="text-end">R$ {{ valorDbForm($mov->valor) }}</td>
                <td class="text-end">R$ {{ valorDbForm($mov->total) }}</td>
                <td>{{ dataDbForm($mov->dt_vencimento) ?: '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Entrada sem medicamentos.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @if (session('abrir_espelho'))
    <script>
      window.open('{{ route('estoque.entradas.espelho', $entrada->id) }}', '_blank');
    </script>
  @endif
@endsection
