@extends('layouts.sistema')

@section('title', 'Enfermagem - Instituto GL')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Enfermagem</h5>
        <span class="text-muted small">Fila de espera e atendimentos do dia</span>
      </div>
      @if (auth()->user()->isEnfermagem() || auth()->user()->isAdmin())
        <span class="badge rounded-pill bg-label-primary">{{ auth()->user()->clinica?->nome ?? 'Sem clínica' }}</span>
      @endif
    </div>

    <div class="card-body">
      @if (session('mensagem'))
        <div class="alert alert-success">{{ session('mensagem') }}</div>
      @endif
      @if (session('mensagem_erro'))
        <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
      @endif

      <ul class="nav nav-tabs" id="tab-enfermagem" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link {{ $aba === 'fila' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-fila" type="button" role="tab">Fila de Espera</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link {{ $aba === 'atendimentos' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-atendimentos" type="button" role="tab">Atendimentos do Dia</button>
        </li>
      </ul>

      <div class="tab-content pt-3">
        {{-- ============ FILA DE ESPERA ============ --}}
        <div class="tab-pane fade {{ $aba === 'fila' ? 'show active' : '' }}" id="tab-fila" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-hover table-sm">
              <thead class="table-light">
                <tr>
                  <th>Chegada</th>
                  <th>Paciente</th>
                  <th>Semana</th>
                  <th>Médico</th>
                  <th>Parcela</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($aguardandoGrupo as $grupo)
                  <tr>
                    <td>{{ $grupo->chegada ? $grupo->chegada->format('d/m/Y H:i') : '-' }}</td>
                    <td>
                      <div class="fw-semibold">{{ $grupo->paciente?->nm_paciente ?? '-' }}</div>
                      @if ($grupo->semanas->count() > 1)
                        <span class="badge bg-label-primary mt-1">{{ $grupo->semanas->count() }} semanas na fila</span>
                      @endif
                    </td>
                    <td>
                      <div class="d-flex flex-wrap gap-1">
                        @foreach ($grupo->semanas as $semana)
                          <span class="badge rounded-pill bg-label-primary">Semana {{ $semana->nr_semana }}/{{ $semana->prescricao->qt_semanas }}</span>
                        @endforeach
                        <span class="text-muted small align-self-center">{{ $grupo->primeira->data_prevista?->format('d/m/Y') }}</span>
                      </div>
                    </td>
                    <td>{{ $grupo->medicos->implode(', ') ?: '-' }}</td>
                    <td>
                      @if ($grupo->semanas->every(fn ($s) => $s->financeiroParcela))
                        @if ($grupo->parcelasPagas)
                          <span class="badge bg-label-success">Paga</span>
                        @else
                          <span class="badge bg-label-warning">Em aberto</span>
                        @endif
                      @else
                        <span class="text-muted small">—</span>
                      @endif
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-1 flex-wrap">
                        <a href="{{ route('enfermagem.aplicacao', $grupo->primeira->id) }}" class="btn btn-sm btn-primary">
                          <i class="ri-play-circle-line me-1"></i>Iniciar Atendimento
                        </a>
                        <a href="{{ route('procedimentos.imprimir_detalhes', $grupo->primeira->prescricao->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Visualizar prontuário completo (todas as informações e histórico)">
                          <i class="ri-file-user-line me-1"></i>Visualizar Prontuário Completo
                        </a>
                        <a href="{{ route('procedimentos.imprimir_detalhes_pdf', $grupo->primeira->prescricao->id) }}" class="btn btn-sm btn-outline-primary" title="Baixar o prontuário completo em um único PDF">
                          <i class="ri-file-pdf-2-line me-1"></i>PDF
                        </a>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">Nenhuma semana aguardando na fila.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        {{-- ============ ATENDIMENTOS DO DIA ============ --}}
        <div class="tab-pane fade {{ $aba === 'atendimentos' ? 'show active' : '' }}" id="tab-atendimentos" role="tabpanel">
          <h6 class="fw-semibold">Em Atendimento</h6>
          <div class="table-responsive mb-4">
            <table class="table table-hover table-sm">
              <thead class="table-light">
                <tr>
                  <th>Início</th>
                  <th>Paciente</th>
                  <th>Semana</th>
                  <th>Médico</th>
                  <th>Enfermeira</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($atendimento as $semana)
                  <tr>
                    <td>{{ $semana->dt_hr_atendimento ? $semana->dt_hr_atendimento->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $semana->prescricao->paciente?->nm_paciente ?? '-' }}</td>
                    <td><span class="badge rounded-pill bg-label-danger">Semana {{ $semana->nr_semana }}/{{ $semana->prescricao->qt_semanas }}</span></td>
                    <td>{{ $semana->prescricao->medico }}</td>
                    <td>{{ $semana->userAplicacao?->nome ?? '-' }}</td>
                    <td>
                      <a href="{{ route('enfermagem.aplicacao', $semana->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="ri-arrow-right-line me-1"></i>Continuar
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">Nenhum atendimento em andamento.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <h6 class="fw-semibold">Aplicadas Hoje</h6>
          <div class="table-responsive mb-4">
            <table class="table table-hover table-sm">
              <thead class="table-light">
                <tr>
                  <th>Finalização</th>
                  <th>Paciente</th>
                  <th>Semana</th>
                  <th>Médico</th>
                  <th>Enfermeira</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($aplicadas as $semana)
                  <tr>
                    <td>{{ $semana->dt_hr_finalizacao ? $semana->dt_hr_finalizacao->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $semana->prescricao->paciente?->nm_paciente ?? '-' }}</td>
                    <td><span class="badge rounded-pill bg-label-success">Semana {{ $semana->nr_semana }}/{{ $semana->prescricao->qt_semanas }}</span></td>
                    <td>{{ $semana->prescricao->medico }}</td>
                    <td>{{ $semana->userAplicacao?->nome ?? '-' }}</td>
                    <td>
                      <a href="{{ route('enfermagem.aplicacao', $semana->id) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="ri-eye-line me-1"></i>Ver
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">Nenhuma aplicação hoje.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <h6 class="fw-semibold">Resumo de Atendimentos do Dia</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Enfermeira</th>
                  <th class="text-center">Qtd. Pacientes</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($resumo as $nome => $qtd)
                  <tr>
                    <td>{{ $nome }}</td>
                    <td class="text-center"><b>{{ $qtd }}</b></td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="2" class="text-center text-muted py-3">Sem atendimentos hoje.</td>
                  </tr>
                @endforelse
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <th>TOTAL GERAL</th>
                  <th class="text-center">{{ array_sum($resumo) }}</th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
