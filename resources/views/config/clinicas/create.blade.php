@extends('layouts.sistema')

@section('title', 'Nova Clínica - Instituto GL')

@section('content')
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Nova Clínica</h5>
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

          <form method="POST" action="{{ route('config.clinicas.store') }}">
            @csrf
            <div class="mb-3">
              <label for="nome" class="form-label">Nome</label>
              <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome') }}" required />
              @error('nome')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="mb-3">
              <label for="cnpj" class="form-label">CNPJ</label>
              <input type="text" class="form-control @error('cnpj') is-invalid @enderror" id="cnpj" name="cnpj" value="{{ old('cnpj') }}" maxlength="20" placeholder="00.000.000/0000-00" />
              @error('cnpj')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="mb-3">
              <label for="id_unidade_feegow" class="form-label">Unidade Flegow</label>
              <input type="number" class="form-control @error('id_unidade_feegow') is-invalid @enderror" id="id_unidade_feegow" name="id_unidade_feegow" value="{{ old('id_unidade_feegow') }}" required />
              @error('id_unidade_feegow')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <button type="submit" class="btn btn-primary me-2">Salvar</button>
            <a href="{{ route('config.clinicas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
