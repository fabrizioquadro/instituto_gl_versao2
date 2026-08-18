@extends('layouts.sistema')

@section('title', 'Novo Combo - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Novo Combo</h5>
    </div>
    <div class="card-body">
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $erro)
              <li>{{ $erro }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('config.combos.store') }}">
        @csrf
        <input type="hidden" name="contador" id="contador" value="1" />

        <div class="row gy-4">
          <div class="col-md-6">
            <label for="nome" class="form-label">Nome</label>
            <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome') }}" required />
            @error('nome')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <hr class="my-4" />

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0">Medicamentos</h6>
          <button type="button" class="btn btn-sm btn-primary" id="btn_adicionar_medicamento">
            <i class="ri-add-line me-1"></i>Adicionar Medicamento
          </button>
        </div>

        <div class="table-responsive">
          <table class="table">
            <thead class="table-light">
              <tr>
                <th>Medicamento</th>
                <th style="width: 150px;">Quantidade</th>
                <th style="width: 180px;">Valor Unitário (R$)</th>
                <th style="width: 60px;"></th>
              </tr>
            </thead>
            <tbody id="tabelas_medicamentos">
              <tr id="linha_med_1">
                <td>
                  <select name="medicamento_id_1" class="form-select">
                    <option value="">Selecione</option>
                    @foreach ($medicamentos as $medicamento)
                      <option value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                    @endforeach
                  </select>
                </td>
                <td><input type="number" min="0" step="0.01" name="quantidade_1" class="form-control" /></td>
                <td><input type="text" inputmode="decimal" name="valor_1" class="form-control" oninput="mascaraMoeda(this)" /></td>
                <td>
                  <button type="button" class="btn btn-icon btn-outline-danger" onclick="excluir_linha(1)">
                    <i class="ri-delete-bin-line"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary me-2">Salvar</button>
          <a href="{{ route('config.combos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    function mascaraMoeda(el) {
      let v = el.value.replace(/\D/g, '');
      v = (v / 100).toFixed(2) + '';
      v = v.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      el.value = v;
    }

    const opcoesMedicamentos = `@foreach ($medicamentos as $m)
        <option value="{{ $m->id }}">{{ $m->nome }}</option>
      @endforeach`;

    document.getElementById('btn_adicionar_medicamento').addEventListener('click', function () {
      let contador = parseInt(document.getElementById('contador').value);
      contador++;
      document.getElementById('contador').value = contador;

      const tr = document.createElement('tr');
      tr.setAttribute('id', 'linha_med_' + contador);
      tr.innerHTML = `
        <td>
          <select name="medicamento_id_${contador}" class="form-select">
            <option value="">Selecione</option>
            ${opcoesMedicamentos}
          </select>
        </td>
        <td><input type="number" min="0" step="0.01" name="quantidade_${contador}" class="form-control" /></td>
        <td><input type="text" inputmode="decimal" name="valor_${contador}" class="form-control" oninput="mascaraMoeda(this)" /></td>
        <td>
          <button type="button" class="btn btn-icon btn-outline-danger" onclick="excluir_linha(${contador})">
            <i class="ri-delete-bin-line"></i>
          </button>
        </td>`;
      document.getElementById('tabelas_medicamentos').appendChild(tr);
    });

    function excluir_linha(linha) {
      if (confirm('Tem certeza que deseja excluir a linha selecionada?')) {
        document.getElementById('linha_med_' + linha).remove();
      }
    }
  </script>
@endsection
