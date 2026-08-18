@extends('layouts.sistema')

@section('title', 'Nova Transferência - Instituto GL')

@section('styles')
  <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/select2/select2.css') }}" />
@endsection

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Nova Transferência</h5>
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

      <form method="POST" action="{{ route('estoque.transferencias.store') }}">
        @csrf
        <input type="hidden" name="contador_medicamentos" id="contador_medicamentos" value="1" />

        <div class="row gy-4">
          <div class="col-md-3">
            <label for="clinica_origem_id" class="form-label">Clínica Origem</label>
            <select class="form-select" id="clinica_origem_id" name="clinica_origem_id" required>
              @foreach ($clinicas as $clinica)
                <option value="{{ $clinica->id }}" @selected($user->clinica_id == $clinica->id)>{{ $clinica->nome }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label for="clinica_destino_id" class="form-label">Clínica Destino</label>
            <select class="form-select" id="clinica_destino_id" name="clinica_destino_id" required>
              <option value="">Selecione</option>
              @foreach ($clinicas as $clinica)
                <option value="{{ $clinica->id }}">{{ $clinica->nome }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label for="data" class="form-label">Data</label>
            <input type="date" class="form-control" id="data" name="data" value="{{ old('data', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required />
          </div>
          <div class="col-md-3">
            <label for="motivo" class="form-label">Motivo</label>
            <input type="text" class="form-control" id="motivo" name="motivo" value="{{ old('motivo') }}" required />
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
          <div class="border rounded-3 p-3 mb-3 linha-medicamento" id="linha_transf_1">
            <div class="row g-3 align-items-end">
              <div class="col-md-7">
                <label for="medicamento_id_1" class="form-label small mb-1">Medicamento <span class="text-danger">*</span></label>
                <select name="medicamento_id_1" id="medicamento_id_1" class="form-select select2-medicamento" onchange="get_codigos(1)">
                  <option value="">Selecione</option>
                  @foreach ($medicamentos as $medicamento)
                    <option value="{{ $medicamento->id }}">{{ $medicamento->nome.' / '.$medicamento->tipo }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label for="quantidade_1" class="form-label small mb-1">Qtd <span class="text-danger">*</span></label>
                <input type="number" min="0" step="0.01" name="quantidade_1" id="quantidade_1" class="form-control" placeholder="0" />
              </div>
              <div class="col-md-2 d-flex justify-content-end">
                <button type="button" class="btn btn-icon btn-outline-danger" title="Excluir item" onclick="excluir_linha_transf(1)"><i class="ri-delete-bin-line"></i></button>
              </div>
            </div>
            <div class="row g-3 mt-1">
              <div class="col-md-4">
                <label for="codigo_barras_1" class="form-label small mb-1">Código de Barras</label>
                <select name="codigo_barras_1" id="codigo_barras_1" class="form-select" onchange="get_lotes(1)">
                  <option value="">Escolha o medicamento</option>
                </select>
              </div>
              <div class="col-md-4">
                <label for="lote_1" class="form-label small mb-1">Lote</label>
                <select name="lote_1" id="lote_1" class="form-select" onchange="seleciona_lote(1)">
                  <option value="">Escolha o código</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-1 d-block">Disponível</label>
                <span id="disponivel_1" class="badge bg-label-secondary fs-6">-</span>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary me-2">Salvar</button>
          <a href="{{ route('estoque.transferencias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('scripts')
  <script src="{{ asset('templates/assets/vendor/libs/select2/select2.js') }}"></script>
  <script>
    function clinicaAtual() {
      return document.getElementById('clinica_origem_id').value;
    }

    function get_codigos(linha) {
      const medicamentoId = document.getElementById('medicamento_id_' + linha).value;
      const selectCodigo = document.getElementById('codigo_barras_' + linha);
      const selectLote = document.getElementById('lote_' + linha);
      selectCodigo.innerHTML = '<option value="">Carregando...</option>';
      selectLote.innerHTML = '<option value="">Escolha o código</option>';
      document.getElementById('disponivel_' + linha).textContent = '-';

      if (!medicamentoId) {
        selectCodigo.innerHTML = '<option value="">Escolha o medicamento</option>';
        return;
      }

      fetch('{{ route('estoque.transferencias.get_codigos_barras') }}?medicamento_id=' + medicamentoId + '&clinica_id=' + clinicaAtual())
        .then(r => r.json())
        .then(json => {
          selectCodigo.innerHTML = '<option value="">Selecione o código</option>';
          json.codigos.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.codigo_barras;
            opt.textContent = c.codigo_barras + ' (' + Number(c.saldo).toFixed(2).replace('.', ',') + ')';
            selectCodigo.appendChild(opt);
          });
        });
    }

    function get_lotes(linha) {
      const medicamentoId = document.getElementById('medicamento_id_' + linha).value;
      const codigo = document.getElementById('codigo_barras_' + linha).value;
      const selectLote = document.getElementById('lote_' + linha);
      selectLote.innerHTML = '<option value="">Carregando...</option>';
      document.getElementById('disponivel_' + linha).textContent = '-';

      if (!codigo) {
        selectLote.innerHTML = '<option value="">Escolha o código</option>';
        return;
      }

      fetch('{{ route('estoque.transferencias.get_lotes') }}?medicamento_id=' + medicamentoId + '&codigo_barras=' + codigo + '&clinica_id=' + clinicaAtual())
        .then(r => r.json())
        .then(json => {
          selectLote.innerHTML = '<option value="">Selecione o lote</option>';
          json.lotes.forEach(l => {
            const opt = document.createElement('option');
            opt.value = l.lote;
            opt.dataset.saldo = l.saldo;
            opt.textContent = l.lote + ' (' + Number(l.saldo).toFixed(2).replace('.', ',') + ')';
            selectLote.appendChild(opt);
          });
        });
    }

    function seleciona_lote(linha) {
      const selectLote = document.getElementById('lote_' + linha);
      const opt = selectLote.options[selectLote.selectedIndex];
      const saldo = opt.dataset.saldo;
      document.getElementById('disponivel_' + linha).textContent = saldo !== undefined ? Number(saldo).toFixed(2).replace('.', ',') : '-';
    }

    function excluir_linha_transf(linha) {
      if (confirm('Tem certeza que deseja excluir este item?')) {
        document.getElementById('linha_transf_' + linha).remove();
      }
    }

    // impede origem == destino
    document.getElementById('clinica_destino_id').addEventListener('change', function () {
      const origem = document.getElementById('clinica_origem_id').value;
      if (this.value === origem) {
        alert('A clínica de destino não pode ser igual à de origem.');
        this.value = '';
      }
    });
    document.getElementById('clinica_origem_id').addEventListener('change', function () {
      const destino = document.getElementById('clinica_destino_id').value;
      if (this.value === destino) {
        alert('A clínica de origem não pode ser igual à de destino.');
        this.value = '';
      }
    });

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
        <option value="{{ $m->id }}">{{ $m->nome.' / '.$m->tipo }}</option>
      @endforeach`;

    function blocoMedicamento(contador) {
      return `
        <div class="row g-3 align-items-end">
          <div class="col-md-7">
            <label for="medicamento_id_${contador}" class="form-label small mb-1">Medicamento <span class="text-danger">*</span></label>
            <select name="medicamento_id_${contador}" id="medicamento_id_${contador}" class="form-select select2-medicamento" onchange="get_codigos(${contador})">
              <option value="">Selecione</option>
              ${opcoesMedicamentos}
            </select>
          </div>
          <div class="col-md-3">
            <label for="quantidade_${contador}" class="form-label small mb-1">Qtd <span class="text-danger">*</span></label>
            <input type="number" min="0" step="0.01" name="quantidade_${contador}" id="quantidade_${contador}" class="form-control" placeholder="0" />
          </div>
          <div class="col-md-2 d-flex justify-content-end">
            <button type="button" class="btn btn-icon btn-outline-danger" title="Excluir item" onclick="excluir_linha_transf(${contador})"><i class="ri-delete-bin-line"></i></button>
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <label for="codigo_barras_${contador}" class="form-label small mb-1">Código de Barras</label>
            <select name="codigo_barras_${contador}" id="codigo_barras_${contador}" class="form-select" onchange="get_lotes(${contador})">
              <option value="">Escolha o medicamento</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="lote_${contador}" class="form-label small mb-1">Lote</label>
            <select name="lote_${contador}" id="lote_${contador}" class="form-select" onchange="seleciona_lote(${contador})">
              <option value="">Escolha o código</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small mb-1 d-block">Disponível</label>
            <span id="disponivel_${contador}" class="badge bg-label-secondary fs-6">-</span>
          </div>
        </div>`;
    }

    document.getElementById('botao_adicionar_medicamento').addEventListener('click', function () {
      let contador = parseInt(document.getElementById('contador_medicamentos').value);
      contador++;
      document.getElementById('contador_medicamentos').value = contador;

      const div = document.createElement('div');
      div.className = 'border rounded-3 p-3 mb-3 linha-medicamento';
      div.setAttribute('id', 'linha_transf_' + contador);
      div.innerHTML = blocoMedicamento(contador);
      document.getElementById('medicamentos-container').appendChild(div);
      iniciarSelect2(div.querySelector('.select2-medicamento'));
    });

    // Inicializa o select2 nas linhas já existentes no carregamento
    document.querySelectorAll('.select2-medicamento').forEach(el => iniciarSelect2(el));
  </script>
@endsection
