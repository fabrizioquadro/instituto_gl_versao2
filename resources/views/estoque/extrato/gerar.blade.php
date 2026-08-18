@extends('layouts.sistema')

@section('title', 'Extrato - '.$medicamento->nome.' - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h5 class="mb-0">{{ $medicamento->nome }}</h5>
        <span class="text-muted small">Tipo: {{ $medicamento->tipo }}</span>
        @if ($request->filled('codigo_barras'))
          <span class="badge bg-label-primary ms-2">Código: {{ $request->codigo_barras }}</span>
        @endif
        @if ($request->filled('clinica_id'))
          @php $clinicaFiltro = $clinicas->firstWhere('id', $request->clinica_id); @endphp
          @if ($clinicaFiltro)
            <span class="badge bg-label-secondary ms-2">{{ $clinicaFiltro->nome }}</span>
          @endif
        @endif
      </div>
      <div>
        <span class="badge bg-label-info me-2">Saldo final: {{ number_format($saldoAcumulado, 2, ',', '.') }}</span>
        <a href="{{ route('estoque.extrato') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Nova consulta</a>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Data</th>
              <th>Clínica</th>
              <th>Tipo</th>
              <th>Origem</th>
              <th>Lote</th>
              <th>Código de Barras</th>
              <th class="text-end">Quantidade</th>
              <th class="text-end">Saldo (código)</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($movimentos as $mov)
              <tr>
                <td>{{ $mov->id }}</td>
                <td>{{ $mov->created_at ? $mov->created_at->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $mov->clinica ? $mov->clinica->nome : '-' }}</td>
                <td>
                  @if ($mov->tipo == 'Entrada')
                    <span class="badge bg-label-success">Entrada</span>
                  @else
                    <span class="badge bg-label-danger">Saída</span>
                  @endif
                </td>
                <td>{{ $mov->origem }}</td>
                <td>{{ $mov->lote }}</td>
                <td><code>{{ $mov->codigo_barras ?: '-' }}</code></td>
                <td class="text-end">{{ number_format($mov->quantidade, 2, ',', '.') }}</td>
                <td class="text-end fw-semibold">{{ number_format($mov->saldo_codigo, 2, ',', '.') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">Nenhuma movimentação encontrada.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
