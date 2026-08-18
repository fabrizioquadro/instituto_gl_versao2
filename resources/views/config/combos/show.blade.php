@extends('layouts.sistema')

@section('title', 'Combo - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ $combo->nome }}</h5>
      <div>
        <a href="{{ route('config.combos.edit', $combo->id) }}" class="btn btn-sm btn-primary"><i class="ri-pencil-line me-1"></i>Editar</a>
        <a href="{{ route('config.combos.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Voltar</a>
      </div>
    </div>
    <div class="card-body">
      <dl class="row mb-4">
        <dt class="col-sm-4">ID</dt>
        <dd class="col-sm-8">{{ $combo->id }}</dd>
        <dt class="col-sm-4">Criado em</dt>
        <dd class="col-sm-8">{{ $combo->created_at ? $combo->created_at->format('d/m/Y H:i') : '-' }}</dd>
        @if ($combo->id_versao1)
          <dt class="col-sm-4">Origem V1</dt>
          <dd class="col-sm-8"><span class="badge bg-label-info">id {{ $combo->id_versao1 }}</span></dd>
        @endif
      </dl>

      <h6 class="mb-3">Medicamentos</h6>
      @php $totalGeral = 0; @endphp
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Medicamento</th>
              <th>Quantidade</th>
              <th>Valor Unitário</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($combo->medicamentos as $med)
              @php $totalGeral += $med->valor_unitario * $med->quantidade; @endphp
              <tr>
                <td>{{ $med->medicamento ? $med->medicamento->nome : 'Medicamento excluído' }}</td>
                <td>{{ number_format($med->quantidade, 2, ',', '.') }}</td>
                <td>R$ {{ valorDbForm($med->valor_unitario) }}</td>
                <td>R$ {{ valorDbForm($med->valor_unitario * $med->quantidade) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Combo sem medicamentos.</td>
              </tr>
            @endforelse
          </tbody>
          @if ($combo->medicamentos->isNotEmpty())
            <tfoot>
              <tr class="table-light">
                <th colspan="3" class="text-end">Total do Combo</th>
                <th>R$ {{ valorDbForm($totalGeral) }}</th>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
@endsection
