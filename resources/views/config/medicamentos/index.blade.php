@extends('layouts.sistema')

@section('title', 'Medicamentos - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Medicamentos</h5>
      <a href="{{ route('config.medicamentos.create') }}" class="btn btn-primary">
        <i class="ri-add-line me-1"></i>Novo Medicamento
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
              <th>Nome</th>
              <th>Fabricante</th>
              <th>Tipo</th>
              <th>Vasilhame</th>
              <th>Últ. Valor Pg.</th>
              <th>Vl. Venda</th>
              <th>Estoque Mín.</th>
              <th>Estoque Médio</th>
              <th>Situação</th>
              <th>Grupo</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($medicamentos as $med)
              <tr class="{{ $med->ehFerro() ? 'table-danger' : '' }}">
                <td>
                  <div class="dropdown">
                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="ri-more-2-line ri-lg"></i>
                    </button>
                    <div class="dropdown-menu">
                      <a class="dropdown-item" href="{{ route('config.medicamentos.show', $med->id) }}"><i class="ri-eye-line me-1"></i>Visualizar</a>
                      <a class="dropdown-item" href="{{ route('config.medicamentos.edit', $med->id) }}"><i class="ri-pencil-line me-1"></i>Editar</a>
                      <form action="{{ route('config.medicamentos.destroy', $med->id) }}" method="POST" onsubmit="return confirm('Excluir este medicamento?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger"><i class="ri-delete-bin-line me-1"></i>Excluir</button>
                      </form>
                    </div>
                  </div>
                </td>
                <td>
                  {{ $med->nome }}
                  @if ($med->ehFerro())
                    <span class="badge bg-label-danger ms-1"><i class="ri-alert-line me-1"></i>FERRO</span>
                  @endif
                </td>
                <td>{{ $med->fabricante }}</td>
                <td><span class="badge bg-label-secondary">{{ $med->tipo }}</span></td>
                <td>{{ $med->tipo == 'Vasilhame' ? $med->vasilhame : '-' }}</td>
                <td>R$ {{ valorDbForm($med->ultimo_valor_pg) }}</td>
                <td>R$ {{ valorDbForm($med->vl_venda) }}</td>
                <td><span class="text-danger fw-semibold">{{ number_format($med->estoque_minimo, 0, ',', '.') }}</span></td>
                <td><span class="text-warning fw-semibold">{{ number_format($med->estoque_medio, 0, ',', '.') }}</span></td>
                <td>
                  @if ($med->situacao == 'Ativo')
                    <span class="badge bg-label-success">Ativo</span>
                  @else
                    <span class="badge bg-label-danger">Inativo</span>
                  @endif
                </td>
                <td>{{ $med->grupo ? $med->grupo->nome : '-' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="text-center text-muted py-4">Nenhum medicamento cadastrado.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
