@extends('layouts.sistema')

@section('title', 'Extrato de Estoque - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Extrato por Medicamento e Código de Barras</h5>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('estoque.extrato.gerar') }}">
        @csrf
        <div class="row gy-4">
          <div class="col-md-4">
            <label for="medicamento_id" class="form-label">Medicamento</label>
            <select class="form-select @error('medicamento_id') is-invalid @enderror" id="medicamento_id" name="medicamento_id" required>
              <option value="">Selecione</option>
              @foreach ($medicamentos as $med)
                <option value="{{ $med->id }}" @selected(old('medicamento_id') == $med->id)>{{ $med->nome.' / '.$med->tipo }}</option>
              @endforeach
            </select>
            @error('medicamento_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label for="codigo_barras" class="form-label">Código de Barras <small class="text-muted">(opcional)</small></label>
            <select class="form-select" id="codigo_barras" name="codigo_barras">
              <option value="">Todos os códigos</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="clinica_id" class="form-label">Clínica <small class="text-muted">(opcional)</small></label>
            <select class="form-select" id="clinica_id" name="clinica_id">
              <option value="">Todas as clínicas</option>
              @foreach ($clinicas as $clinica)
                <option value="{{ $clinica->id }}" @selected(old('clinica_id') == $clinica->id)>{{ $clinica->nome }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary"><i class="ri-search-line me-1"></i>Gerar Extrato</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    document.getElementById('medicamento_id').addEventListener('change', function () {
      const selectCodigo = document.getElementById('codigo_barras');
      selectCodigo.innerHTML = '<option value="">Todos os códigos</option>';
      if (!this.value) return;

      fetch('{{ route('estoque.extrato.codigos') }}?medicamento_id=' + this.value)
        .then(r => r.json())
        .then(json => {
          json.codigos.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            selectCodigo.appendChild(opt);
          });
        });
    });
  </script>
@endsection
