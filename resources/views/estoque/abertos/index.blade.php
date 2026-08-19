@extends('layouts.sistema')

@section('title', 'Estoques Abertos - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0"><i class="ri-flask-line me-1 text-primary"></i>Estoques Abertos — {{ $clinica->nome ?? 'Minha Clínica' }}</h5>
      <span class="text-muted small">{{ $abertos->count() }} frasco(s)</span>
    </div>
    <div class="card-body">
      @if (session('mensagem'))
        <div class="alert alert-success">{{ session('mensagem') }}</div>
      @endif
      @if (session('mensagem_erro'))
        <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
      @endif

      <form method="GET" action="{{ route('estoque.abertos') }}" class="row gy-3 mb-3 align-items-end">
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
          <label for="situacao" class="form-label">Situação</label>
          <select class="form-select" id="situacao" name="situacao">
            <option value="todos" @selected(request('situacao', 'todos') == 'todos')>Todos</option>
            <option value="Aberto" @selected(request('situacao') == 'Aberto')>Aberto</option>
            <option value="Finalizado" @selected(request('situacao') == 'Finalizado')>Finalizado</option>
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
              <th>Lote</th>
              <th>Código de Barras</th>
              <th class="text-end">Inicial</th>
              <th class="text-end">Utilizado</th>
              <th class="text-end">Restante</th>
              <th>Situação</th>
              <th>Cadastro</th>
              <th>Usuário</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($abertos as $aberto)
              <tr class="{{ $aberto->situacao === 'Finalizado' ? 'table-light text-muted' : '' }}">
                <td>{{ $aberto->medicamento ? $aberto->medicamento->nome : 'Medicamento #'.$aberto->medicamento_id }}</td>
                <td>{{ $aberto->lote }}</td>
                <td><code>{{ $aberto->codigo_barras ?: '-' }}</code></td>
                <td class="text-end">{{ number_format($aberto->qt_inicial, 2, ',', '.') }}</td>
                <td class="text-end">{{ number_format($aberto->qt_utilizado, 2, ',', '.') }}</td>
                <td class="text-end fw-semibold">{{ number_format($aberto->qt_restante, 2, ',', '.') }}</td>
                <td>
                  @if ($aberto->situacao === 'Aberto')
                    <span class="badge bg-label-success">Aberto</span>
                  @else
                    <span class="badge bg-label-secondary">Finalizado</span>
                  @endif
                </td>
                <td>{{ $aberto->dt_cadastro ? dataDbForm($aberto->dt_cadastro) : '-' }}</td>
                <td>{{ $aberto->user ? $aberto->user->nome : '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">Nenhum estoque aberto nesta clínica.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
