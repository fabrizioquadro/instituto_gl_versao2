@extends('layouts.sistema')

@section('title', 'Relatórios - Instituto GL')

@section('content')
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h4 class="mb-0">Relatórios</h4>
      <span class="text-muted small">Escolha um relatório para gerar</span>
    </div>
  </div>

  <div class="row g-3">
    @foreach ($relatorios as $r)
      <div class="col-sm-6 col-xl-4">
        <a href="{{ route('relatorios.'.$r['rota']) }}" class="text-decoration-none">
          <div class="card h-100">
            <div class="card-body d-flex align-items-start gap-3">
              <div class="avatar avatar-sm flex-shrink-0 bg-label-primary">
                <span class="avatar-initial rounded-2"><i class="{{ $r['icone'] }} ri-lg"></i></span>
              </div>
              <div>
                <h6 class="mb-1">{{ $r['titulo'] }}</h6>
                <p class="text-muted small mb-0">{{ $r['desc'] }}</p>
              </div>
            </div>
          </div>
        </a>
      </div>
    @endforeach
  </div>
@endsection
