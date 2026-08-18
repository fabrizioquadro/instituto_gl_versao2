@extends('layouts.sistema')

@section('title', 'Grupo - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Grupo</h5>
      <div>
        <a href="{{ route('config.grupos.edit', $grupo->id) }}" class="btn btn-sm btn-primary"><i class="ri-pencil-line me-1"></i>Editar</a>
        <a href="{{ route('config.grupos.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Voltar</a>
      </div>
    </div>
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-4">ID</dt>
        <dd class="col-sm-8">{{ $grupo->id }}</dd>
        <dt class="col-sm-4">Nome</dt>
        <dd class="col-sm-8">{{ $grupo->nome }}</dd>
        <dt class="col-sm-4">Medicamentos</dt>
        <dd class="col-sm-8">{{ $grupo->medicamentos->count() }}</dd>
        <dt class="col-sm-4">Criado em</dt>
        <dd class="col-sm-8">{{ $grupo->created_at ? $grupo->created_at->format('d/m/Y H:i') : '-' }}</dd>
        @if ($grupo->id_versao1)
          <dt class="col-sm-4">Origem V1</dt>
          <dd class="col-sm-8"><span class="badge bg-label-info">id {{ $grupo->id_versao1 }}</span></dd>
        @endif
      </dl>

      @if ($grupo->medicamentos->isNotEmpty())
        <hr />
        <h6 class="mb-3">Medicamentos do grupo</h6>
        <div class="table-responsive text-nowrap">
          <table class="table table-sm table-hover">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Situação</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($grupo->medicamentos as $med)
                <tr>
                  <td>{{ $med->nome }}</td>
                  <td>{{ $med->tipo }}</td>
                  <td>{{ $med->situacao }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
@endsection
