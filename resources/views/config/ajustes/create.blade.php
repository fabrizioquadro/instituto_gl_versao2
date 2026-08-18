@extends('layouts.sistema')

@section('title', 'Novo Ajuste de Estoque - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Novo Ajuste de Estoque</h5>
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
      @if (session('mensagem_erro'))
        <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
      @endif

      <form method="POST" action="{{ route('estoque.ajustes.store') }}">
        @csrf
        <div class="row gy-4">
          <div class="col-md-4">
            <x-select-clinica required />
          </div>
          <div class="col-md-4">
            <label for="medicamento_id" class="form-label">Medicamento</label>
            <select class="form-select" id="medicamento_id" name="medicamento_id" required>
              <option value="">Selecione</option>
              @foreach ($medicamentos as $med)
                <option value="{{ $med->id }}" @selected(old('medicamento_id') == $med->id)>{{ $med->nome.' / '.$med->tipo }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label for="tipo" class="form-label">Tipo</label>
            <select class="form-select" id="tipo" name="tipo" required>
              <option value="Entrada" @selected(old('tipo', 'Entrada') == 'Entrada')>Entrada (acréscimo)</option>
              <option value="Saida" @selected(old('tipo') == 'Saida')>Saída (abatimento)</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="codigo_barras" class="form-label">Código de Barras <small class="text-muted">(opcional)</small></label>
            <select class="form-select" id="codigo_barras" name="codigo_barras">
              <option value="">Novo / sem código</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="lote" class="form-label">Lote</label>
            <input type="text" class="form-control" id="lote" name="lote" value="{{ old('lote') }}" required />
          </div>
          <div class="col-md-4">
            <label for="dt_vencimento" class="form-label">Vencimento <small class="text-muted">(opcional)</small></label>
            <input type="date" class="form-control" id="dt_vencimento" name="dt_vencimento" value="{{ old('dt_vencimento') }}" />
          </div>
          <div class="col-md-4">
            <label for="quantidade" class="form-label">Quantidade</label>
            <input type="number" min="0.01" step="0.01" class="form-control" id="quantidade" name="quantidade" value="{{ old('quantidade') }}" required />
          </div>
          <div class="col-md-8">
            <label for="motivo" class="form-label">Motivo do Ajuste</label>
            <input type="text" class="form-control" id="motivo" name="motivo" value="{{ old('motivo') }}" required />
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary me-2">Salvar</button>
          <a href="{{ route('estoque.ajustes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    // carrega os códigos com saldo do medicamento na clínica selecionada
    function carregarCodigos() {
      const medicamentoId = document.getElementById('medicamento_id').value;
      const clinicaId = document.getElementById('clinica_id').value;
      const select = document.getElementById('codigo_barras');
      select.innerHTML = '<option value="">Novo / sem código</option>';
      if (!medicamentoId || !clinicaId) return;

      fetch('{{ route('estoque.estoques.get_codigos_barras') }}?medicamento_id=' + medicamentoId + '&clinica_id=' + clinicaId)
        .then(r => r.json())
        .then(json => {
          json.codigos.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.codigo_barras;
            opt.dataset.lote = c.lote;
            opt.dataset.vencimento = c.dt_vencimento || '';
            opt.dataset.saldo = c.saldo;
            opt.textContent = c.codigo_barras + ' — lote ' + c.lote + ' (' + Number(c.saldo).toFixed(2).replace('.', ',') + ')';
            select.appendChild(opt);
          });
        });
    }

    document.getElementById('medicamento_id').addEventListener('change', carregarCodigos);
    document.getElementById('clinica_id').addEventListener('change', carregarCodigos);

    document.getElementById('codigo_barras').addEventListener('change', function () {
      const opt = this.options[this.selectedIndex];
      if (opt.dataset.lote) {
        document.getElementById('lote').value = opt.dataset.lote;
      }
      if (opt.dataset.vencimento) {
        document.getElementById('dt_vencimento').value = opt.dataset.vencimento;
      }
    });
  </script>
@endsection
