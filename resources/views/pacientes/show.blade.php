@extends('layouts.sistema')

@section('title', 'Paciente - '.$paciente->nm_paciente.' - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ $paciente->nm_paciente }}</h5>
      <a href="{{ route('pacientes.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Voltar</a>
    </div>
    <div class="card-body">
      @if (session('mensagem'))
        <div class="alert alert-success">{{ session('mensagem') }}</div>
      @endif

      <div class="row">
        <div class="col-lg-7">
          <dl class="row mb-0">
            <dt class="col-sm-4">Nome</dt>
            <dd class="col-sm-8">{{ $paciente->nm_paciente }}</dd>
            <dt class="col-sm-4">CPF</dt>
            <dd class="col-sm-8">{{ $paciente->cpf ?: '-' }}</dd>
            <dt class="col-sm-4">Nascimento</dt>
            <dd class="col-sm-8">{{ dataDbForm($paciente->dt_nascimento) ?: '-' }}</dd>
            <dt class="col-sm-4">Telefone</dt>
            <dd class="col-sm-8">{{ $paciente->telefone ?: '-' }}</dd>
            <dt class="col-sm-4">E-mail</dt>
            <dd class="col-sm-8">{{ $paciente->email ?: '-' }}</dd>
            <dt class="col-sm-4">Endereço</dt>
            <dd class="col-sm-8">{{ trim($paciente->endereco.' '.$paciente->numero.' '.$paciente->complemento.' - '.$paciente->bairro) ?: '-' }}</dd>
            <dt class="col-sm-4">Cidade/UF</dt>
            <dd class="col-sm-8">{{ trim($paciente->cidade.' '.$paciente->estado.' '.$paciente->cep) ?: '-' }}</dd>
            <dt class="col-sm-4">Id Feegow</dt>
            <dd class="col-sm-8"><code>{{ $paciente->paciente_id_feegow }}</code></dd>
            @if ($paciente->id_versao1)
              <dt class="col-sm-4">Origem V1</dt>
              <dd class="col-sm-8"><span class="badge bg-label-info">id {{ $paciente->id_versao1 }}</span></dd>
            @endif
            @if ($paciente->sincronizado_em)
              <dt class="col-sm-4">Sincronizado (Feegow)</dt>
              <dd class="col-sm-8">{{ $paciente->sincronizado_em->format('d/m/Y H:i') }}</dd>
            @endif
          </dl>
        </div>
        <div class="col-lg-5">
          <div class="card border">
            <div class="card-header">
              <h6 class="mb-0">Observação local</h6>
            </div>
            <div class="card-body">
              <form method="POST" action="{{ route('pacientes.obs', $paciente->id) }}">
                @csrf
                @method('PUT')
                <textarea class="form-control mb-2" name="obs" rows="4" placeholder="Observações internas (não vão para a Feegow)">{{ old('obs', $paciente->obs) }}</textarea>
                <button type="submit" class="btn btn-sm btn-primary"><i class="ri-save-line me-1"></i>Salvar observação</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
