@extends('layouts.sistema')

@section('title', 'Estoque - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Estoque — {{ $clinica->nome ?? 'Minha Clínica' }}</h5>
    </div>
    <div class="card-body">
      @if (session('mensagem'))
        <div class="alert alert-success">{{ session('mensagem') }}</div>
      @endif
      @if (session('mensagem_erro'))
        <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
      @endif

      <form method="GET" action="{{ route('estoque.estoques.index') }}" class="row gy-3 mb-3 align-items-end">
        <div class="col-md-3">
          <label for="clinica_id" class="form-label">Clínica</label>
          @if (auth()->user()->isAdmin())
            <select class="form-select" id="clinica_id" name="clinica_id">
              @foreach ($clinicas as $clinica)
                <option value="{{ $clinica->id }}" @selected(request('clinica_id', auth()->user()->clinica_id) == $clinica->id)>{{ $clinica->nome }}</option>
              @endforeach
            </select>
          @else
            <input type="text" class="form-control" value="{{ auth()->user()->clinica ? auth()->user()->clinica->nome : '-' }}" disabled>
            <input type="hidden" name="clinica_id" value="{{ auth()->user()->clinica_id }}">
          @endif
        </div>
        <div class="col-md-3">
          <label for="medicamento_id" class="form-label">Medicamento</label>
          <select class="form-select" id="medicamento_id" name="medicamento_id">
            <option value="">Todos</option>
            @foreach ($medicamentos as $med)
              <option value="{{ $med->id }}" @selected(request('medicamento_id') == $med->id)>{{ $med->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label for="alerta" class="form-label">Alerta</label>
          <select class="form-select" id="alerta" name="alerta">
            <option value="todos" @selected(request('alerta', 'todos') == 'todos')>Todos</option>
            <option value="vermelho" @selected(request('alerta') == 'vermelho')>Vermelho (abaixo do mínimo)</option>
            <option value="amarelo" @selected(request('alerta') == 'amarelo')>Amarelo (abaixo do médio)</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-line me-1"></i>Filtrar</button>
        </div>
      </form>

      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Medicamento</th>
              <th>Tipo</th>
              <th>Lote</th>
              <th>Código de Barras</th>
              <th>Vencimento</th>
              <th class="text-end">Saldo</th>
              <th>Alerta</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($saldos as $saldo)
              @php
                $alerta = $alertaPorMed[$saldo->medicamento_id] ?? 'ok';
                $vencido = $saldo->dt_vencimento && $saldo->dt_vencimento < date('Y-m-d');
              @endphp
              <tr>
                <td>{{ $saldo->medicamento ? $saldo->medicamento->nome : 'Medicamento #'.$saldo->medicamento_id }}</td>
                <td>{{ $saldo->medicamento ? $saldo->medicamento->tipo : '-' }}</td>
                <td>{{ $saldo->lote }}</td>
                <td><code>{{ $saldo->codigo_barras ?: '-' }}</code></td>
                <td>
                  @if ($vencido)
                    <span class="badge bg-label-danger">{{ dataDbForm($saldo->dt_vencimento) }} (vencido)</span>
                  @else
                    {{ dataDbForm($saldo->dt_vencimento) ?: '-' }}
                  @endif
                </td>
                <td class="text-end fw-semibold">{{ number_format($saldo->saldo, 2, ',', '.') }}</td>
                <td>
                  @if ($alerta === 'vermelho')
                    <span class="badge bg-label-danger">Crítico</span>
                  @elseif ($alerta === 'amarelo')
                    <span class="badge bg-label-warning">Atenção</span>
                  @else
                    <span class="badge bg-label-success">OK</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Nenhum saldo de estoque nesta clínica.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
