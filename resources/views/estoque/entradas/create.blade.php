@extends('layouts.sistema')

@section('title', 'Nova Entrada - Instituto GL')

@section('styles')
  <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Nova Entrada</h5>
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

      <form method="POST" action="{{ route('estoque.entradas.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="contador_medicamentos" id="contador_medicamentos" value="1" />

        <div class="row gy-4">
          <div class="col-md-3">
            <label for="fornecedor_id" class="form-label">Fornecedor</label>
            <select class="form-select @error('fornecedor_id') is-invalid @enderror" id="fornecedor_id" name="fornecedor_id" required>
              <option value="">Selecione</option>
              @foreach ($fornecedores as $fornecedor)
                <option value="{{ $fornecedor->id }}" @selected(old('fornecedor_id') == $fornecedor->id)>{{ $fornecedor->nome }}</option>
              @endforeach
            </select>
            @error('fornecedor_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-3">
            <label for="data" class="form-label">Data</label>
            <input type="date" class="form-control @error('data') is-invalid @enderror" id="data" name="data" value="{{ old('data', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required />
            @error('data')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-3">
            <label for="nota" class="form-label">Nr. Nota</label>
            <input type="text" class="form-control" id="nota" name="nota" value="{{ old('nota') }}" />
          </div>
          <div class="col-md-3">
            <label for="arquivo" class="form-label">Arquivo da Nota</label>
            <input type="file" class="form-control" id="arquivo" name="arquivo" accept="application/pdf,image/*" />
          </div>
        </div>

        <hr class="my-4" />

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0">Medicamentos</h6>
          <button type="button" class="btn btn-sm btn-primary" id="botao_adicionar_medicamento">
            <i class="ri-add-line me-1"></i>Adicionar
          </button>
        </div>

        <div id="medicamentos-container">
          <div class="border rounded-3 p-3 mb-3 linha-medicamento" id="linha_adicionar_1">
            <div class="row g-3 align-items-end">
              <div class="col-md-7">
                <label for="medicamento_id_1" class="form-label small mb-1">Medicamento <span class="text-danger">*</span></label>
                <select name="medicamento_id_1" id="medicamento_id_1" class="form-select select2-medicamento">
                  <option value="">Selecione</option>
                  @foreach ($medicamentos as $medicamento)
                    <option value="{{ $medicamento->id }}">{{ $medicamento->nome }} - {{ $medicamento->fabricante }} ({{ $medicamento->tipo }}{{ $medicamento->vasilhame ? ' '.$medicamento->vasilhame : '' }})</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label for="quantidade_1" class="form-label small mb-1">Quantidade <span class="text-danger">*</span></label>
                <input type="number" min="0" step="0.01" name="quantidade_1" id="quantidade_1" class="form-control" placeholder="0" onblur="calcula_total_medicamento(1)" />
              </div>
              <div class="col-md-2 d-flex justify-content-end">
                <button type="button" class="btn btn-icon btn-outline-danger" title="Excluir item" onclick="excluir_linha_medicamento(1)"><i class="ri-delete-bin-line"></i></button>
              </div>
            </div>
            <div class="row g-3 mt-1">
              <div class="col-6 col-md-2">
                <label for="valor_1" class="form-label small mb-1">Unitário (R$)</label>
                <input type="text" inputmode="decimal" name="valor_1" id="valor_1" class="form-control" placeholder="0,00" oninput="mascaraMoeda(this)" onblur="calcula_total_medicamento(1)" />
              </div>
              <div class="col-6 col-md-2">
                <label for="total_1" class="form-label small mb-1">Total (R$)</label>
                <input type="text" inputmode="decimal" name="total_1" id="total_1" class="form-control" readonly placeholder="0,00" />
              </div>
              <div class="col-6 col-md-2">
                <label for="lote_1" class="form-label small mb-1">Lote</label>
                <input type="text" name="lote_1" id="lote_1" class="form-control" />
              </div>
              <div class="col-6 col-md-2">
                <label for="dt_vencimento_1" class="form-label small mb-1">Vencimento</label>
                <input type="date" name="dt_vencimento_1" id="dt_vencimento_1" class="form-control" />
              </div>
              <div class="col-12 col-md-4">
                <label for="codigo_barras_1" class="form-label small mb-1">Código de Barras</label>
                <div class="input-group">
                  <input type="text" name="codigo_barras_1" id="codigo_barras_1" class="form-control" placeholder="Código" />
                  <button type="button" class="btn btn-outline-secondary" title="Gerar Código de Barras" onclick="get_codigo_barras(1)"><i class="ri-codepen-line"></i></button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end align-items-center gap-2 mb-3">
          <strong class="me-2">Total da Entrada:</strong>
          <input type="text" name="total_entrada" id="total_entrada" class="form-control text-end" style="max-width: 180px;" readonly placeholder="0,00" />
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary me-2">Salvar</button>
          <a href="{{ route('estoque.entradas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('scripts')
  <script src="{{ asset('templates/assets/vendor/libs/select2/select2.js') }}"></script>
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
      document.querySelectorAll('#medicamentos-container input[id^="total_"]').forEach(input => {
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

    function iniciarSelect2(el) {
      if (window.jQuery && jQuery.fn.select2) {
        jQuery(el).select2({
          placeholder: 'Selecione o medicamento',
          allowClear: false,
          width: '100%'
        });
      }
    }

    const opcoesMedicamentos = `@foreach ($medicamentos as $m)
        <option value="{{ $m->id }}">{{ $m->nome }} - {{ $m->fabricante }} ({{ $m->tipo }}{{ $m->vasilhame ? ' '.$m->vasilhame : '' }})</option>
      @endforeach`;

    function blocoMedicamento(contador) {
      return `
        <div class="row g-3 align-items-end">
          <div class="col-md-7">
            <label for="medicamento_id_${contador}" class="form-label small mb-1">Medicamento <span class="text-danger">*</span></label>
            <select name="medicamento_id_${contador}" id="medicamento_id_${contador}" class="form-select select2-medicamento">
              <option value="">Selecione</option>
              ${opcoesMedicamentos}
            </select>
          </div>
          <div class="col-md-3">
            <label for="quantidade_${contador}" class="form-label small mb-1">Quantidade <span class="text-danger">*</span></label>
            <input type="number" min="0" step="0.01" name="quantidade_${contador}" id="quantidade_${contador}" class="form-control" placeholder="0" onblur="calcula_total_medicamento(${contador})" />
          </div>
          <div class="col-md-2 d-flex justify-content-end">
            <button type="button" class="btn btn-icon btn-outline-danger" title="Excluir item" onclick="excluir_linha_medicamento(${contador})"><i class="ri-delete-bin-line"></i></button>
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-6 col-md-2">
            <label for="valor_${contador}" class="form-label small mb-1">Unitário (R$)</label>
            <input type="text" inputmode="decimal" name="valor_${contador}" id="valor_${contador}" class="form-control" placeholder="0,00" oninput="mascaraMoeda(this)" onblur="calcula_total_medicamento(${contador})" />
          </div>
          <div class="col-6 col-md-2">
            <label for="total_${contador}" class="form-label small mb-1">Total (R$)</label>
            <input type="text" inputmode="decimal" name="total_${contador}" id="total_${contador}" class="form-control" readonly placeholder="0,00" />
          </div>
          <div class="col-6 col-md-2">
            <label for="lote_${contador}" class="form-label small mb-1">Lote</label>
            <input type="text" name="lote_${contador}" id="lote_${contador}" class="form-control" />
          </div>
          <div class="col-6 col-md-2">
            <label for="dt_vencimento_${contador}" class="form-label small mb-1">Vencimento</label>
            <input type="date" name="dt_vencimento_${contador}" id="dt_vencimento_${contador}" class="form-control" />
          </div>
          <div class="col-12 col-md-4">
            <label for="codigo_barras_${contador}" class="form-label small mb-1">Código de Barras</label>
            <div class="input-group">
              <input type="text" name="codigo_barras_${contador}" id="codigo_barras_${contador}" class="form-control" placeholder="Código" />
              <button type="button" class="btn btn-outline-secondary" title="Gerar Código de Barras" onclick="get_codigo_barras(${contador})"><i class="ri-codepen-line"></i></button>
            </div>
          </div>
        </div>`;
    }

    document.getElementById('botao_adicionar_medicamento').addEventListener('click', function () {
      let contador = parseInt(document.getElementById('contador_medicamentos').value);
      contador++;
      document.getElementById('contador_medicamentos').value = contador;

      const div = document.createElement('div');
      div.className = 'border rounded-3 p-3 mb-3 linha-medicamento';
      div.setAttribute('id', 'linha_adicionar_' + contador);
      div.innerHTML = blocoMedicamento(contador);
      document.getElementById('medicamentos-container').appendChild(div);
      iniciarSelect2(div.querySelector('.select2-medicamento'));
    });

    // Inicializa o select2 nas linhas já existentes no carregamento
    document.querySelectorAll('.select2-medicamento').forEach(el => iniciarSelect2(el));
  </script>
@endsection
