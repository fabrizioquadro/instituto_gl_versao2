@extends('layouts.sistema')

@section('title', 'Editar Medicamento - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Editar Medicamento: {{ $medicamento->nome }}</h5>
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

      <form method="POST" action="{{ route('config.medicamentos.update', $medicamento->id) }}">
        @csrf
        @method('PUT')
        <div class="row gy-4">
          <div class="col-md-4">
            <label for="nome" class="form-label">Nome</label>
            <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $medicamento->nome) }}" required />
            @error('nome')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label for="fabricante" class="form-label">Fabricante</label>
            <input type="text" class="form-control @error('fabricante') is-invalid @enderror" id="fabricante" name="fabricante" value="{{ old('fabricante', $medicamento->fabricante) }}" required />
            @error('fabricante')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label for="tipo" class="form-label">Tipo</label>
            <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo" required>
              <option value="">Selecione</option>
              <option value="Ampola" @selected(old('tipo', $medicamento->tipo) == 'Ampola')>Ampola</option>
              <option value="Vasilhame" @selected(old('tipo', $medicamento->tipo) == 'Vasilhame')>Vasilhame</option>
              <option value="Procedimento" @selected(old('tipo', $medicamento->tipo) == 'Procedimento')>Procedimento</option>
            </select>
            @error('tipo')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4" id="grupo_vasilhame" style="display: none;">
            <label for="vasilhame" class="form-label">Tamanho do Vasilhame</label>
            <input type="number" min="1" class="form-control @error('vasilhame') is-invalid @enderror" id="vasilhame" name="vasilhame" value="{{ old('vasilhame', $medicamento->vasilhame) }}" />
            @error('vasilhame')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">Tamanho do vasilhame (ex.: 500, 1000).</div>
          </div>

          <div class="col-md-4">
            <label for="grupo_id" class="form-label">Grupo</label>
            <select class="form-select @error('grupo_id') is-invalid @enderror" id="grupo_id" name="grupo_id">
              <option value="">Sem grupo</option>
              @foreach ($grupos as $grupo)
                <option value="{{ $grupo->id }}" @selected(old('grupo_id', $medicamento->grupo_id) == $grupo->id)>{{ $grupo->nome }}</option>
              @endforeach
            </select>
            @error('grupo_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label for="situacao" class="form-label">Situação</label>
            <select class="form-select @error('situacao') is-invalid @enderror" id="situacao" name="situacao">
              <option value="Ativo" @selected(old('situacao', $medicamento->situacao) == 'Ativo')>Ativo</option>
              <option value="Inativo" @selected(old('situacao', $medicamento->situacao) == 'Inativo')>Inativo</option>
            </select>
            @error('situacao')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label for="ultimo_valor_pg" class="form-label">Último Valor Pago (R$)</label>
            <input type="text" inputmode="decimal" class="form-control @error('ultimo_valor_pg') is-invalid @enderror" id="ultimo_valor_pg" name="ultimo_valor_pg" value="{{ old('ultimo_valor_pg', valorDbForm($medicamento->ultimo_valor_pg)) }}" oninput="mascaraMoeda(this)" />
            @error('ultimo_valor_pg')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label for="vl_venda" class="form-label">Valor de Venda (R$)</label>
            <input type="text" inputmode="decimal" class="form-control @error('vl_venda') is-invalid @enderror" id="vl_venda" name="vl_venda" value="{{ old('vl_venda', valorDbForm($medicamento->vl_venda)) }}" oninput="mascaraMoeda(this)" required />
            @error('vl_venda')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label for="estoque_minimo" class="form-label">Estoque Mínimo <span class="text-danger">(alerta vermelho)</span></label>
            <input type="number" min="0" step="0.01" class="form-control @error('estoque_minimo') is-invalid @enderror" id="estoque_minimo" name="estoque_minimo" value="{{ old('estoque_minimo', $medicamento->estoque_minimo) }}" />
            @error('estoque_minimo')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label for="estoque_medio" class="form-label">Estoque Médio <span class="text-warning">(alerta amarelo)</span></label>
            <input type="number" min="0" step="0.01" class="form-control @error('estoque_medio') is-invalid @enderror" id="estoque_medio" name="estoque_medio" value="{{ old('estoque_medio', $medicamento->estoque_medio) }}" />
            @error('estoque_medio')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label for="aplicacao" class="form-label">Gera Aplicação</label>
            <select class="form-select @error('aplicacao') is-invalid @enderror" id="aplicacao" name="aplicacao">
              <option value="Sim" @selected(old('aplicacao', $medicamento->aplicacao) == 'Sim')>Sim</option>
              <option value="Não" @selected(old('aplicacao', $medicamento->aplicacao) == 'Não')>Não</option>
            </select>
            @error('aplicacao')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label for="aplicacao_feegow_id" class="form-label">Feegow Aplicação ID</label>
            <input type="number" class="form-control @error('aplicacao_feegow_id') is-invalid @enderror" id="aplicacao_feegow_id" name="aplicacao_feegow_id" value="{{ old('aplicacao_feegow_id', $medicamento->aplicacao_feegow_id) }}" />
            @error('aplicacao_feegow_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary me-2">Salvar</button>
          <a href="{{ route('config.medicamentos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
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

    function atualizarVasilhame() {
      const tipo = document.getElementById('tipo').value;
      const grupo = document.getElementById('grupo_vasilhame');
      const vas = document.getElementById('vasilhame');
      if (tipo === 'Vasilhame') {
        grupo.style.display = '';
        vas.removeAttribute('readonly');
        vas.removeAttribute('disabled');
      } else {
        grupo.style.display = 'none';
        vas.value = '';
        vas.setAttribute('readonly', 'readonly');
        vas.setAttribute('disabled', 'disabled');
      }
    }

    document.getElementById('tipo').addEventListener('change', atualizarVasilhame);
    atualizarVasilhame();
  </script>
@endsection
