@extends('layouts.sistema')

@section('title', 'Editar Entrada - Instituto GL')

@section('content')
  @php $contadorInicial = $entrada->movimentos->count(); @endphp
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Editar Entrada #{{ $entrada->id }}</h5>
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

      <form method="POST" action="{{ route('estoque.entradas.update', $entrada->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="contador_medicamentos" id="contador_medicamentos" value="{{ $contadorInicial }}" />

        <div class="row gy-4">
          <div class="col-md-3">
            <label for="fornecedor_id" class="form-label">Fornecedor</label>
            <select class="form-select" id="fornecedor_id" name="fornecedor_id" required>
              <option value="">Selecione</option>
              @foreach ($fornecedores as $fornecedor)
                <option value="{{ $fornecedor->id }}" @selected($entrada->fornecedor_id == $fornecedor->id)>{{ $fornecedor->nome }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label for="data" class="form-label">Data</label>
            <input type="date" class="form-control" id="data" name="data" value="{{ $entrada->data }}" max="{{ date('Y-m-d') }}" required />
          </div>
          <div class="col-md-3">
            <label for="nota" class="form-label">Nr. Nota</label>
            <input type="text" class="form-control" id="nota" name="nota" value="{{ $entrada->nota }}" />
          </div>
          <div class="col-md-3">
            <label for="arquivo" class="form-label">Arquivo da Nota</label>
            <input type="file" class="form-control" id="arquivo" name="arquivo" accept="application/pdf,image/*" />
            @if ($entrada->arquivo)
              <div class="form-text">Atual: <a href="{{ asset('img/entradas/notas/'.$entrada->arquivo) }}" target="_blank">{{ $entrada->arquivo }}</a></div>
            @endif
          </div>
        </div>

        <hr class="my-4" />

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0">Medicamentos</h6>
          <button type="button" class="btn btn-sm btn-primary" id="botao_adicionar_medicamento">
            <i class="ri-add-line me-1"></i>Adicionar
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-sm">
            <thead class="table-light">
              <tr>
                <th>Medicamento</th>
                <th style="width: 90px;">Qtd</th>
                <th style="width: 130px;">Unitário (R$)</th>
                <th style="width: 130px;">Total (R$)</th>
                <th style="width: 110px;">Lote</th>
                <th style="width: 130px;">Venc.</th>
                <th style="width: 140px;">C. Barras</th>
                <th style="width: 60px;"></th>
              </tr>
            </thead>
            <tbody id="tabela_medicamentos">
              @foreach ($entrada->movimentos as $idx => $mov)
                @php $n = $idx + 1; @endphp
                <tr id="linha_adicionar_{{ $n }}">
                  <td>
                    <select name="medicamento_id_{{ $n }}" id="medicamento_id_{{ $n }}" class="form-select">
                      <option value="">Selecione</option>
                      @foreach ($medicamentos as $medicamento)
                        <option value="{{ $medicamento->id }}" @selected($mov->medicamento_id == $medicamento->id)>{{ $medicamento->nome.' / '.$medicamento->fabricante.' - '.$medicamento->tipo.' '.$medicamento->vasilhame }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td><input type="number" min="0" step="0.01" name="quantidade_{{ $n }}" id="quantidade_{{ $n }}" class="form-control" value="{{ $mov->quantidade }}" onblur="calcula_total_medicamento({{ $n }})" /></td>
                  <td><input type="text" inputmode="decimal" name="valor_{{ $n }}" id="valor_{{ $n }}" class="form-control" value="{{ valorDbForm($mov->valor) }}" oninput="mascaraMoeda(this)" onblur="calcula_total_medicamento({{ $n }})" /></td>
                  <td><input type="text" inputmode="decimal" name="total_{{ $n }}" id="total_{{ $n }}" class="form-control" value="{{ valorDbForm($mov->total) }}" readonly /></td>
                  <td><input type="text" name="lote_{{ $n }}" id="lote_{{ $n }}" class="form-control" value="{{ $mov->lote }}" /></td>
                  <td><input type="date" name="dt_vencimento_{{ $n }}" id="dt_vencimento_{{ $n }}" class="form-control" value="{{ $mov->dt_vencimento }}" /></td>
                  <td>
                    <div class="input-group">
                      <input type="text" name="codigo_barras_{{ $n }}" id="codigo_barras_{{ $n }}" class="form-control" value="{{ $mov->codigo_barras }}" />
                      <button type="button" class="btn btn-outline-secondary" title="Gerar Código de Barras" onclick="get_codigo_barras({{ $n }})"><i class="ri-codepen-line"></i></button>
                    </div>
                  </td>
                  <td>
                    <button type="button" class="btn btn-icon btn-outline-danger" onclick="excluir_linha_medicamento({{ $n }})"><i class="ri-delete-bin-line"></i></button>
                  </td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr class="table-light">
                <th colspan="3" class="text-end">Total da Entrada</th>
                <th>
                  <input type="text" name="total_entrada" id="total_entrada" class="form-control" value="{{ valorDbForm($entrada->valor) }}" readonly />
                </th>
                <th colspan="4"></th>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary me-2">Salvar</button>
          <a href="{{ route('estoque.entradas.show', $entrada->id) }}" class="btn btn-outline-secondary">Cancelar</a>
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

    function parseMoeda(v) {
      if (!v) return 0;
      return parseFloat(v.replace(/\./g, '').replace(',', '.')) || 0;
    }

    function get_codigo_barras(linha) {
      const medicamentoId = document.getElementById('medicamento_id_' + linha).value;
      if (!medicamentoId) {
        alert('É necessário escolher o medicamento.');
        return;
      }
      fetch('{{ route('estoque.entradas.gerar_codigo_barras') }}?medicamento_id=' + medicamentoId)
        .then(r => r.json())
        .then(json => {
          document.getElementById('codigo_barras_' + linha).value = json.codigo;
        });
    }

    function calcula_total_medicamento(linha) {
      const qtd = parseFloat(document.getElementById('quantidade_' + linha).value) || 0;
      const valor = parseMoeda(document.getElementById('valor_' + linha).value);
      const total = qtd * valor;
      document.getElementById('total_' + linha).value = total.toFixed(2).replace('.', ',');
      calcula_total_entrada();
    }

    function calcula_total_entrada() {
      let somatorio = 0;
      document.querySelectorAll('#tabela_medicamentos input[id^="total_"]').forEach(input => {
        somatorio += parseMoeda(input.value);
      });
      document.getElementById('total_entrada').value = somatorio.toFixed(2).replace('.', ',');
    }

    function excluir_linha_medicamento(linha) {
      if (confirm('Tem certeza que deseja excluir este item?')) {
        document.getElementById('linha_adicionar_' + linha).remove();
        calcula_total_entrada();
      }
    }

    const opcoesMedicamentos = `@foreach ($medicamentos as $m)
        <option value="{{ $m->id }}">{{ $m->nome.' / '.$m->fabricante.' - '.$m->tipo.' '.$m->vasilhame }}</option>
      @endforeach`;

    document.getElementById('botao_adicionar_medicamento').addEventListener('click', function () {
      let contador = parseInt(document.getElementById('contador_medicamentos').value);
      contador++;
      document.getElementById('contador_medicamentos').value = contador;

      const tr = document.createElement('tr');
      tr.setAttribute('id', 'linha_adicionar_' + contador);
      tr.innerHTML = `
        <td>
          <select name="medicamento_id_${contador}" id="medicamento_id_${contador}" class="form-select">
            <option value="">Selecione</option>
            ${opcoesMedicamentos}
          </select>
        </td>
        <td><input type="number" min="0" step="0.01" name="quantidade_${contador}" id="quantidade_${contador}" class="form-control" onblur="calcula_total_medicamento(${contador})" /></td>
        <td><input type="text" inputmode="decimal" name="valor_${contador}" id="valor_${contador}" class="form-control" oninput="mascaraMoeda(this)" onblur="calcula_total_medicamento(${contador})" /></td>
        <td><input type="text" inputmode="decimal" name="total_${contador}" id="total_${contador}" class="form-control" readonly /></td>
        <td><input type="text" name="lote_${contador}" id="lote_${contador}" class="form-control" /></td>
        <td><input type="date" name="dt_vencimento_${contador}" id="dt_vencimento_${contador}" class="form-control" /></td>
        <td>
          <div class="input-group">
            <input type="text" name="codigo_barras_${contador}" id="codigo_barras_${contador}" class="form-control" />
            <button type="button" class="btn btn-outline-secondary" title="Gerar Código de Barras" onclick="get_codigo_barras(${contador})"><i class="ri-codepen-line"></i></button>
          </div>
        </td>
        <td>
          <button type="button" class="btn btn-icon btn-outline-danger" onclick="excluir_linha_medicamento(${contador})"><i class="ri-delete-bin-line"></i></button>
        </td>`;
      document.getElementById('tabela_medicamentos').appendChild(tr);
    });
  </script>
@endsection
