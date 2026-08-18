@extends('layouts.sistema')

@section('title', 'Aplicação - Enfermagem - Instituto GL')

@section('content')
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h5 class="mb-0">Aplicação — Semana {{ $semana->nr_semana }}</h5>
        <span class="text-muted small">
          {{ $semana->prescricao->paciente?->nm_paciente ?? '-' }} • Médico: {{ $semana->prescricao->medico }}
        </span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge rounded-pill bg-label-primary">{{ $semana->situacao }}</span>
        @if ($visualizar)
          <span class="badge rounded-pill bg-label-info">Visualização</span>
        @endif
        <a href="{{ route('enfermagem.index') }}" class="btn btn-sm btn-outline-secondary">
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

      {{-- Outras semanas do paciente na fila/atendimento (R16) --}}
      @if ($outrasSemanasFila->isNotEmpty())
        <div class="alert alert-info py-2 d-flex align-items-center gap-2 flex-wrap">
          <i class="ri-list-check-2 ri-18px"></i>
          <strong class="me-1">Este paciente tem mais semanas na fila:</strong>
          @foreach ($outrasSemanasFila as $outra)
            <a href="{{ route('enfermagem.aplicacao', $outra->id) }}" class="btn btn-sm btn-outline-primary">
              Semana {{ $outra->nr_semana }}/{{ $outra->prescricao->qt_semanas }}
              <span class="text-muted small">({{ $outra->data_prevista?->format('d/m/Y') }})</span>
            </a>
          @endforeach
        </div>
      @endif

      <div class="row g-3 mb-3">
        <div class="col-md-3"><strong>Paciente:</strong><br>{{ $semana->prescricao->paciente?->nm_paciente ?? '-' }}</div>
        <div class="col-md-3"><strong>CPF:</strong><br>{{ $semana->prescricao->paciente?->cpf ?? '-' }}</div>
        <div class="col-md-3"><strong>Nascimento:</strong><br>{{ $semana->prescricao->paciente?->dt_nascimento ? dataDbForm($semana->prescricao->paciente->dt_nascimento) : '-' }}</div>
        <div class="col-md-3"><strong>Clínica:</strong><br>{{ $semana->prescricao->clinica?->nome ?? '-' }}</div>
        <div class="col-md-3"><strong>Data da prescrição:</strong><br>{{ $semana->prescricao->data_prescricao?->format('d/m/Y') ?? '-' }}</div>
        <div class="col-md-3"><strong>Tipo de atendimento:</strong><br>{{ $semana->prescricao->tipo_atendimento ?: '-' }}</div>
        <div class="col-md-3"><strong>Agendamento:</strong><br>{{ $semana->prescricao->agendamento ?: '-' }}</div>
        <div class="col-md-3"><strong>Semana:</strong><br>Semana {{ $semana->nr_semana }} de {{ $semana->prescricao->qt_semanas }}</div>
        <div class="col-md-3"><strong>Médico:</strong><br>{{ $semana->prescricao->medico }}</div>
        <div class="col-md-3"><strong>Data prevista:</strong><br>{{ $semana->data_prevista?->format('d/m/Y') }}</div>
        <div class="col-md-3"><strong>Chegada:</strong><br>{{ $semana->dt_hr_chegada ? $semana->dt_hr_chegada->format('d/m/Y H:i') : '-' }}</div>
        <div class="col-md-3"><strong>Atendimento:</strong><br>{{ $semana->dt_hr_atendimento ? $semana->dt_hr_atendimento->format('d/m/Y H:i') : '-' }}</div>
        <div class="col-md-3"><strong>Finalização:</strong><br>{{ $semana->dt_hr_finalizacao ? $semana->dt_hr_finalizacao->format('d/m/Y H:i') : '-' }}</div>
        <div class="col-md-3"><strong>Atendido por:</strong><br>{{ $semana->userAplicacao?->nome ?? '-' }}</div>
        @if ($semana->autorizador)
          <div class="col-md-3"><strong>Autorizado sem pagamento:</strong><br>{{ $semana->autorizador->nome }}</div>
        @endif
      </div>

      @if ($semana->obs)
        <div class="alert alert-light border py-2"><strong>Obs da semana:</strong> {{ $semana->obs }}</div>
      @endif

      @if ($semana->prescricao->paciente?->obs)
        <div class="alert alert-info py-2"><strong>Obs do Paciente:</strong> {{ $semana->prescricao->paciente->obs }}</div>
      @endif

      @if ($semana->prescricao->obs)
        <div class="alert alert-warning py-2"><strong>Obs da prescrição:</strong> {{ $semana->prescricao->obs }}</div>
      @endif
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0">Anexos / Receita</h6>
    </div>
    <div class="card-body">
      @forelse ($semana->prescricao->anexos as $anexo)
        <a href="{{ route('procedimentos.anexos.visualizar', $anexo->id) }}" target="_blank" class="btn btn-sm btn-outline-primary me-1 mb-1">
          <i class="ri-file-line me-1"></i>{{ $anexo->nm_anexo }}
        </a>
      @empty
        <span class="text-muted small">Nenhum anexo.</span>
      @endforelse
    </div>
  </div>

  <form action="{{ route('enfermagem.aplicacao.lancar', $semana->id) }}" method="post" id="formulario_aplicacao">
    @csrf
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0">Aplicações — Bipagem dos Códigos de Barras</h6>
        @empty($visualizar)
          <button type="button" class="btn btn-label-primary btn-sm" id="botao_abrir_frasco">
            <i class="ri-medication-line me-1"></i>Abrir Frasco
          </button>
        @endempty
      </div>
      <div class="card-body">
        <div class="form-floating form-floating-outline mb-4">
          <textarea {{ $visualizar ? 'readonly' : '' }} class="form-control" id="obs_aplicacao" name="obs_aplicacao" style="height: 80px;"></textarea>
          <label for="obs_aplicacao">Obs da aplicação:</label>
        </div>

        <div class="table-responsive">
          @if ($visualizar)
            {{-- ===== VISUALIZAÇÃO (somente leitura — todas as informações) ===== --}}
            <table class="table table-sm table-bordered">
              <thead class="table-light">
                <tr>
                  <th>Medicação</th>
                  <th>Tipo</th>
                  <th style="width: 70px;">Qtd</th>
                  <th>Chegada</th>
                  <th>Atendimento</th>
                  <th>Código de Barras</th>
                  <th>Lote</th>
                  <th>Vencimento</th>
                  <th>Aplicado em</th>
                  <th>Aplicado por</th>
                  <th>Situação</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($grupos as $grupo)
                  @php $med = $grupo['med']; @endphp
                  <tr>
                    <td class="align-middle">
                      @if ($med->is_soro)
                        <span class="badge bg-label-info">Soro</span>
                      @elseif ($med->combo_id)
                        <span class="badge bg-label-warning">Combo</span>
                      @endif
                      {{ $grupo['nome'] }}
                    </td>
                    <td class="align-middle">{{ collect($grupo['linhas'])->pluck('medicamento.tipo')->unique()->implode(' / ') ?: ($med->is_soro ? 'Soro' : '-') }}</td>
                    <td class="align-middle text-center">{{ $med->quantidade }}</td>
                    <td class="align-middle">
                      @if ($med->dt_hr_chegada)
                        {{ $med->dt_hr_chegada->format('d/m/Y H:i') }}
                      @else
                        <span class="text-muted small">—</span>
                      @endif
                    </td>
                    <td class="align-middle">
                      @if ($med->dt_hr_atendimento)
                        {{ $med->dt_hr_atendimento->format('d/m/Y H:i') }}
                      @else
                        <span class="text-muted small">—</span>
                      @endif
                    </td>
                    <td class="align-middle">{!! $med->situacao === 'Aplicada' && $med->lotes->isNotEmpty() ? $med->codigosDisplay() : '<span class="text-muted small">—</span>' !!}</td>
                    <td class="align-middle">{!! $med->situacao === 'Aplicada' && $med->lotes->isNotEmpty() ? $med->lotesDisplay() : '<span class="text-muted small">—</span>' !!}</td>
                    <td class="align-middle">{!! $med->situacao === 'Aplicada' && $med->lotes->isNotEmpty() ? $med->vencimentosDisplay() : '<span class="text-muted small">—</span>' !!}</td>
                    <td class="align-middle">
                      @if ($med->aplicado_em)
                        {{ $med->aplicado_em->format('d/m/Y H:i') }}
                      @else
                        <span class="text-muted small">—</span>
                      @endif
                    </td>
                    <td class="align-middle">{{ $med->userAplicacao?->nome ?? '—' }}</td>
                    <td class="align-middle">
                      @if ($med->situacao === 'Aplicada')
                        <span class="badge bg-label-success">Aplicada</span>
                      @elseif ($med->situacao === 'Pendente')
                        <span class="badge bg-label-warning">Pendente</span>
                      @else
                        <span class="badge bg-label-secondary">{{ $med->situacao }}</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="11" class="text-center text-muted py-4">Nenhuma medicação nesta semana.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          @else
            {{-- ===== BIPAGEM (edição) ===== --}}
            <table class="table table-sm table-bordered">
              <thead class="table-light">
                <tr>
                  <th style="width: 70px;">Pendente</th>
                  <th>Medicação</th>
                  <th>Tipo</th>
                  <th style="width: 70px;">Qtd</th>
                  <th>Medicamento</th>
                  <th>Código</th>
                  <th>Lote</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($grupos as $grupo)
                  @php
                    $med = $grupo['med'];
                    $emAberto = in_array($med->situacao, ['Aberta', 'Pendente']);
                  @endphp
                  <tr>
                    <td class="text-center align-middle">
                      @if ($emAberto && empty($visualizar))
                        <input class="form-check-input" type="checkbox" value="Sim"
                               name="controle_pendente_{{ $med->id }}" id="controle_pendente_{{ $med->id }}"
                               data-med-key="{{ $med->id }}"
                               onclick="controle_pendente({{ $med->id }}, this)">
                      @elseif ($emAberto && $visualizar)
                        <span class="text-muted small">—</span>
                      @endif
                    </td>
                    <td class="align-middle">
                      @if ($med->is_soro)
                        <span class="badge bg-label-info">Soro</span>
                      @elseif ($med->combo_id)
                        <span class="badge bg-label-warning">Combo</span>
                      @endif
                      {{ $grupo['nome'] }}
                      @if ($med->situacao === 'Aplicada')
                        <span class="text-success ms-1"><i class="ri-check-double-line"></i></span>
                      @elseif ($med->situacao === 'Pendente')
                        <span class="badge bg-label-warning ms-1">Pendente</span>
                      @endif
                    </td>
                    <td class="align-middle">
                      @php
                        $tipos = collect($grupo['linhas'])->pluck('medicamento.tipo')->unique()->implode(' / ');
                      @endphp
                      {{ $tipos ?: ($med->is_soro ? 'Soro' : '-') }}
                    </td>
                    <td class="align-middle text-center">{{ $med->quantidade }}</td>
                    <td class="align-middle">
                      @forelse ($grupo['linhas'] as $linha)
                        <div class="mb-1">{{ $linha['medicamento']->nome }}</div>
                      @empty
                        <span class="text-muted small">—</span>
                      @endforelse
                    </td>

                    @if ($emAberto && ! $visualizar && $grupo['linhas'])
                      <td class="align-middle">
                        @foreach ($grupo['linhas'] as $linha)
                          @php $medicamento = $linha['medicamento']; @endphp
                          <div class="d-flex align-items-center gap-1 mb-1">
                            @if ($medicamento->tipo === 'Ampola')
                              <div class="d-flex flex-column gap-1 flex-grow-1">
                                <input type="text" required autocomplete="off"
                                       name="codigo_barras_{{ $linha['key'] }}" id="codigo_barras_{{ $linha['key'] }}"
                                       class="form-control form-control-sm" placeholder="Código de barras"
                                       data-med-key="{{ $med->id }}"
                                       onblur="buscaLote(this, '{{ $linha['key'] }}', {{ $medicamento->id }}, {{ $user->clinica_id }}, {{ $linha['quantidade'] }})">
                                @if ($linha['quantidade'] < 1)
                                  <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox"
                                           name="ampola_inteira_{{ $linha['key'] }}" id="ampola_inteira_{{ $linha['key'] }}"
                                           value="Sim" checked>
                                    <label class="form-check-label small" for="ampola_inteira_{{ $linha['key'] }}">
                                      Retirar 1 ampola inteira (meia dose)
                                    </label>
                                  </div>
                                @endif
                              </div>
                            @elseif ($medicamento->tipo === 'Vasilhame')
                              <input type="text" required autocomplete="off"
                                     name="codigo_barras_{{ $linha['key'] }}" id="codigo_barras_{{ $linha['key'] }}"
                                     class="form-control form-control-sm" placeholder="Código de barras"
                                     data-med-key="{{ $med->id }}"
                                     onblur="buscaFrasco(this, '{{ $linha['key'] }}', {{ $medicamento->id }}, {{ $user->clinica_id }}, {{ $linha['quantidade'] }})">
                              <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" title="Aplicação com 2 códigos"
                                      onclick="abreModal2Codigo('{{ $linha['key'] }}', {{ $medicamento->id }}, {{ $user->clinica_id }}, {{ $linha['quantidade'] }})">
                                <i class="ri-number-2"></i>
                              </button>
                            @else
                              <input type="text" autocomplete="off"
                                     name="codigo_barras_{{ $linha['key'] }}" id="codigo_barras_{{ $linha['key'] }}"
                                     class="form-control form-control-sm" placeholder="Código"
                                     data-med-key="{{ $med->id }}">
                            @endif
                          </div>
                        @endforeach
                        <div id="hidden_2codigo_area"></div>
                      </td>
                      <td class="align-middle">
                        @foreach ($grupo['linhas'] as $linha)
                          <div class="mb-1">
                            <input type="hidden" name="lote_{{ $linha['key'] }}" id="lote_{{ $linha['key'] }}" value="">
                            <span id="lote_display_{{ $linha['key'] }}" class="badge bg-label-secondary fs-6 fw-normal">—</span>
                          </div>
                        @endforeach
                      </td>
                    @else
                      <td class="align-middle">
                        @if ($med->situacao === 'Aplicada' && $med->lotes->isNotEmpty())
                          <span class="small">{!! $med->codigosDisplay() !!}</span>
                        @else
                          <span class="text-muted small">—</span>
                        @endif
                      </td>
                      <td class="align-middle">
                        @if ($med->situacao === 'Aplicada' && $med->lotes->isNotEmpty())
                          <span class="small">{!! $med->lotesDisplay() !!}</span>
                        @else
                          <span class="text-muted small">—</span>
                        @endif
                      </td>
                    @endif
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">Nenhuma medicação para aplicação nesta semana.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          @endif
        </div>

        <div class="d-flex flex-column align-items-end gap-2 mt-3">
          @empty($visualizar)
            @if ($semana->prescricao->anexos->isNotEmpty())
              <a href="{{ route('procedimentos.anexos.visualizar', $semana->prescricao->anexos->first()->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="ri-eye-line me-1"></i>Visualizar prescrição (pedido médico)
              </a>
            @endif
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="confirmacao_pedido_medico" id="confirmacao_pedido_medico" value="1">
              <label class="form-check-label" for="confirmacao_pedido_medico">Confirmei e verifiquei o pedido médico</label>
            </div>
            <button type="button" class="btn btn-primary" id="btn_registrar_aplicacao">
              <i class="ri-check-double-line me-1"></i>Registrar Aplicação
            </button>
          @endempty
        </div>
      </div>
    </div>
  </form>

  {{-- ============ MODAL ABRIR FRASCO ============ --}}
  <div class="modal fade" id="modal_abrir_frasco" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <form action="{{ route('enfermagem.aplicacao.abrir_frasco') }}" method="post" class="modal-content">
        @csrf
        <input type="hidden" name="semana_id" value="{{ $semana->id }}">
        <div class="modal-header">
          <h5 class="modal-title">Abrir Frasco</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3 align-items-end">
            <div class="col-md-5">
              <label class="form-label">Medicamento (Vasilhame)</label>
              <select id="modal_medicamento_id" name="medicamento_id" class="form-select" required>
                <option value="">Opções</option>
                @foreach ($vasilhames as $vasilhame)
                  <option value="{{ $vasilhame->id }}">{{ $vasilhame->nome }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">Código de Barras</label>
              <select id="modal_codigo_barras" name="codigo_barras" class="form-select" required>
                <option value="">Opções</option>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-secondary w-100">Abrir</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- ============ MODAL 2 CÓDIGOS ============ --}}
  <div class="modal fade" id="modal_2_codigo" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Aplicação com 2 Códigos</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
  <input type="hidden" id="modal2_key">
          <input type="hidden" id="modal2_medicamento_id">
          <input type="hidden" id="modal2_total">
          <table class="table table-sm">
            <thead class="table-light">
              <tr>
                <th>Quantidade</th>
                <th>Código</th>
                <th>Lote</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input type="text" id="modal2_quantidade_1" class="form-control" autocomplete="off"></td>
                <td><input type="text" id="modal2_codigo_1" class="form-control" autocomplete="off" onblur="buscaFrasco2(1)"></td>
                <td>
                  <input type="hidden" id="modal2_lote_1" value="">
                  <span id="modal2_lote_display_1" class="badge bg-label-secondary fs-6 fw-normal">—</span>
                </td>
              </tr>
              <tr>
                <td><input type="text" id="modal2_quantidade_2" class="form-control" autocomplete="off"></td>
                <td><input type="text" id="modal2_codigo_2" class="form-control" autocomplete="off" onblur="buscaFrasco2(2)"></td>
                <td>
                  <input type="hidden" id="modal2_lote_2" value="">
                  <span id="modal2_lote_display_2" class="badge bg-label-secondary fs-6 fw-normal">—</span>
                </td>
              </tr>
            </tbody>
          </table>
          <button type="button" class="btn btn-secondary" id="modal2_salvar">Salvar</button>
        </div>
      </div>
    </div>
  </div>

  {{-- ============ MODAL CONFIRMAÇÃO ============ --}}
  <div class="modal fade" id="modal_confirmar_aplicacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirmar Aplicação</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="conteudo_confirmacao"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-success" id="btn_confirmar_submissao">Confirmar e Salvar</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    let modal2Codigo = null;
    let modalConfirmar = null;

    // ============ ALERTA FERRO (abertura da aplicação) ============
    @if ($temFerro)
    $(function () {
      if (typeof Swal !== 'undefined' && Swal.fire) {
        Swal.fire({
          icon: 'warning',
          title: '🚨 FERRO CADASTRADO',
          html: '<b style="font-size:1.2rem;">Este paciente tem <span style="color:#dc3545;">FERRO</span> cadastrado.</b><br><span class="small">Deseja confirmar a aplicação?</span>',
          showCancelButton: true,
          confirmButtonText: 'Sim, confirmar',
          cancelButtonText: 'Voltar',
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d'
        }).then((result) => {
          if (!result.isConfirmed) {
            window.location.href = "{{ route('enfermagem.index') }}";
          }
        });
      } else {
        if (!confirm('Este paciente tem FERRO cadastrado. Deseja confirmar a aplicação?')) {
          window.location.href = "{{ route('enfermagem.index') }}";
        }
      }
    });
    @endif

    // ============ CONFIG ============
    // Bloqueio de digitação manual (para uso com leitora). Igual à V1: DESLIGADO por
    // padrão (a leitora quebrou e as enfermeiras digitam manualmente). Ligue se voltar a usar leitor.
    const BLOQUEAR_DIGITACAO_MANUAL = false;

    // ============ LEITOR (Enter = blur) ============
    $(document).on('keydown', 'input[id^="codigo_barras_"]', function(e) {
      if (e.keyCode === 13) {
        e.preventDefault();
        $(this).blur();
        return false;
      }
    });

    // Seleciona o conteúdo do código ao focar (facilita rebipar outro código)
    $(document).on('focus', 'input[id^="codigo_barras_"]', function() {
      this.select();
    });

    // ============ KEEP-ALIVE (10 min) — evita expirar o CSRF ============
    setInterval(() => {
      fetch("{{ route('enfermagem.aplicacao.keep_alive') }}", {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        cache: 'no-store'
      }).catch(() => {});
    }, 10 * 60 * 1000);

    // ============ BLOQUEIO DE DIGITAÇÃO MANUAL (opcional) ============
    if (BLOQUEAR_DIGITACAO_MANUAL) {
      let lastKeyTime = Date.now();
      $(document).on('keydown', 'input[id^="codigo_barras_"]', function(e) {
        // Permitir teclas de controle: Backspace, Tab, Enter, Setas
        if ([8, 9, 13, 37, 38, 39, 40].includes(e.keyCode)) {
          return;
        }
        const currentTime = Date.now();
        const timeDiff = currentTime - lastKeyTime;
        // Permitir a primeira tecla se o campo estiver vazio
        if ($(this).val().length === 0) {
          lastKeyTime = currentTime;
          return;
        }
        // Se o tempo entre teclas for maior que 50ms, bloqueia (digitação manual)
        if (timeDiff > 50) {
          e.preventDefault();
          return false;
        }
        lastKeyTime = currentTime;
      });
      // Bloqueio de Cópia e Colar
      $(document).on('paste drop', 'input[id^="codigo_barras_"]', function(e) {
        e.preventDefault();
        return false;
      });
    }

    // ============ MENSAGENS ============
    function msgErro(mensagem) {
      if (typeof Swal !== 'undefined' && Swal.fire) {
        Swal.fire({ icon: 'error', title: 'Atenção', text: mensagem });
      } else {
        alert(mensagem);
      }
    }

    function msgVencido(mensagem) {
      if (typeof Swal !== 'undefined' && Swal.fire) {
        Swal.fire({
          icon: 'error',
          title: '🚨 MEDICAMENTO VENCIDO!',
          html: '<b style="font-size: 1.3rem; color: #dc3545;">' + mensagem + '</b>',
          showConfirmButton: true,
          confirmButtonText: 'OK',
          confirmButtonColor: '#dc3545'
        });
      } else {
        alert(mensagem);
      }
    }

    // ============ FEEDBACK VISUAL ============
    function resetLote(key) {
      $('#lote_' + key).val('');
      $('#lote_display_' + key).text('—').removeClass('bg-success text-white').addClass('bg-label-secondary').removeAttr('title');
    }

    function limparCampos(key, foco) {
      $('#codigo_barras_' + key).removeClass('is-valid is-invalid').val('');
      resetLote(key);
      if (foco) {
        $('#codigo_barras_' + key).focus();
      }
    }

    function sucessoLote(key, lote, saldo) {
      $('#lote_' + key).val(lote);
      const span = $('#lote_display_' + key);
      span.text(lote).removeClass('bg-label-secondary').addClass('bg-success text-white');
      if (typeof saldo !== 'undefined') {
        span.attr('title', 'Saldo disponível: ' + saldo);
      }
      $('#codigo_barras_' + key).removeClass('is-invalid').addClass('is-valid');
    }

    // Guarda de validações em andamento (evita bip duplo/sobreposto)
    const validando = new Set();

    // ============ CONTROLE PENDENTE ============
    function controle_pendente(medKey, elem) {
      $(`input[data-med-key="${medKey}"]`).each(function() {
        if (!$(this).attr('id')) return;
        const isCodigo = $(this).attr('id').startsWith('codigo_barras_');
        const isLote = $(this).attr('id').startsWith('lote_');
        if (!isCodigo && !isLote) return;
        if (elem.checked) {
          $(this).removeAttr('required').removeClass('is-valid is-invalid');
        } else {
          $(this).attr('required', 'required');
        }
      });
    }

    // ============ AMPOLA ============
    function buscaLote(elem, key, medicamentoId, clinicaId, quantidade) {
      if (!elem.value) {
        resetLote(key);
        return;
      }
      if (validando.has(key)) return; // evita bip duplo
      validando.add(key);

      $.getJSON("{{ route('enfermagem.aplicacao.buscar_lote') }}", {
        codigo: elem.value,
        medicamento_id: medicamentoId,
        clinica_id: clinicaId,
        quantidade: quantidade
      }, function(json) {
        validando.delete(key);
        if (json.controle === 'vencido') {
          msgVencido(json.mensagem);
          limparCampos(key, true);
        } else if (json.controle === 'true') {
          sucessoLote(key, json.lote, json.saldo);
        } else if (json.controle === 'insuficiente') {
          msgErro(json.mensagem || 'Quantidade em estoque insuficiente!');
          limparCampos(key, true);
        } else {
          msgErro(json.mensagem || 'Código de barras inválido para este medicamento!');
          limparCampos(key, true);
        }
      }).fail(function() {
        validando.delete(key);
        msgErro('Falha ao consultar o código de barras.');
      });
    }

    // ============ VASILHAME (frasco aberto) ============
    function buscaFrasco(elem, key, medicamentoId, clinicaId, quantidade) {
      if (!elem.value) {
        resetLote(key);
        return;
      }
      if (validando.has(key)) return;
      validando.add(key);

      $.getJSON("{{ route('enfermagem.aplicacao.buscar_frasco') }}", {
        codigo: elem.value,
        medicamento_id: medicamentoId,
        clinica_id: clinicaId,
        quantidade: quantidade
      }, function(json) {
        validando.delete(key);
        if (json.controle === 'vencido') {
          msgVencido(json.mensagem);
          limparCampos(key, true);
        } else if (json.controle === 'true') {
          sucessoLote(key, json.lote, json.saldo);
        } else {
          msgErro(json.mensagem || 'Código de barras inválido!');
          limparCampos(key, true);
        }
      }).fail(function() {
        validando.delete(key);
        msgErro('Falha ao consultar o código de barras.');
      });
    }

    // ============ 2 CÓDIGOS ============
    function abreModal2Codigo(key, medicamentoId, clinicaId, quantidadeTotal) {
      $('#modal2_key').val(key);
      $('#modal2_medicamento_id').val(medicamentoId);
      $('#modal2_total').val(quantidadeTotal);
      $('#modal2_quantidade_1, #modal2_quantidade_2, #modal2_codigo_1, #modal2_codigo_2')
        .val('').removeClass('is-valid is-invalid');
      $('#modal2_lote_1, #modal2_lote_2').val('');
      $('#modal2_lote_display_1, #modal2_lote_display_2')
        .text('—').removeClass('bg-success text-white').addClass('bg-label-secondary');
      modal2Codigo = new bootstrap.Modal(document.getElementById('modal_2_codigo'));
      modal2Codigo.show();
    }

    function buscaFrasco2(numero) {
      const codigo = $('#modal2_codigo_' + numero).val();
      const quantidade = $('#modal2_quantidade_' + numero).val();
      const medicamentoId = $('#modal2_medicamento_id').val();
      if (!codigo || !quantidade) return;
      $.getJSON("{{ route('enfermagem.aplicacao.buscar_frasco') }}", {
        codigo: codigo,
        medicamento_id: medicamentoId,
        clinica_id: {{ $user->clinica_id }},
        quantidade: quantidade
      }, function(json) {
        if (json.controle === 'true') {
          $('#modal2_lote_' + numero).val(json.lote);
          $('#modal2_lote_display_' + numero).text(json.lote).removeClass('bg-label-secondary').addClass('bg-success text-white');
        } else if (json.controle === 'vencido') {
          msgVencido(json.mensagem);
          $('#modal2_codigo_' + numero).val('').focus();
          $('#modal2_lote_' + numero).val('');
          $('#modal2_lote_display_' + numero).text('—').removeClass('bg-success text-white').addClass('bg-label-secondary');
        } else {
          msgErro(json.mensagem || 'Código de barras inválido!');
          $('#modal2_codigo_' + numero).val('').focus();
          $('#modal2_lote_' + numero).val('');
          $('#modal2_lote_display_' + numero).text('—').removeClass('bg-success text-white').addClass('bg-label-secondary');
        }
      });
    }

    $('#modal2_salvar').on('click', function() {
      const key = $('#modal2_key').val();
      const qtd1 = parseFloat($('#modal2_quantidade_1').val());
      const qtd2 = parseFloat($('#modal2_quantidade_2').val());
      const cod1 = $('#modal2_codigo_1').val();
      const cod2 = $('#modal2_codigo_2').val();
      const lote1 = $('#modal2_lote_1').val();
      const lote2 = $('#modal2_lote_2').val();
      const total = parseFloat($('#modal2_total').val());

      if (!qtd1 || !qtd2 || !cod1 || !cod2 || !lote1 || !lote2) {
        alert('É necessário preencher todos os campos');
        return;
      }

      // Validação extra: a soma dos dois frascos deve bater com a quantidade da aplicação
      if (total && Math.abs((qtd1 + qtd2) - total) > 0.0001) {
        alert('A soma das quantidades (' + (qtd1 + qtd2) + ') deve ser igual à quantidade da aplicação (' + total + ').');
        return;
      }

      $('#hidden_2codigo_area').append(
        `<input type="hidden" name="controle_med_${key}" value="2_codigo">` +
        `<input type="hidden" name="quant_med_1_${key}" value="${qtd1}">` +
        `<input type="hidden" name="quant_med_2_${key}" value="${qtd2}">` +
        `<input type="hidden" name="cod_med_1_${key}" value="${cod1}">` +
        `<input type="hidden" name="cod_med_2_${key}" value="${cod2}">` +
        `<input type="hidden" name="lote_med_1_${key}" value="${lote1}">` +
        `<input type="hidden" name="lote_med_2_${key}" value="${lote2}">`
      );

      $('#codigo_barras_' + key).val('2 códigos').prop('readonly', true).removeAttr('required')
        .removeClass('is-invalid').addClass('is-valid');
      $('#lote_' + key).val(lote1 + ' / ' + lote2);
      $('#lote_display_' + key).text(lote1 + ' / ' + lote2).removeClass('bg-label-secondary').addClass('bg-success text-white');

      modal2Codigo.hide();
    });

    // ============ ABRIR FRASCO ============
    $('#botao_abrir_frasco').on('click', function() {
      $('#modal_codigo_barras').html('<option value="">Opções</option>');
      new bootstrap.Modal(document.getElementById('modal_abrir_frasco')).show();
    });

    $('#modal_medicamento_id').on('change', function() {
      const medicamentoId = $(this).val();
      $('#modal_codigo_barras').html('<option value="">Carregando...</option>');
      if (!medicamentoId) return;
      $.getJSON("{{ route('enfermagem.aplicacao.lotes_medicamento') }}", {
        medicamento_id: medicamentoId
      }, function(json) {
        let html = '<option value="">Opções</option>';
        (json.codigos || []).forEach(c => {
          html += `<option value="${c.codigo_barras}" data-lote="${c.lote}">${c.codigo_barras} — Lote ${c.lote} (Saldo: ${c.saldo})${c.dt_vencimento ? ' — Venc: ' + c.dt_vencimento : ''}</option>`;
        });
        $('#modal_codigo_barras').html(html);
      });
    });

    // ============ CONFIRMAÇÃO ============
    $('#btn_registrar_aplicacao').on('click', function() {
      if (!$('#confirmacao_pedido_medico').prop('checked')) {
        msgErro('Marque a confirmação de que verificou o pedido médico (abra o anexo da prescrição) antes de registrar a aplicação.');
        return;
      }
      if (!$('#formulario_aplicacao')[0].checkValidity()) {
        $('#formulario_aplicacao')[0].reportValidity();
        return;
      }

      let linhas = [];
      $('input[name^="controle_pendente_"]').each(function() {
        if (!this.checked) {
          const medKey = $(this).data('med-key');
          $(`input[id^="codigo_barras_"][data-med-key="${medKey}"]`).each(function() {
            const id = $(this).attr('id').replace('codigo_barras_', '');
            linhas.push({
              nome: $(this).closest('tr').find('td').eq(1).text().trim(),
              quantidade: $(this).closest('tr').find('td').eq(3).text().trim(),
              codigo: $(this).val() || '-',
              lote: $('#lote_' + id).val() || '-'
            });
          });
        }
      });

      let html = '<div class="alert alert-primary fw-semibold mb-3">Você confirma a aplicação de <strong>' + (linhas.length ? [...new Set(linhas.map(l => l.nome).filter(n => n))].join(', ') : '—') + '</strong> conforme prescrição médica?</div>';
      html += '<table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th>Medicação</th><th>Qtd</th><th>Código</th><th>Lote</th></tr></thead><tbody>';
      if (linhas.length === 0) {
        html += '<tr><td colspan="4" class="text-center text-muted">Nenhuma medicação para aplicar nesta semana.</td></tr>';
      } else {
        linhas.forEach(l => {
          html += `<tr><td>${l.nome}</td><td>${l.quantidade}</td><td>${l.codigo}</td><td>${l.lote}</td></tr>`;
        });
      }
      html += '</tbody></table>';

      $('#conteudo_confirmacao').html(html);
      modalConfirmar = new bootstrap.Modal(document.getElementById('modal_confirmar_aplicacao'));
      modalConfirmar.show();
    });

    $('#btn_confirmar_submissao').on('click', function() {
      $('#formulario_aplicacao').submit();
    });
  </script>
@endsection
