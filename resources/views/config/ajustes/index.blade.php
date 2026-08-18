@extends('layouts.sistema')

@section('title', 'Ajustes de Estoque - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Ajustes de Estoque</h5>
      <a href="{{ route('estoque.ajustes.create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Novo Ajuste
      </a>
    </div>
    <div class="card-body">
      @if (session('mensagem'))
        <div class="alert alert-success">{{ session('mensagem') }}</div>
      @endif
      @if (session('mensagem_erro'))
        <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
      @endif

      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead>
            <tr>
              <th style="width: 40px;"></th>
              <th>Data</th>
              <th>Clínica</th>
              <th>Medicamento</th>
              <th>Lote</th>
              <th>C. Barras</th>
              <th>Tipo</th>
              <th class="text-end">Quantidade</th>
              <th>Motivo</th>
              <th>Usuário</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($ajustes as $ajuste)
              <tr>
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="ri-more-2-line ri-lg"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('estoque.ajustes.show', $ajuste->id) }}"><i class="ri-eye-line me-1"></i>Visualizar</a>
                      <form action="{{ route('estoque.ajustes.destroy', $ajuste->id) }}" method="POST" onsubmit="return confirm('Excluir (estornar) este ajuste?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger"><i class="ri-delete-bin-line me-1"></i>Estornar</button>
                      </form>
                    </div>
                  </div>
                </td>
                <td>{{ $ajuste->created_at ? $ajuste->created_at->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $ajuste->clinica ? $ajuste->clinica->nome : '-' }}</td>
                <td>{{ $ajuste->medicamento ? $ajuste->medicamento->nome : 'Medicamento #'.$ajuste->medicamento_id }}</td>
                <td>{{ $ajuste->lote }}</td>
                <td><code>{{ $ajuste->codigo_barras ?: '-' }}</code></td>
                <td>
                  @if ($ajuste->tipo == 'Entrada')
                    <span class="badge bg-label-success">Entrada</span>
                  @else
                    <span class="badge bg-label-danger">Saída</span>
                  @endif
                </td>
                <td class="text-end">{{ number_format($ajuste->quantidade, 2, ',', '.') }}</td>
                <td>{{ $ajuste->motivo }}</td>
                <td>{{ $ajuste->user ? $ajuste->user->nome : '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-muted py-4">Nenhum ajuste registrado.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
