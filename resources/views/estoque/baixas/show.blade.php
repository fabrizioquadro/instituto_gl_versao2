@extends('layouts.sistema')

@section('title', 'Baixa - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Baixa #{{ $baixa->id }}</h5>
      <div class="d-flex gap-2">
        <a href="{{ route('estoque.baixas.espelho', $baixa->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="ri-printer-line me-1"></i>Espelho</a>
        <a href="{{ route('estoque.baixas.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Voltar</a>
      </div>
    </div>
    <div class="card-body">
      <dl class="row mb-4">
        <dt class="col-sm-3">Data</dt>
        <dd class="col-sm-9">{{ dataDbForm($baixa->data) }}</dd>
        <dt class="col-sm-3">Motivo</dt>
        <dd class="col-sm-9">{{ $baixa->motivo }}</dd>
        <dt class="col-sm-3">Usuário</dt>
        <dd class="col-sm-9">{{ $baixa->user ? $baixa->user->nome : '-' }}</dd>
        <dt class="col-sm-3">Valor Total</dt>
        <dd class="col-sm-9">R$ {{ valorDbForm($baixa->valor) }}</dd>
        @if ($baixa->id_versao1)
          <dt class="col-sm-3">Origem V1</dt>
          <dd class="col-sm-9"><span class="badge bg-label-info">id {{ $baixa->id_versao1 }}</span></dd>
        @endif
      </dl>

      <h6 class="mb-3">Medicamentos baixados</h6>
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
            </tr>
          </thead>
          <tbody>
            @forelse ($baixa->movimentos as $mov)
              <tr>
                <td>{{ $mov->medicamento ? $mov->medicamento->nome : 'Medicamento #'.$mov->medicamento_id }}</td>
                <td>{{ $mov->lote }}</td>
                <td><code>{{ $mov->codigo_barras ?: '-' }}</code></td>
                <td class="text-end">{{ number_format($mov->quantidade, 2, ',', '.') }}</td>
                <td class="text-end">R$ {{ valorDbForm($mov->valor) }}</td>
                <td class="text-end">R$ {{ valorDbForm($mov->total) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Baixa sem medicamentos.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @if (session('abrir_espelho'))
    <script>
      window.open('{{ route('estoque.baixas.espelho', $baixa->id) }}', '_blank');
    </script>
  @endif
@endsection
