@extends('layouts.sistema')

@section('title', 'Procedimento - Instituto GL')

@section('styles')
  <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/flatpickr/flatpickr.css') }}" />
  <style>
    /* Acordeão das semanas: todas começam fechadas e a seta fica à esquerda do título */
    #accordion-semanas .accordion-button {
      display: flex;
      align-items: center;
      gap: .75rem;
    }
    #accordion-semanas .accordion-button::after {
      order: -1;
      margin-left: 0;
      margin-right: 0;
    }
  </style>
@endsection

@section('content')
  @php
    $parcelas = $prescricao->financeiroParcelas;
    $totalParcelas = (float) $parcelas->sum('valor_parcela');
    $totalPago = (float) $parcelas->sum('valor_pago');
    $saldo = max(0, $totalParcelas - $totalPago);
    $parcelasExtra = $parcelas->whereIn('situacao', ['Em Aberto', 'Parcial'])
        ->sortBy('nr_parcela')->values()
        ->map(fn ($p) => [
            'id' => $p->id,
            'nr' => $p->nr_parcela,
            'semana' => $p->semana?->nr_semana ? 'Semana '.$p->semana->nr_semana : '-',
            'venc' => $p->dt_vencimento ? $p->dt_vencimento->format('d/m/Y') : '-',
            'valor' => (float) $p->valor_parcela,
            'pago' => (float) $p->valor_pago,
            'saldo' => max(0, (float) $p->valor_parcela - (float) $p->valor_pago),
        ])->all();
  @endphp

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h5 class="mb-0">{{ $prescricao->paciente?->nm_paciente ?? '-' }}</h5>
        <span class="text-muted small">Médico: {{ $prescricao->medico }} • Prescrição: {{ $prescricao->data_prescricao?->format('d/m/Y') ?? '-' }}</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge rounded-pill bg-label-primary">{{ $prescricao->situacao }}</span>
        <span class="badge rounded-pill bg-label-success">{{ $prescricao->situacao_financeira }}</span>
        <a href="{{ route('procedimentos.imprimir_detalhes', $prescricao->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Abrir janela com todas as informações e histórico para impressão">
          <i class="ri-printer-line me-1"></i>Imprimir Detalhes
        </a>
        <a href="{{ route('procedimentos.imprimir_detalhes_pdf', $prescricao->id) }}" class="btn btn-sm btn-outline-primary" title="Baixar o prontuário completo em um único PDF">
          <i class="ri-file-pdf-2-line me-1"></i>Baixar PDF
        </a>
        <a href="{{ route('procedimentos.index') }}" class="btn btn-sm btn-outline-secondary">
          <i class="ri-arrow-left-line me-1"></i>Voltar
        </a>
      </div>
    </div>

    <div class="card-body">
      @if (session('mensagem'))
        <div class="alert alert-success">{{ session('mensagem') }}</div>
      @endif
      @if (session('mensagem_erro'))
        <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $erro)
              <li>{{ $erro }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <ul class="nav nav-tabs" id="tab-procedimento" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-resumo" type="button" role="tab">Resumo</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-semanas" type="button" role="tab">Semanas</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-financeiro" type="button" role="tab">Financeiro</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-anexos" type="button" role="tab">Anexos</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-historico" type="button" role="tab">Histórico</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-observacoes" type="button" role="tab">Observações</button>
        </li>
      </ul>

      <div class="tab-content pt-3">
        {{-- ============ RESUMO ============ --}}
        <div class="tab-pane fade show active" id="tab-resumo" role="tabpanel">
          <div class="row g-3">
            <div class="col-md-3"><strong>Paciente:</strong><br>{{ $prescricao->paciente?->nm_paciente ?? '-' }}</div>
            <div class="col-md-3"><strong>Médico:</strong><br>{{ $prescricao->medico }}</div>
            <div class="col-md-3"><strong>Clínica:</strong><br>{{ $prescricao->clinica?->nome ?? '-' }}</div>
            <div class="col-md-3"><strong>Data da prescrição:</strong><br>{{ $prescricao->data_prescricao?->format('d/m/Y') ?? '-' }}</div>
            <div class="col-md-3"><strong>Tipo de atendimento:</strong><br>{{ $prescricao->tipo_atendimento ?: '-' }}</div>
            <div class="col-md-3"><strong>Agendamento:</strong><br>{{ $prescricao->agendamento ?: '-' }}</div>
            <div class="col-md-3"><strong>Semanas:</strong><br>{{ $prescricao->qt_semanas }} ({{ $prescricao->qt_semanas_aplicacao }} com aplicação)</div>
            <div class="col-md-3"><strong>Periodicidade:</strong><br>{{ $prescricao->periodicidade_dias ?: 7 }} dias</div>
            <div class="col-md-3"><strong>Parcelas:</strong><br>{{ $prescricao->qt_parcelas }}</div>
            <div class="col-md-3"><strong>Valor do tratamento:</strong><br>R$ {{ valorDbForm($prescricao->valor_tratamento) }}</div>
            <div class="col-md-3"><strong>Crédito em aberto:</strong><br>R$ {{ valorDbForm($prescricao->credito_em_aberto) }}</div>
            <div class="col-md-3"><strong>Total parcelas:</strong><br>R$ {{ valorDbForm($totalParcelas) }}</div>
            <div class="col-md-3"><strong>Saldo devedor:</strong><br>R$ {{ valorDbForm($saldo) }}</div>
            @if ($prescricao->obs)
              <div class="col-12"><strong>Observações:</strong><br>{{ $prescricao->obs }}</div>
            @endif
          </div>

          @if ($prescricao->situacao !== 'Cancelada')
            <hr>
            <form method="POST" action="{{ route('procedimentos.cancelar', $prescricao->id) }}" class="d-flex align-items-start gap-2"
                  onsubmit="return confirm('Confirmar cancelamento da prescrição?');">
              @csrf
              <input type="text" name="motivo" class="form-control" placeholder="Motivo do cancelamento (obrigatório)" required style="max-width: 400px;" />
              <button type="submit" class="btn btn-outline-danger"><i class="ri-close-circle-line me-1"></i>Cancelar</button>
            </form>
          @endif
        </div>

        {{-- ============ SEMANAS ============ --}}
        <div class="tab-pane fade" id="tab-semanas" role="tabpanel">
          @if ($prescricao->situacao !== 'Cancelada')
            <div class="d-flex justify-content-end mb-3">
              <a href="{{ route('procedimentos.semana.adicionar', $prescricao->id) }}" class="btn btn-sm btn-outline-primary">
                <i class="ri-add-line me-1"></i>Adicionar Semanas
              </a>
            </div>
          @endif
          <div class="accordion" id="accordion-semanas">
          @forelse ($prescricao->semanas as $semana)
            @php
              $parcelaSemana = $semana->financeiroParcela;
              $parcelaPaga = ! $parcelaSemana || (float) $parcelaSemana->valor_pago >= (float) $parcelaSemana->valor_parcela;
              $open = false;
            @endphp
            <div class="accordion-item mb-2">
              <h2 class="accordion-header" id="heading-semana-{{ $semana->id }}">
                <div class="d-flex align-items-center gap-2 w-100">
                  <button class="accordion-button {{ $open ? '' : 'collapsed' }} flex-grow-1" type="button"
                          data-bs-toggle="collapse" data-bs-target="#collapse-semana-{{ $semana->id }}"
                          aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="collapse-semana-{{ $semana->id }}">
                    <span class="fw-semibold">Semana {{ $semana->nr_semana }} — {{ $semana->data_prevista?->format('d/m/Y') }}</span>
                    <span class="badge rounded-pill bg-label-secondary ms-2">{{ $semana->situacao }}</span>
                  </button>
                  <div class="d-flex align-items-center gap-2 pe-3 flex-wrap">
                    @if ($prescricao->situacao !== 'Cancelada' && ! in_array($semana->situacao, ['Aplicado', 'Aplicação Parcial', 'Pendente']))
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-add-medicamento"
                              onclick="document.getElementById('addmed_semana_id').value = {{ $semana->id }}">
                        <i class="ri-add-line me-1"></i>Adicionar Medicamento
                      </button>
                    @endif
                    @if ($semana->tem_aplicacao && $prescricao->situacao !== 'Cancelada')
                      @if (in_array($semana->situacao, ['Fila de Aplicação', 'Atendimento', 'Aplicação Parcial', 'Pendente', 'Aplicado']))
                        <a href="{{ route('enfermagem.aplicacao', $semana->id) }}" class="btn btn-sm btn-primary">
                          <i class="ri-nurse-line me-1"></i>{{ $semana->situacao === 'Aplicado' ? 'Ver Aplicação' : 'Aplicar' }}
                        </a>
                      @elseif ($semana->situacao === 'Agendada')
                        <form method="POST" action="{{ route('enfermagem.fila.enviar', $semana->id) }}" class="d-inline">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-outline-primary"><i class="ri-time-line me-1"></i>Enviar para Fila</button>
                        </form>
                        @if (! $parcelaPaga)
                          <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modal-autorizar-fila"
                                  onclick="document.getElementById('autorizar_semana_id').value = {{ $semana->id }}">
                            <i class="ri-shield-keyhole-line me-1"></i>Enviar c/ autorização
                          </button>
                        @endif
                      @endif
                    @endif
                  </div>
                </div>
              </h2>
              <div id="collapse-semana-{{ $semana->id }}" class="accordion-collapse collapse {{ $open ? 'show' : '' }}"
                   aria-labelledby="heading-semana-{{ $semana->id }}">
                <div class="accordion-body">
                  @if ($semana->medicamentos->isEmpty())
                    <span class="text-muted small">Sem aplicação (pausa)</span>
                  @else
                    <div class="table-responsive">
                      <table class="table table-sm table-striped mb-0">
                        <thead>
                          <tr>
                            <th>Medicação</th>
                            <th>Qtd</th>
                            <th>Aplicação</th>
                            <th>Situação</th>
                            <th>Data prevista</th>
                            <th>Chegada</th>
                            <th>Atendimento</th>
                            <th>Aplicado em</th>
                            <th>Código</th>
                            <th>Lote</th>
                            <th>Vencimento</th>
                            <th>Aplicado por</th>
                            <th class="text-end">Ações</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach ($semana->medicamentos as $med)
                            @php $medAplicada = $med->situacao === 'Aplicada' && $med->lotes->isNotEmpty(); @endphp
                            <tr>
                              <td>
                                @if ($med->is_soro)
                                  <span class="badge bg-label-info">Soro</span> {{ $med->soro?->nome ?? $med->combo?->nome ?? $med->medicamento?->nome ?? '-' }}
                                @elseif ($med->combo_id)
                                  <span class="badge bg-label-warning">Combo</span> {{ $med->combo?->nome ?? '-' }}
                                @else
                                  {{ $med->medicamento?->nome ?? '-' }}
                                @endif
                              </td>
                              <td>{{ $med->quantidade }}</td>
                              <td>{{ $med->gera_aplicacao ? 'Sim' : 'Não' }}</td>
                              <td>{{ $med->situacao }}</td>
                              <td>{{ $med->data_prevista ? $med->data_prevista->format('d/m/Y') : '-' }}</td>
                              <td>{{ $med->dt_hr_chegada ? $med->dt_hr_chegada->format('d/m/Y H:i') : '-' }}</td>
                              <td>{{ $med->dt_hr_atendimento ? $med->dt_hr_atendimento->format('d/m/Y H:i') : '-' }}</td>
                              <td>{{ $med->aplicado_em ? $med->aplicado_em->format('d/m/Y H:i') : '-' }}</td>
                              <td>{!! $medAplicada ? $med->codigosDisplay() : '-' !!}</td>
                              <td>{!! $medAplicada ? $med->lotesDisplay() : '-' !!}</td>
                              <td>{!! $medAplicada ? $med->vencimentosDisplay() : '-' !!}</td>
                              <td>{{ $med->userAplicacao?->nome ?? '-' }}</td>
                              <td class="text-end">
                                @if (! $med->aplicado_em && $prescricao->situacao !== 'Cancelada')
                                  <div class="d-inline-flex align-items-center gap-1">
                                    <button type="button"
                                            class="btn btn-sm btn-icon btn-outline-secondary btn-editar-medicamento"
                                            data-id="{{ $med->id }}"
                                            data-nome="{{ $med->soro?->nome ?? $med->combo?->nome ?? $med->medicamento?->nome ?? '-' }}"
                                            data-qtd="{{ $med->quantidade }}"
                                            data-prevista="{{ $med->data_prevista ? $med->data_prevista->format('d/m/Y') : '' }}"
                                            data-obs="{{ $med->obs }}"
                                            title="Editar medicamento">
                                      <i class="ri-pencil-line"></i>
                                    </button>
                                    <form method="POST" action="{{ route('procedimentos.semana.medicamento.excluir', [$prescricao->id, $med->id]) }}" class="d-inline"
                                          onsubmit="return confirm('Excluir este medicamento da semana? Ainda não foi aplicado.');">
                                      @csrf
                                      <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Excluir medicamento da semana">
                                        <i class="ri-delete-bin-6-line"></i>
                                      </button>
                                    </form>
                                  </div>
                                @else
                                  <span class="text-muted small">—</span>
                                @endif
                              </td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  @endif
                  <hr class="my-2">
                  <div class="d-flex flex-column gap-1">
                    <form method="POST" action="{{ route('procedimentos.semana.obs.atualizar', [$prescricao->id, $semana->id]) }}" class="row g-2 align-items-end">
                      @csrf
                      <div class="col-12 col-md-9">
                        <label class="form-label small fw-semibold mb-1"><i class="ri-file-text-line me-1"></i>Observação da semana</label>
                        <textarea name="obs" class="form-control form-control-sm" rows="2" placeholder="Anotações desta semana...">{{ $semana->obs }}</textarea>
                      </div>
                      <div class="col-12 col-md-3 text-end">
                        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="ri-save-line me-1"></i>Salvar obs</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          @empty
            <span class="text-muted">Nenhuma semana gerada.</span>
          @endforelse
          </div>
        </div>

        {{-- ============ FINANCEIRO ============ --}}
        <div class="tab-pane fade" id="tab-financeiro" role="tabpanel">
          @if ($prescricao->situacao !== 'Cancelada')
            <div class="d-flex flex-wrap gap-2 mb-3">
              <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-pagamento-extra">
                <i class="ri-bank-card-line me-1"></i>Pagamento Extra
              </button>
            </div>
          @endif

          <div class="row g-3 mb-3">
            <div class="col-md-3"><strong>Total parcelas:</strong> R$ {{ valorDbForm($totalParcelas) }}</div>
            <div class="col-md-3"><strong>Total pago:</strong> R$ {{ valorDbForm($totalPago) }}</div>
            <div class="col-md-3"><strong>Saldo devedor:</strong> R$ {{ valorDbForm($saldo) }}</div>
            <div class="col-md-3">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <div>
                  <strong>Crédito em aberto:</strong><br>
                  <span class="text-muted small">R$ {{ valorDbForm($prescricao->credito_em_aberto) }}</span>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal-credito-aberto" title="Editar crédito em aberto">
                  <i class="ri-pencil-line"></i>
                </button>
              </div>
            </div>
          </div>

          <h6 class="fw-semibold">Parcelas</h6>
          <div class="table-responsive mb-4">
            <table class="table table-hover table-sm">
              <thead>
                <tr>
                  <th>Parcela</th>
                  <th>Semana</th>
                  <th>Vencimento</th>
                  <th class="text-end">Valor</th>
                  <th class="text-end">Pago</th>
                  <th class="text-end">Saldo</th>
                  <th>Situação</th>
                  <th>Obs</th>
                  <th class="text-end">Ações</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($parcelas as $parcela)
                  <tr>
                    <td>{{ $parcela->nr_parcela }}</td>
                    <td>{{ $parcela->semana?->nr_semana ? 'Semana '.$parcela->semana->nr_semana : '-' }}</td>
                    <td>{{ $parcela->dt_vencimento ? $parcela->dt_vencimento->format('d/m/Y') : '-' }}</td>
                    <td class="text-end">R$ {{ valorDbForm($parcela->valor_parcela) }}</td>
                    <td class="text-end">R$ {{ valorDbForm($parcela->valor_pago) }}</td>
                    <td class="text-end">R$ {{ valorDbForm(max(0, (float) $parcela->valor_parcela - (float) $parcela->valor_pago)) }}</td>
                    <td>
                      <span class="badge rounded-pill bg-label-{{ $parcela->situacao === 'Paga' ? 'success' : ($parcela->situacao === 'Parcial' ? 'warning' : 'secondary') }}">{{ $parcela->situacao }}</span>
                    </td>
                    <td>{{ $parcela->obs ?: '-' }}</td>
                    <td class="text-end">
                      <div class="d-inline-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary btn-editar-parcela"
                                data-id="{{ $parcela->id }}"
                                data-nr="{{ $parcela->nr_parcela }}"
                                data-valor="{{ valorDbForm($parcela->valor_parcela) }}"
                                data-vencimento="{{ $parcela->dt_vencimento ? $parcela->dt_vencimento->format('Y-m-d') : '' }}"
                                data-obs="{{ $parcela->obs }}"
                                title="Editar parcela">
                          <i class="ri-pencil-line"></i>
                        </button>
                        @if (max(0, (float) $parcela->valor_parcela - (float) $parcela->valor_pago) > 0)
                          <button type="button"
                                  class="btn btn-sm btn-outline-primary btn-pagar-parcela"
                                  data-id="{{ $parcela->id }}"
                                  data-nr="{{ $parcela->nr_parcela }}"
                                  data-semana="{{ $parcela->semana?->nr_semana ? 'Semana '.$parcela->semana->nr_semana : '-' }}"
                                  data-vencimento="{{ $parcela->dt_vencimento ? $parcela->dt_vencimento->format('d/m/Y') : '-' }}"
                                  data-valor="{{ valorDbForm($parcela->valor_parcela) }}"
                                  data-saldo="{{ valorDbForm(max(0, (float) $parcela->valor_parcela - (float) $parcela->valor_pago)) }}">
                            <i class="ri-money-dollar-circle-line me-1"></i>Pagamento
                          </button>
                        @endif
                        @if ((float) $parcela->valor_pago > 0)
                          <button type="button"
                                  class="btn btn-sm btn-icon btn-outline-info btn-ver-pagamentos"
                                  data-id="{{ $parcela->id }}"
                                  data-nr="{{ $parcela->nr_parcela }}"
                                  title="Ver pagamentos desta parcela">
                            <i class="ri-file-list-3-line"></i>
                          </button>
                        @endif
                        @if (max(0, (float) $parcela->valor_parcela - (float) $parcela->valor_pago) <= 0 && (float) $parcela->valor_pago <= 0)
                          <span class="text-muted small">—</span>
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          {{-- Detalhes de pagamentos por parcela (usado pelo modal) --}}
          @foreach ($parcelas as $parcela)
            @if ($parcela->pagamentos->isNotEmpty())
              <div class="d-none" id="pag-detalhes-{{ $parcela->id }}">
                @foreach ($parcela->pagamentos->sortBy('id') as $pp)
                  @php $pagamento = $pp->pagamento; @endphp
                  <div class="border rounded mb-3 overflow-hidden">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-2 bg-light border-bottom">
                      <strong><i class="ri-bank-card-line me-1 text-primary"></i>Pagamento #{{ $pagamento->id }}</strong>
                      <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold text-primary">R$ {{ valorDbForm($pp->valor) }} nesta parcela</span>
                        <form method="POST" action="{{ route('procedimentos.pagamentos.destroy', $pagamento->id) }}"
                              onsubmit="return confirm('Excluir este pagamento? A distribuição será revertida.');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Excluir pagamento #{{ $pagamento->id }}"><i class="ri-delete-bin-line"></i></button>
                        </form>
                      </div>
                    </div>
                    <div class="p-3">
                      <div class="row g-2 small">
                        <div class="col-md-4"><span class="text-muted">Data:</span> <strong>{{ $pagamento->dt_pagamento?->format('d/m/Y') ?? '-' }}</strong></div>
                        <div class="col-md-4"><span class="text-muted">Total do pagamento:</span> <strong>R$ {{ valorDbForm($pagamento->vl_total) }}</strong></div>
                        <div class="col-md-4"><span class="text-muted">Lançado por:</span> <strong>{{ $pagamento->user?->nome ?? '-' }}</strong></div>
                      </div>

                      <div class="mt-2">
                        <span class="text-muted small">Formas de pagamento:</span>
                        <div class="mt-1 d-flex flex-column gap-1">
                          @forelse ($pagamento->formas as $forma)
                            <div class="d-flex flex-wrap align-items-center gap-2 small">
                              <span class="badge bg-label-primary">{{ $forma->forma_pagamento }}</span>
                              <span>R$ {{ valorDbForm($forma->vl_pagamento) }}</span>
                              @if ($forma->parcelas > 1)
                                <span class="badge bg-label-warning">{{ $forma->parcelas }}x</span>
                              @endif
                              @if ($forma->id_transacao)
                                <span class="text-muted"><i class="ri-hashtag me-1"></i>ID transação: {{ $forma->id_transacao }}</span>
                              @endif
                              @if ($forma->obs)
                                <span class="text-muted"><i class="ri-question-line me-1"></i>{{ $forma->obs }}</span>
                              @endif
                            </div>
                          @empty
                            <span class="text-muted small">—</span>
                          @endforelse
                        </div>
                      </div>

                      @if ($pagamento->obs)
                        <div class="mt-2">
                          <span class="text-muted small">Observação do pagamento:</span>
                          <div class="alert alert-light border py-1 px-2 small mb-0 mt-1">{{ $pagamento->obs }}</div>
                        </div>
                      @endif

                      @if ($pagamento->anexos->isNotEmpty())
                        <div class="mt-2">
                          <span class="text-muted small">Comprovantes:</span>
                          <div class="d-flex flex-wrap gap-2 mt-1">
                            @foreach ($pagamento->anexos as $anexo)
                              <a href="{{ route('procedimentos.anexos.visualizar', $anexo->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ri-file-{{ $anexo->extensao === 'pdf' ? 'pdf' : 'image' }}-line me-1"></i>{{ $anexo->nm_anexo }}
                              </a>
                            @endforeach
                          </div>
                        </div>
                      @endif
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          @endforeach
        </div>

        {{-- ============ ANEXOS ============ --}}
        <div class="tab-pane fade" id="tab-anexos" role="tabpanel">
          <h6 class="fw-semibold">Prescrição</h6>
          @forelse ($prescricao->anexos as $anexo)
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="ri-file-{{ $anexo->extensao === 'pdf' ? 'pdf' : 'image' }}-line"></i>
              <span>{{ $anexo->nm_anexo }}</span>
              <a href="{{ route('procedimentos.anexos.visualizar', $anexo->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">Visualizar</a>
              <a href="{{ route('procedimentos.anexos.download', $anexo->id) }}" class="btn btn-sm btn-outline-secondary">Baixar</a>
              @if ($anexo->visualizado_em)
                <span class="badge bg-label-success">Visualizado {{ $anexo->visualizado_em->format('d/m/Y H:i') }}</span>
              @endif
            </div>
          @empty
            <span class="text-muted">Nenhum anexo de prescrição.</span>
          @endforelse

          <h6 class="fw-semibold mt-4">Comprovantes de pagamento</h6>
          @php $comprovantes = $prescricao->pagamentos->flatMap(fn ($p) => $p->anexos); @endphp
          @forelse ($comprovantes as $anexo)
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="ri-file-{{ $anexo->extensao === 'pdf' ? 'pdf' : 'image' }}-line"></i>
              <span>{{ $anexo->nm_anexo }}</span>
              <a href="{{ route('procedimentos.anexos.visualizar', $anexo->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">Visualizar</a>
              <a href="{{ route('procedimentos.anexos.download', $anexo->id) }}" class="btn btn-sm btn-outline-secondary">Baixar</a>
            </div>
          @empty
            <span class="text-muted">Nenhum comprovante.</span>
          @endforelse

          <h6 class="fw-semibold mt-4">Demonstrativos de pagamento</h6>
          @php $demonstrativos = $prescricao->anexos->where('tipo', 'demonstrativo_pagamento'); @endphp
          @forelse ($demonstrativos as $anexo)
            <div class="d-flex align-items-center gap-2 mb-2">
              <i class="ri-file-{{ $anexo->extensao === 'pdf' ? 'pdf' : 'image' }}-line"></i>
              <span>{{ $anexo->nm_anexo }}</span>
              <a href="{{ route('procedimentos.anexos.visualizar', $anexo->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">Visualizar</a>
              <a href="{{ route('procedimentos.anexos.download', $anexo->id) }}" class="btn btn-sm btn-outline-secondary">Baixar</a>
            </div>
          @empty
            <span class="text-muted">Nenhum demonstrativo.</span>
          @endforelse
        </div>

        {{-- ============ HISTÓRICO ============ --}}
        <div class="tab-pane fade" id="tab-historico" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Data</th>
                  <th>Usuário</th>
                  <th>Ação</th>
                  <th>Descrição</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($prescricao->logs->sortByDesc('id') as $log)
                  <tr>
                    <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->user?->nome ?? '-' }}</td>
                    <td><span class="badge bg-label-info">{{ $log->acao }}</span></td>
                    <td>{{ $log->descricao }}</td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-muted">Sem histórico.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        {{-- ============ OBSERVAÇÕES ============ --}}
        <div class="tab-pane fade" id="tab-observacoes" role="tabpanel">
          <form method="POST" action="{{ route('procedimentos.observacoes.store', $prescricao->id) }}" class="mb-4">
            @csrf
            <label class="form-label fw-semibold">Nova observação</label>
            <textarea name="observacao" class="form-control" rows="3" placeholder="Digite uma observação..." required maxlength="2000"></textarea>
            <div class="text-end mt-2">
              <button type="submit" class="btn btn-primary"><i class="ri-add-line me-1"></i>Adicionar observação</button>
            </div>
          </form>

          @forelse ($prescricao->observacoes->sortByDesc('id') as $obs)
            <div class="d-flex justify-content-between align-items-start gap-2 border rounded p-3 mb-2">
              <div>
                <div class="text-muted small mb-1">{{ $obs->created_at?->format('d/m/Y H:i') }} — {{ $obs->user?->nome ?? 'Sistema' }}</div>
                <div style="white-space: pre-wrap;">{{ $obs->observacao }}</div>
              </div>
              <form method="POST" action="{{ route('procedimentos.observacoes.destroy', [$prescricao->id, $obs->id]) }}"
                    onsubmit="return confirm('Excluir esta observação?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Excluir observação">
                  <i class="ri-delete-bin-6-line"></i>
                </button>
              </form>
            </div>
          @empty
            <span class="text-muted">Nenhuma observação registrada.</span>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- ============ MODAL PAGAMENTO EXTRA ============ --}}
  <div class="modal fade" id="modal-pagamento-extra" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form method="POST" action="{{ route('procedimentos.pagamento-extra', $prescricao->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="ri-bank-card-line me-1"></i>Pagamento Extra</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-primary py-2 small mb-3">
              <i class="ri-information-line me-1"></i>
              Lança um pagamento extra sobre o saldo devedor e escolhe como ele será aplicado nas parcelas em aberto.
            </div>

            <div class="mb-3">
              <label class="form-label">Data do pagamento</label>
              <input type="text" id="me_dt_pagamento" name="dt_pagamento" class="form-control flatpickr-date" required value="{{ now()->format('d/m/Y') }}" />
            </div>

            <label class="form-label">Formas de pagamento</label>
            <div id="me-formas">
              <div class="row g-2 mb-2 me-forma-row">
                <div class="col-md-3">
                  <select name="forma_pagamento[]" class="form-select me-forma-select">
                    <option>Dinheiro</option>
                    <option>Pix</option>
                    <option>Cartão de Crédito</option>
                    <option>Cartão de Débito</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <input type="text" name="vl_pagamento[]" class="form-control me-forma-valor text-end" placeholder="0,00" inputmode="decimal" />
                </div>
                <div class="col-md-2 d-none me-cartao-parcelas">
                  <select name="forma_parcelas[]" class="form-select">
                    @for ($i = 1; $i <= 12; $i++)
                      <option value="{{ $i }}" @selected($i === 1)>{{ $i }}x</option>
                    @endfor
                  </select>
                </div>
                <div class="col-md-3">
                  <input type="text" name="forma_id_transacao[]" class="form-control" placeholder="ID transação (opcional)" />
                </div>
                <div class="col-md-2">
                  <button type="button" class="btn btn-sm btn-outline-danger me-remove-forma" title="Remover"><i class="ri-delete-bin-line"></i></button>
                </div>
              </div>
            </div>
            <button type="button" id="me-add-forma" class="btn btn-sm btn-outline-primary mb-3">
              <i class="ri-add-line me-1"></i>Adicionar forma
            </button>
            <div class="mb-3">
              <span>Total das formas: <strong id="me-total">R$ 0,00</strong></span>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Este valor é para ser lançado na próxima parcela em aberto e ser recalculado o saldo devedor nas parcelas restantes, ou pagar parcela por parcela em ordem crescente?</label>
              <div class="form-check mb-1">
                <input class="form-check-input" type="radio" name="modo_extra" id="me-proxima" value="proxima" checked>
                <label class="form-check-label" for="me-proxima">Lançar na <strong>próxima parcela em aberto</strong> e <strong>recalcular o saldo devedor</strong> nas parcelas restantes (dividido igualmente)</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="modo_extra" id="me-ordem" value="ordem">
                <label class="form-check-label" for="me-ordem">Pagar <strong>parcela por parcela em ordem crescente</strong></label>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Prévia — como ficarão as parcelas em aberto:</label>
              <div class="table-responsive">
                <table class="table table-sm table-bordered">
                  <thead>
                    <tr>
                      <th>Parcela</th>
                      <th>Semana</th>
                      <th class="text-end">Vencimento</th>
                      <th class="text-end">Valor atual</th>
                      <th class="text-end">Saldo atual</th>
                      <th class="text-end">Resultado</th>
                      <th>Situação</th>
                    </tr>
                  </thead>
                  <tbody id="me-preview-tbody"></tbody>
                </table>
              </div>
              <div id="me-alerta" class="alert alert-danger mt-2 mb-0" style="display:none;"></div>
            </div>

            <div class="mb-3">
              <label class="form-label">Observação</label>
              <textarea name="obs" class="form-control" rows="2"></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Comprovantes (anexos, opcional — pode enviar vários)</label>
              <input type="file" name="anexos_comprovante[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple />
            </div>
            <div class="mb-3">
              <label class="form-label">Demonstrativo de pagamento (opcional)</label>
              <input type="file" name="demonstrativo_pagamento" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" />
              <span class="text-muted small"><i class="ri-information-line me-1"></i>Fica no financeiro do paciente e não aparece no imprimir do cadastro.</span>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar Pagamento Extra</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- ============ MODAL PAGAMENTO INDIVIDUAL DE PARCELA ============ --}}
  <div class="modal fade" id="modal-pagar-parcela" tabindex="-1" aria-hidden="true"
       data-url-base="{{ route('procedimentos.parcelas.pagar', [$prescricao->id, ':ID']) }}">
    <div class="modal-dialog modal-lg">
      <form method="POST" action="#" enctype="multipart/form-data">
        @csrf
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Pagamento — Parcela <span id="pp-nr" class="text-primary">-</span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-primary py-2 small mb-3">
              <i class="ri-information-line me-1"></i>
              <strong>Pagamento individual desta parcela.</strong> Pode pagar o valor integral ou parcial, mas
              <strong>não pode ser maior que o saldo</strong> restante da parcela.
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-3"><strong>Semana:</strong><br><span id="pp-semana">-</span></div>
              <div class="col-md-3"><strong>Vencimento:</strong><br><span id="pp-vencimento">-</span></div>
              <div class="col-md-3"><strong>Valor da parcela:</strong><br><span id="pp-valor">-</span></div>
              <div class="col-md-3"><strong>Saldo restante:</strong><br><span id="pp-saldo" class="text-primary fw-semibold">-</span></div>
            </div>

            <div class="mb-3">
              <label class="form-label">Data do pagamento</label>
              <input type="text" id="pp_dt_pagamento" name="dt_pagamento" class="form-control flatpickr-date" required value="{{ now()->format('d/m/Y') }}" />
            </div>

            <label class="form-label">Formas de pagamento</label>
            <div id="pp-formas">
              <div class="row g-2 mb-2 pp-forma-row">
                <div class="col-md-3">
                  <select name="forma_pagamento[]" class="form-select pp-forma-select">
                    <option>Dinheiro</option>
                    <option>Pix</option>
                    <option>Cartão de Crédito</option>
                    <option>Cartão de Débito</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <input type="text" name="vl_pagamento[]" class="form-control pp-forma-valor text-end" placeholder="0,00" inputmode="decimal" />
                </div>
                <div class="col-md-2 d-none pp-cartao-parcelas">
                  <select name="forma_parcelas[]" class="form-select">
                    @for ($i = 1; $i <= 12; $i++)
                      <option value="{{ $i }}" @selected($i === 1)>{{ $i }}x</option>
                    @endfor
                  </select>
                </div>
                <div class="col-md-3">
                  <input type="text" name="forma_id_transacao[]" class="form-control" placeholder="ID transação (opcional)" />
                </div>
                <div class="col-md-2">
                  <button type="button" class="btn btn-sm btn-outline-danger pp-remove-forma" title="Remover"><i class="ri-delete-bin-line"></i></button>
                </div>
              </div>
            </div>
            <button type="button" id="pp-add-forma" class="btn btn-sm btn-outline-primary mb-3">
              <i class="ri-add-line me-1"></i>Adicionar forma
            </button>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
              <span>Saldo da parcela: <strong id="pp-saldo2" class="text-primary">R$ 0,00</strong></span>
              <span>Total das formas: <strong id="pp-total">R$ 0,00</strong></span>
              <button type="button" id="pp-pagar-integral" class="btn btn-sm btn-outline-success"><i class="ri-check-double-line me-1"></i>Pagar integral</button>
            </div>
            <div id="pp-alerta" class="alert alert-danger mt-2 mb-0" style="display:none;"></div>

            <div class="mb-3 mt-3">
              <label class="form-label">Observação</label>
              <textarea name="obs" class="form-control" rows="2"></textarea>
            </div>

            <div>
              <label class="form-label">Comprovantes (anexos, opcional — pode enviar vários)</label>
              <input type="file" name="anexos_comprovante[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple />
            </div>
            <div class="mt-3">
              <label class="form-label">Demonstrativo de pagamento (opcional)</label>
              <input type="file" name="demonstrativo_pagamento" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" />
              <span class="text-muted small"><i class="ri-information-line me-1"></i>Fica no financeiro do paciente e não aparece no imprimir do cadastro.</span>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar Pagamento</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- ============ MODAL PAGAMENTOS DA PARCELA ============ --}}
  <div class="modal fade" id="modal-parcela-pagamentos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="pp-detalhes-titulo">Pagamentos da parcela</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body" id="pp-detalhes-conteudo"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>

  {{-- ============ MODAL AUTORIZAÇÃO (ENVIAR SEM PAGAMENTO) ============ --}}
  <div class="modal fade" id="modal-autorizar-fila" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" action="{{ route('enfermagem.fila.enviar_sem_pagamento') }}" class="modal-content">
        @csrf
        <input type="hidden" name="semana_id" id="autorizar_semana_id" value="">
        <div class="modal-header">
          <h5 class="modal-title">Enviar para Fila sem Pagamento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">Esta semana possui parcela em aberto. Para liberá-la na fila de atendimento, informe um administrador autorizador:</p>
          <div class="mb-3">
            <label class="form-label">E-mail do autorizador</label>
            <input type="email" name="autorizador_email" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Senha do autorizador</label>
            <input type="password" name="autorizador_senha" class="form-control" required />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning"><i class="ri-shield-keyhole-line me-1"></i>Autorizar e Enviar</button>
        </div>
      </form>
    </div>
  </div>

  {{-- ============ MODAL ADICIONAR MEDICAMENTO À SEMANA ============ --}}
  <div class="modal fade" id="modal-add-medicamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form method="POST" action="{{ route('procedimentos.semana.adicionar_medicamento', $prescricao->id) }}" class="modal-content">
        @csrf
        <input type="hidden" name="semana_id" id="addmed_semana_id" value="">
        <div class="modal-header">
          <h5 class="modal-title">Adicionar Medicamento à Semana</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Itens a adicionar (medicamento / combo / soro)</label>
            <div id="addmed_itens"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addmed_btn_item">
              <i class="ri-add-line me-1"></i>Adicionar item
            </button>
          </div>
          <div class="mb-3">
            <label class="form-label">Valor a distribuir (R$)</label>
            <input type="text" name="valor" id="addmed_valor" class="form-control text-end" placeholder="0,00" inputmode="decimal" />
          </div>
          <div class="mb-3">
            <label class="form-label">Distribuição do valor</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="distribuicao" id="dist_semana" value="semana" checked>
              <label class="form-check-label" for="dist_semana">Somente nesta semana</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="distribuicao" id="dist_parcelas" value="parcelas">
              <label class="form-check-label" for="dist_parcelas">Ratear nas parcelas (selecionar abaixo)</label>
            </div>
          </div>
          <div class="mb-3 d-none" id="addmed_parcelas">
            <label class="form-label">Ratear somente nas parcelas selecionadas</label>
            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
              @forelse ($prescricao->financeiroParcelas->sortBy('nr_parcela') as $parcela)
                <div class="form-check">
                  <input class="form-check-input addmed-parcela" type="checkbox" name="parcelas[]" value="{{ $parcela->id }}" id="addmed_parcela_{{ $parcela->id }}">
                  <label class="form-check-label" for="addmed_parcela_{{ $parcela->id }}">
                    Parcela {{ $parcela->nr_parcela }} — R$ {{ valorDbForm($parcela->valor_parcela) }}
                    @if ($parcela->semana)
                      (Semana {{ $parcela->semana->nr_semana }})
                    @endif
                    <span class="badge rounded-pill bg-label-{{ $parcela->situacao === 'Paga' ? 'success' : ($parcela->situacao === 'Parcial' ? 'warning' : 'secondary') }}">{{ $parcela->situacao }}</span>
                  </label>
                </div>
              @empty
                <span class="text-muted small">Nenhuma parcela cadastrada.</span>
              @endforelse
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ri-check-line me-1"></i>Adicionar</button>
        </div>
      </form>
    </div>
  </div>

  {{-- ============ MODAL EDITAR PARCELA ============ --}}
  <div class="modal fade" id="modal-editar-parcela" tabindex="-1" aria-hidden="true"
       data-url-base="{{ route('procedimentos.parcelas.atualizar', [$prescricao->id, ':ID']) }}">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Editar Parcela <span id="ep-nr"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Valor da parcela (R$) <span class="text-danger">*</span></label>
            <input type="text" name="valor_parcela" id="ep-valor" class="form-control text-end" placeholder="0,00" inputmode="decimal" />
          </div>
          <div class="mb-3">
            <label class="form-label">Data de vencimento</label>
            <input type="text" name="dt_vencimento" id="ep-vencimento" class="form-control" placeholder="dd/mm/aaaa" />
          </div>
          <div class="mb-3">
            <label class="form-label">Observação</label>
            <textarea name="obs" id="ep-obs" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ri-check-line me-1"></i>Salvar</button>
        </div>
      </form>
    </div>
  </div>

  {{-- ============ MODAL EDITAR CRÉDITO EM ABERTO ============ --}}
  <div class="modal fade" id="modal-credito-aberto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
      <form method="POST" action="{{ route('procedimentos.credito_em_aberto.atualizar', $prescricao->id) }}" class="modal-content">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Crédito em Aberto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-3">Valor que o paciente ainda tem de crédito pago para usar em próximos protocolos.</p>
          <div class="mb-3">
            <label class="form-label">Valor (R$) <span class="text-danger">*</span></label>
            <input type="text" name="credito_em_aberto" id="ca-valor" class="form-control text-end" placeholder="0,00" inputmode="decimal" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ri-check-line me-1"></i>Salvar</button>
        </div>
      </form>
    </div>
  </div>

  {{-- ============ MODAL EDITAR MEDICAMENTO DA SEMANA ============ --}}
  <div class="modal fade" id="modal-editar-medicamento" tabindex="-1" aria-hidden="true"
       data-url-base="{{ route('procedimentos.semana.medicamento.atualizar', [$prescricao->id, ':ID']) }}">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Editar Medicamento <span id="emm-nome"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Quantidade <span class="text-danger">*</span></label>
            <input type="text" name="quantidade" id="emm-qtd" class="form-control text-end" placeholder="0" inputmode="decimal" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Data prevista</label>
            <input type="text" name="data_prevista" id="emm-prevista" class="form-control" placeholder="dd/mm/aaaa" />
          </div>
          <div class="mb-3">
            <label class="form-label">Observação</label>
            <textarea name="obs" id="emm-obs" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="ri-check-line me-1"></i>Salvar</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('scripts')
  <script src="{{ asset('templates/assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
  <script>
    $(function () {
      // ---------- helpers ----------
      function parseMoeda(v) {
        if (!v) return 0;
        v = String(v).trim().replace(/[^\d.,-]/g, '');
        if (v.includes(',')) {
          v = v.replace(/\./g, '').replace(',', '.');
          return parseFloat(v) || 0;
        }
        v = v.replace(/\./g, '');
        return parseFloat(v) || 0;
      }
      function formatarMoeda(n) {
        return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
      function aplicarMascaraMoedaInput(el) {
        const digits = String(el.value).replace(/\D/g, '').slice(0, 14);
        if (!digits) { el.value = ''; return; }
        const cents = parseInt(digits, 10);
        const v = (cents / 100).toFixed(2);
        const partes = v.split('.');
        const intFmt = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        el.value = 'R$ ' + intFmt + ',' + partes[1];
      }

      // ---------- flatpickr ----------
      flatpickr('.flatpickr-date', {
        locale: {
          firstDayOfWeek: 0,
          weekdays: { shorthand: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'], longhand: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'] },
          months: { shorthand: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'], longhand: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'] }
        },
        dateFormat: 'd/m/Y',
        allowInput: true
      });

      // ---------- pagamento extra ----------
      const EXTRA_PARCELAS = @json($parcelasExtra);

      function meTotal() {
        let t = 0;
        $('.me-forma-valor').each(function () { t += parseMoeda($(this).val()); });
        return t;
      }

      function meRound2(n) { return Math.round((n + Number.EPSILON) * 100) / 100; }

      function simularExtra(total, modo) {
        const parcelas = EXTRA_PARCELAS.map(p => ({
          ...p,
          novoValor: p.valor,
          pagoNovo: p.pago,
          saldoNovo: p.saldo,
          recalculado: false
        }));
        if (modo === 'ordem') {
          let restante = total;
          for (const p of parcelas) {
            if (restante <= 0.001) break;
            const aplicar = Math.min(restante, p.saldoNovo);
            p.pagoNovo += aplicar;
            p.saldoNovo -= aplicar;
            restante -= aplicar;
          }
        } else {
          if (parcelas.length === 0) return parcelas;
          const primeira = parcelas[0];
          if (total >= primeira.valor) {
            // a próxima parcela em aberto assume o valor total do pagamento e fica paga
            primeira.novoValor = total;
            primeira.pagoNovo = total;
            primeira.saldoNovo = 0;
            primeira.recalculado = true;
            const sobra = total - primeira.valor;
            if (sobra > 0.001 && parcelas.length > 1) {
              const restantes = parcelas.slice(1);
              const totalRestantesAtual = restantes.reduce((s, p) => s + p.valor, 0);
              const novoTotalRestantes = Math.max(0, totalRestantesAtual - sobra);
              const n = restantes.length;
              let soma = 0;
              restantes.forEach((p, i) => {
                const novoValor = i === n - 1
                  ? Math.max(0, meRound2(novoTotalRestantes - soma))
                  : Math.max(0, meRound2(novoTotalRestantes / n));
                if (i < n - 1) soma += novoValor;
                p.novoValor = novoValor;
                p.saldoNovo = Math.max(0, novoValor - p.pago);
                p.recalculado = true;
              });
            }
          } else {
            // pagamento menor que a parcela: paga parcialmente mantendo o valor
            primeira.pagoNovo += total;
            primeira.saldoNovo -= total;
          }
        }
        return parcelas;
      }

      function meRecalcularPreview() {
        const total = meTotal();
        $('#me-total').text('R$ ' + formatarMoeda(total));
        const modo = $('input[name="modo_extra"]:checked').val();
        const sim = simularExtra(total, modo);
        const saldoTotal = EXTRA_PARCELAS.reduce((s, p) => s + p.saldo, 0);
        const $tb = $('#me-preview-tbody');
        $tb.empty();
        sim.forEach(p => {
          const sit = p.saldoNovo <= 0.009 ? 'Paga' : (p.pagoNovo > 0 ? 'Parcial' : 'Em Aberto');
          const sitCls = p.saldoNovo <= 0.009 ? 'success' : (p.pagoNovo > 0 ? 'warning' : 'secondary');
          let resultado = '-';
          if (p.recalculado) {
            resultado = '<span class="text-primary">R$ ' + formatarMoeda(p.novoValor) + '</span>';
          } else if (p.pagoNovo > p.pago + 0.009) {
            resultado = '<span class="text-success">R$ ' + formatarMoeda(p.pagoNovo - p.pago) + '</span>';
          }
          $tb.append(`<tr>
            <td>${p.nr}</td>
            <td>${p.semana}</td>
            <td class="text-end">${p.venc}</td>
            <td class="text-end">R$ ${formatarMoeda(p.valor)}</td>
            <td class="text-end">R$ ${formatarMoeda(p.saldo)}</td>
            <td class="text-end">${resultado}</td>
            <td><span class="badge rounded-pill bg-label-${sitCls}">${sit}</span></td>
          </tr>`);
        });
        const $alerta = $('#me-alerta');
        if (total > saldoTotal + 0.009) {
          $alerta.text('O total (R$ ' + formatarMoeda(total) + ') não pode ser maior que o saldo devedor (R$ ' + formatarMoeda(saldoTotal) + ').').show();
        } else if (total <= 0) {
          $alerta.text('Informe o valor do pagamento extra.').show();
        } else {
          $alerta.hide();
        }
      }

      function meAddForma() {
        const $row = $(`
          <div class="row g-2 mb-2 me-forma-row">
            <div class="col-md-3">
              <select name="forma_pagamento[]" class="form-select me-forma-select">
                <option>Dinheiro</option>
                <option>Pix</option>
                <option>Cartão de Crédito</option>
                <option>Cartão de Débito</option>
              </select>
            </div>
            <div class="col-md-2">
              <input type="text" name="vl_pagamento[]" class="form-control me-forma-valor text-end" placeholder="0,00" inputmode="decimal" />
            </div>
            <div class="col-md-2 d-none me-cartao-parcelas">
              <select name="forma_parcelas[]" class="form-select">
                @for ($i = 1; $i <= 12; $i++)
                  <option value="{{ $i }}">{{ $i }}x</option>
                @endfor
              </select>
            </div>
            <div class="col-md-3">
              <input type="text" name="forma_id_transacao[]" class="form-control" placeholder="ID transação (opcional)" />
            </div>
            <div class="col-md-2">
              <button type="button" class="btn btn-sm btn-outline-danger me-remove-forma" title="Remover"><i class="ri-delete-bin-line"></i></button>
            </div>
          </div>`);
        $('#me-formas').append($row);
      }

      $('#me-add-forma').on('click', meAddForma);

      $('#me-formas').on('click', '.me-remove-forma', function () {
        if ($('.me-forma-row').length > 1) {
          $(this).closest('.me-forma-row').remove();
          meRecalcularPreview();
        }
      });

      $('#me-formas').on('change', '.me-forma-select', function () {
        const isCartao = $(this).val() === 'Cartão de Crédito';
        $(this).closest('.me-forma-row').find('.me-cartao-parcelas').toggleClass('d-none', !isCartao);
      });

      $('#me-formas').on('input', '.me-forma-valor', function () {
        aplicarMascaraMoedaInput(this);
        meRecalcularPreview();
      });

      $('input[name="modo_extra"]').on('change', meRecalcularPreview);

      $('#modal-pagamento-extra').on('shown.bs.modal', function () {
        $('#me-formas .me-forma-row').not(':first').remove();
        $('.me-forma-valor').val('');
        $('.me-forma-select').val('Dinheiro').trigger('change');
        $('#modal-pagamento-extra textarea[name="obs"]').val('');
        $('#modal-pagamento-extra input[type="file"]').val('');
        meRecalcularPreview();
      });

      $('#modal-pagamento-extra form').on('submit', function () {
        $('.me-forma-valor').each(function () {
          const v = parseMoeda($(this).val());
          $(this).val(v > 0 ? formatarMoeda(v) : '0,00');
        });
        return true;
      });

      // ---------- pagamento individual de parcela ----------
      let ppSaldo = 0;

      function ppParseMoeda(v) {
        if (!v) return 0;
        v = String(v).trim().replace(/[^\d.,-]/g, '');
        if (v.includes(',')) {
          v = v.replace(/\./g, '').replace(',', '.');
          return parseFloat(v) || 0;
        }
        v = v.replace(/\./g, '');
        return parseFloat(v) || 0;
      }

      function ppRecalcular() {
        let total = 0;
        $('.pp-forma-valor').each(function () { total += ppParseMoeda($(this).val()); });
        $('#pp-total').text('R$ ' + formatarMoeda(total));
        const $alerta = $('#pp-alerta');
        if (total > ppSaldo + 0.009) {
          $alerta.text('O total (R$ ' + formatarMoeda(total) + ') não pode ser maior que o saldo da parcela (R$ ' + formatarMoeda(ppSaldo) + ').').show();
        } else if (total <= 0) {
          $alerta.text('Informe um valor de pagamento.').show();
        } else {
          $alerta.hide();
        }
      }

      function ppAddForma() {
        const $row = $(`
          <div class="row g-2 mb-2 pp-forma-row">
            <div class="col-md-3">
              <select name="forma_pagamento[]" class="form-select pp-forma-select">
                <option>Dinheiro</option>
                <option>Pix</option>
                <option>Cartão de Crédito</option>
                <option>Cartão de Débito</option>
              </select>
            </div>
            <div class="col-md-2">
              <input type="text" name="vl_pagamento[]" class="form-control pp-forma-valor text-end" placeholder="0,00" inputmode="decimal" />
            </div>
            <div class="col-md-2 d-none pp-cartao-parcelas">
              <select name="forma_parcelas[]" class="form-select">
                @for ($i = 1; $i <= 12; $i++)
                  <option value="{{ $i }}">{{ $i }}x</option>
                @endfor
              </select>
            </div>
            <div class="col-md-3">
              <input type="text" name="forma_id_transacao[]" class="form-control" placeholder="ID transação (opcional)" />
            </div>
            <div class="col-md-2">
              <button type="button" class="btn btn-sm btn-outline-danger pp-remove-forma" title="Remover"><i class="ri-delete-bin-line"></i></button>
            </div>
          </div>`);
        $('#pp-formas').append($row);
      }

      $('#pp-add-forma').on('click', ppAddForma);

      $('#pp-formas').on('click', '.pp-remove-forma', function () {
        if ($('.pp-forma-row').length > 1) {
          $(this).closest('.pp-forma-row').remove();
          ppRecalcular();
        }
      });

      $('#pp-formas').on('change', '.pp-forma-select', function () {
        const isCartao = $(this).val() === 'Cartão de Crédito';
        $(this).closest('.pp-forma-row').find('.pp-cartao-parcelas').toggleClass('d-none', !isCartao);
      });

      $('#pp-formas').on('input', '.pp-forma-valor', function () {
        aplicarMascaraMoedaInput(this);
        ppRecalcular();
      });

      $('#pp-pagar-integral').on('click', function () {
        $('.pp-forma-valor').each(function (i) {
          $(this).val(i === 0 ? 'R$ ' + formatarMoeda(ppSaldo) : '0,00');
        });
        ppRecalcular();
      });

      // ao clicar no botão Pagamento da parcela, preenche o modal
      $('.btn-pagar-parcela').on('click', function () {
        const $b = $(this);
        const saldoStr = String($b.data('saldo'));
        ppSaldo = parseFloat(saldoStr.replace(/\./g, '').replace(',', '.'));
        $('#pp-nr').text($b.data('nr'));
        $('#pp-semana').text($b.data('semana'));
        $('#pp-vencimento').text($b.data('vencimento'));
        $('#pp-valor').text('R$ ' + $b.data('valor'));
        $('#pp-saldo, #pp-saldo2').text('R$ ' + saldoStr);

        // limpa o formulário
        $('#pp-formas .pp-forma-row').not(':first').remove();
        $('.pp-forma-valor').val('');
        $('.pp-forma-select').val('Dinheiro').trigger('change');
        $('.pp-cartao-parcelas').addClass('d-none');
        $('#modal-pagar-parcela textarea[name="obs"]').val('');
        $('#modal-pagar-parcela input[type="file"]').val('');
        $('#pp-alerta').hide();
        $('#pp-total').text('R$ 0,00');

        // define o action do form com o id da parcela
        const urlBase = $('#modal-pagar-parcela').data('url-base');
        $('#modal-pagar-parcela form').attr('action', urlBase.replace(':ID', $b.data('id')));

        const modal = new bootstrap.Modal(document.getElementById('modal-pagar-parcela'));
        modal.show();
      });

      // detalhes de pagamentos feitos para a parcela
      $('.btn-ver-pagamentos').on('click', function () {
        const $b = $(this);
        $('#pp-detalhes-titulo').text('Pagamentos — Parcela ' + $b.data('nr'));
        $('#pp-detalhes-conteudo').html($('#pag-detalhes-' + $b.data('id')).html());
        const modal = new bootstrap.Modal(document.getElementById('modal-parcela-pagamentos'));
        modal.show();
      });

      // ao submeter, normaliza valores mascarados (servidor ignora zerados)
      $('#modal-pagar-parcela form').on('submit', function () {
        $('.pp-forma-valor').each(function () {
          const v = ppParseMoeda($(this).val());
          $(this).val(v > 0 ? formatarMoeda(v) : '0,00');
        });
        return true;
      });

      // ============ ADICIONAR MEDICAMENTO À SEMANA ============
      @php
        $addItens = array_merge(
            $medicamentos->map(fn ($m) => ['id' => $m->id, 'nome' => $m->nome, 'tipo' => 'medicamento'])->all(),
            $combos->map(fn ($c) => ['id' => $c->id, 'nome' => $c->nome, 'tipo' => 'combo'])->all(),
            $soros->map(fn ($s) => ['id' => $s->id, 'nome' => $s->nome, 'tipo' => 'soro'])->all()
        );
      @endphp
      const ADD_ITENS = @json($addItens);

      function addmedPopularItemSelect($sel, tipo) {
        $sel.empty();
        const lista = ADD_ITENS.filter(i => i.tipo === tipo);
        lista.forEach(i => $sel.append(new Option(i.nome, i.id)));
        if (lista.length) {
          $sel.val(lista[0].id);
        }
      }

      function addmedNovaLinha() {
        const $div = $('<div class="d-flex gap-2 align-items-center mb-2 addmed-linha"></div>');
        const $tipo = $('<select class="form-select form-select-sm addmed-tipo" name="item_tipo[]"></select>')
          .append('<option value="medicamento">Medicamento</option>')
          .append('<option value="combo">Combo</option>')
          .append('<option value="soro">Soro</option>');
        const $item = $('<select class="form-select form-select-sm addmed-item" name="item_id[]" required></select>');
        const $qtd = $('<input type="text" class="form-control form-control-sm addmed-qtd" name="item_qtd[]" placeholder="Qtd" required>');
        const $rem = $('<button type="button" class="btn btn-sm btn-icon btn-outline-danger" title="Remover"><i class="ri-delete-bin-line"></i></button>');
        $div.append($tipo, $item, $qtd, $rem);
        $('#addmed_itens').append($div);
        addmedPopularItemSelect($item, 'medicamento');
        $tipo.on('change', function () { addmedPopularItemSelect($item, this.value); });
        $rem.on('click', function () { $div.remove(); });
      }

      $('#addmed_btn_item').on('click', addmedNovaLinha);

      // máscara de valor no campo "Valor a distribuir"
      $('#addmed_valor').on('input', function () {
        aplicarMascaraMoedaInput(this);
      });

      // mostra/esconde a lista de parcelas conforme a opção escolhida
      $('input[name="distribuicao"]').on('change', function () {
        $('#addmed_parcelas').toggleClass('d-none', this.value !== 'parcelas');
      });

      $('#modal-add-medicamento').on('shown.bs.modal', function () {
        if ($('#addmed_itens .addmed-linha').length === 0) {
          addmedNovaLinha();
        }
        // reseta o modal
        $('#modal-add-medicamento input[name="distribuicao"][value="semana"]').prop('checked', true);
        $('#addmed_parcelas').addClass('d-none');
        $('#addmed_valor').val('');
        $('.addmed-parcela').prop('checked', false);
      });

      // normaliza o valor (remove a máscara p/ o backend) e valida parcelas selecionadas
      $('#modal-add-medicamento form').on('submit', function () {
        const $v = $(this).find('input[name="valor"]');
        const v = ppParseMoeda($v.val());
        $v.val(v > 0 ? formatarMoeda(v) : '');

        const distribuicao = $(this).find('input[name="distribuicao"]:checked').val();
        if (distribuicao === 'parcelas' && $(this).find('.addmed-parcela:checked').length === 0) {
          alert('Selecione pelo menos uma parcela para ratear o valor.');
          return false;
        }
        return true;
      });

      // ============ EDITAR PARCELA ============
      flatpickr('#ep-vencimento', { dateFormat: 'd/m/Y', allowInput: true });

      // ============ CRÉDITO EM ABERTO ============
      $('#modal-credito-aberto').on('shown.bs.modal', function () {
        const atual = $(this).data('atual') != null ? $(this).data('atual') : {{ (float) $prescricao->credito_em_aberto }};
        $('#ca-valor').val(Number(atual).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      });

      $('.btn-editar-parcela').on('click', function () {
        const $b = $(this);
        $('#ep-nr').text('— Parcela ' + $b.data('nr'));
        $('#ep-valor').val(formatarMoeda(ppParseMoeda($b.data('valor'))));
        $('#ep-vencimento').val($b.data('vencimento') ? String($b.data('vencimento')).split('-').reverse().join('/') : '');
        $('#ep-obs').val($b.data('obs') || '');
        const urlBase = $('#modal-editar-parcela').data('url-base');
        $('#modal-editar-parcela form').attr('action', urlBase.replace(':ID', $b.data('id')));
        new bootstrap.Modal(document.getElementById('modal-editar-parcela')).show();
      });

      $('#ep-valor').on('input', function () { aplicarMascaraMoedaInput(this); });

      $('#modal-editar-parcela form').on('submit', function () {
        const $v = $('#ep-valor');
        const v = ppParseMoeda($v.val());
        $v.val(v > 0 ? formatarMoeda(v) : '');
        return true;
      });

      // ============ EDITAR MEDICAMENTO DA SEMANA ============
      flatpickr('#emm-prevista', { dateFormat: 'd/m/Y', allowInput: true });

      $('.btn-editar-medicamento').on('click', function () {
        const $b = $(this);
        $('#emm-nome').text('— ' + $b.data('nome'));
        $('#emm-qtd').val($b.data('qtd'));
        $('#emm-prevista').val($b.data('prevista') || '');
        $('#emm-obs').val($b.data('obs') || '');
        const urlBase = $('#modal-editar-medicamento').data('url-base');
        $('#modal-editar-medicamento form').attr('action', urlBase.replace(':ID', $b.data('id')));
        new bootstrap.Modal(document.getElementById('modal-editar-medicamento')).show();
      });

      // quantidade aceita apenas dígitos e vírgula (decimal)
      $('#emm-qtd').on('input', function () {
        const v = this.value.replace(/[^\d.,]/g, '');
        this.value = v;
      });
    });
  </script>
@endsection
