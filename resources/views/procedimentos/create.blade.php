@extends('layouts.sistema')

@section('title', 'Novo Procedimento - Instituto GL')

@section('styles')
  <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/flatpickr/flatpickr.css') }}" />
  <link rel="stylesheet" href="{{ asset('templates/assets/vendor/libs/select2/select2.css') }}" />
  <style>
    .modal-semanas-list {
      display: flex;
      flex-direction: column;
      gap: .35rem;
      max-height: 260px;
      overflow-y: auto;
      border: 1px solid #e4e6ed;
      border-radius: .5rem;
      padding: .65rem;
    }
    .modal-semanas-list .form-check {
      display: flex;
      align-items: center;
      gap: .5rem;
      padding: .35rem .5rem;
      margin: 0;
      border-radius: .375rem;
    }
    .modal-semanas-list .form-check .form-check-input {
      float: none;
      margin: 0;
      flex: 0 0 auto;
    }
    .modal-semanas-list .form-check:hover {
      background: #f5f6f8;
    }
    #marcar-todas-semanas {
      float: none;
      margin: 0;
    }
    .qty-btn {
      padding: 0;
      width: 26px;
      height: 26px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .qty-btn i {
      font-size: 13px;
    }
    #semanas-container .table-sm th,
    #semanas-container .table-sm td {
      padding: .15rem .4rem;
      vertical-align: middle;
    }
    #semanas-container .table-sm {
      font-size: .8125rem !important;
    }
    #semanas-container .table-sm td,
    #semanas-container .table-sm th {
      line-height: 1.2 !important;
    }
    .item-remove-btn {
      width: 28px !important;
      height: 28px !important;
      padding: 0 !important;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .qty-stepper {
      display: inline-flex;
      align-items: center;
      border: 1px solid #d9dee3;
      border-radius: .375rem;
      overflow: hidden;
      background: #fff;
    }
    .qty-step-btn {
      width: 24px;
      height: 26px;
      border: 0;
      background: transparent;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: #566a7f;
    }
    .qty-step-btn:hover { background: #f1f2f4; }
    .qty-step-btn i { font-size: 12px; }
    .qty-step-input {
      width: 50px;
      height: 26px;
      border: 0;
      border-left: 1px solid #e4e6ed;
      border-right: 1px solid #e4e6ed;
      text-align: center;
      font-size: .8125rem;
      color: #566a7f;
      -moz-appearance: textfield;
      appearance: textfield;
    }
    .qty-step-input::-webkit-outer-spin-button,
    .qty-step-input::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
  </style>
@endsection

@section('content')
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h4 class="fw-semibold mb-0">Novo Procedimento</h4>
    <a href="{{ route('procedimentos.index') }}" class="btn btn-sm btn-outline-secondary">
      <i class="ri-arrow-left-line me-1"></i>Voltar
    </a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $erro)
          <li>{{ $erro }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Alerta evidente de FERRO (dupla checagem da recepção) --}}
  <div id="ferro-alert" class="d-none alert alert-danger border-danger d-flex align-items-start gap-3 mb-4">
    <i class="ri-alert-line ri-24px flex-shrink-0"></i>
    <div class="flex-grow-1">
      <div class="fw-semibold text-uppercase mb-1" style="letter-spacing:.5px;">Atenção — Medicamento FERRO</div>
      <div class="small mb-2">Este cadastro contém <strong>FERRO</strong>. Realize a <strong>dupla checagem</strong> da prescrição antes de aprovar o cadastro.</div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="confirmar-ferro">
        <label class="form-check-label" for="confirmar-ferro">
          Confirmei a dupla checagem do <strong>FERRO</strong> com a prescrição do médico.
        </label>
      </div>
    </div>
  </div>

  <form id="form-procedimento" method="POST" action="{{ route('procedimentos.store') }}" enctype="multipart/form-data">
    @csrf

        {{-- ===== CARD 1: DADOS GERAIS (inclui anexos) ===== --}}
        <div class="card border shadow-none mb-4">
          <div class="card-header py-3">
            <h6 class="mb-0 fw-semibold"><i class="ri-user-heart-line me-2 text-primary"></i>Dados Gerais</h6>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Paciente <span class="text-danger">*</span></label>
                <select id="paciente_id" name="paciente_id" class="form-select"></select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Médico <span class="text-danger">*</span></label>
                <select id="medico" name="medico" class="form-select" required>
                  <option value=""></option>
                </select>
                <span id="medico-erro" class="text-danger small d-none"><i class="ri-error-warning-line me-1"></i>Não foi possível carregar os médicos da Feegow.</span>
              </div>
              <div class="col-md-2">
                <x-select-clinica required />
              </div>
              <div class="col-md-3">
                <label class="form-label">Tipo de Atendimento <span class="text-danger">*</span></label>
                <select name="tipo_atendimento" class="form-select" required>
                  <option value="">Selecione</option>
                  <option value="Consulta Tratamento" @selected(old('tipo_atendimento') === 'Consulta Tratamento')>Consulta Tratamento</option>
                  <option value="Retorno" @selected(old('tipo_atendimento') === 'Retorno')>Retorno</option>
                  <option value="Consulta Nova" @selected(old('tipo_atendimento') === 'Consulta Nova')>Consulta Nova</option>
                  <option value="Coleta/Bio" @selected(old('tipo_atendimento') === 'Coleta/Bio')>Coleta/Bio</option>
                  <option value="Implante" @selected(old('tipo_atendimento') === 'Implante')>Implante</option>
                </select>
              </div>
            </div>
            <div class="row g-3 mt-1">
              <div class="col-md-4">
                <label class="form-label">Agendamento</label>
                <input type="text" name="agendamento" class="form-control" value="{{ old('agendamento') }}" />
              </div>
              <div class="col-md-8">
                <label class="form-label">Observações</label>
                <textarea name="obs" class="form-control" rows="2">{{ old('obs') }}</textarea>
              </div>
            </div>

            {{-- Observação do paciente (destaque ao selecionar) --}}
            <div id="obs-paciente-card" class="d-none rounded-3 p-3 mt-3 mb-1 position-relative overflow-hidden"
                 style="background-color:#fffbef; box-shadow:0 0 0 1px rgba(217,164,6,.18);">
              <span class="position-absolute top-0 start-0 bottom-0" style="width:4px;background:#d9a406;"></span>
              <div class="d-flex align-items-start gap-3">
                <span class="badge bg-label-warning rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;">
                  <i class="ri-alert-line ri-18px"></i>
                </span>
                <div class="flex-grow-1" style="min-width:0;">
                  <div class="small text-uppercase fw-semibold text-muted mb-1" style="letter-spacing:.5px;">Observação do paciente</div>
                  <div id="obs-paciente-texto" class="small text-body"></div>
                </div>
              </div>
            </div>

            <hr class="my-4" />

            {{-- Anexo da prescrição (dentro de Dados Gerais) --}}
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Anexo da Prescrição</label>
                <input type="file" name="anexo_prescricao" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" />
                <span class="text-muted small"><i class="ri-information-line me-1"></i>Obrigatório quando houver medicação que gera aplicação.</span>
              </div>
            </div>
          </div>
        </div>

        {{-- ===== CARD 2: SEMANAS DO TRATAMENTO ===== --}}
        <div class="card border shadow-none mb-4">
          <div class="card-header py-3">
            <h6 class="mb-0 fw-semibold"><i class="ri-calendar-check-line me-2 text-primary"></i>Semanas do Tratamento</h6>
          </div>
          <div class="card-body">

            <div class="row g-3 align-items-end">
              <div class="col-md-3">
                <label class="form-label">Data inicial <span class="text-danger">*</span></label>
                <input type="text" id="data_prescricao" name="data_prescricao" class="form-control" required value="{{ old('data_prescricao') ?? now()->format('d/m/Y') }}" />
              </div>
              <div class="col-md-2">
                <label class="form-label">Quantidade de semanas <span class="text-danger">*</span></label>
                <input type="number" id="qt_semanas" name="qt_semanas" class="form-control" min="1" max="104" required value="{{ old('qt_semanas', 6) }}" />
              </div>
              <div class="col-md-2">
                <label class="form-label">Periodicidade (dias) <span class="text-danger">*</span></label>
                <input type="number" id="periodicidade_dias" name="periodicidade_dias" class="form-control" min="1" max="90" required value="{{ old('periodicidade_dias', 7) }}" />
              </div>
              <div class="col-md-2">
                <button type="button" id="btn-gerar-semanas" class="btn btn-outline-primary w-100">
                  <i class="ri-calendar-check-line me-1"></i>Gerar Semanas
                </button>
              </div>
              <div class="col-md-3">
                <span class="text-muted small"><i class="ri-information-line me-1"></i>As semanas são geradas automaticamente (intervalo padrão de 7 dias, podendo ser alterado). Adicione medicamento/combo/soro e marque em quais semanas ele será aplicado.</span>
              </div>
            </div>

            <hr class="my-4" />

            <div id="semanas-container" class="row g-2"></div>
          </div>
        </div>

        {{-- ===== CARD: QUANTITATIVO DE MEDICAMENTOS ===== --}}
        <div class="card border shadow-none mb-4">
          <div class="card-header py-3">
            <h6 class="mb-0 fw-semibold"><i class="ri-stack-line me-2 text-primary"></i>Quantitativo de Medicamentos</h6>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-2">
                <thead class="table-light">
                  <tr>
                    <th>Medicamento</th>
                    <th class="text-end" style="width: 140px;">Quantidade</th>
                  </tr>
                </thead>
                <tbody id="quantitativo-tbody">
                  <tr>
                    <td colspan="2" class="text-center text-muted py-3">Nenhum medicamento adicionado.</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="table-light">
                    <th class="text-end">Total</th>
                    <th class="text-end" id="quantitativo-total">0,00</th>
                  </tr>
                </tfoot>
              </table>
            </div>
            <span class="text-muted small"><i class="ri-information-line me-1"></i>Soma a quantidade de cada medicamento em todas as semanas; combos e soros contam também as quantidades dos componentes internos.</span>
          </div>
        </div>

        {{-- ===== CARD 3: FINANCEIRO ===== --}}
        <div class="card border shadow-none mb-4">
          <div class="card-header py-3">
            <h6 class="mb-0 fw-semibold"><i class="ri-money-dollar-circle-line me-2 text-primary"></i>Financeiro</h6>
          </div>
          <div class="card-body">
            <div id="financeiro-vazio" class="text-muted small">
              <i class="ri-information-line me-1"></i>O financeiro é montado automaticamente ao gerar as semanas e adicionar medicações com aplicação.
            </div>

            <div id="financeiro-section" style="display: none;">
              <div class="row g-3 mb-3">
                <div class="col-md-3">
                  <label class="form-label">Valor do tratamento (R$)</label>
                  <input type="text" id="valor_tratamento" name="valor_tratamento" class="form-control text-end" value="R$ 0,00" inputmode="decimal" />
                </div>
                <div class="col-md-3">
                  <label class="form-label">Crédito em aberto (R$) <i class="ri-information-line text-muted" title="Valor que o paciente tem de outra prescrição (pagou e não usou)"></i></label>
                  <input type="text" id="credito_em_aberto" name="credito_em_aberto" class="form-control text-end" value="R$ 0,00" inputmode="decimal" />
                </div>
                <div class="col-md-3 align-self-end">
                  <span class="text-muted">Nº de parcelas: <strong id="qt-parcelas-info">0</strong></span>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table table-sm table-bordered">
                  <thead>
                    <tr>
                      <th style="width:70px;">Parcela</th>
                      <th style="width:80px;">Semana</th>
                      <th style="width:150px;">Vencimento</th>
                      <th class="text-end" style="width:170px;">Valor</th>
                      <th>Obs</th>
                    </tr>
                  </thead>
                  <tbody id="parcelas-tbody"></tbody>
                </table>
              </div>
              <span class="text-muted small"><i class="ri-information-line me-1"></i>O valor das parcelas é calculado automaticamente (valor ÷ nº de parcelas) e pode ser ajustado antes de salvar. Parcelas = semanas com aplicação.</span>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <input type="hidden" name="confirmar_ferro" id="confirmar-ferro-hidden" value="0">
          <button type="submit" class="btn btn-primary">
            <i class="ri-check-line me-1"></i>Salvar Procedimento
          </button>
          <a href="{{ route('procedimentos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>

  {{-- Modal: confirmação do cadastro --}}
  <div class="modal fade" id="modal-confirmacao" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="ri-checkbox-circle-line me-2 text-primary"></i>Confirmar Cadastro do Procedimento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small mb-3">Revise o resumo das semanas e do financeiro abaixo antes de confirmar o cadastro.</p>

          {{-- Resumo geral --}}
          <div class="row g-2 mb-3 small">
            <div class="col-md-4"><span class="text-muted">Paciente:</span> <strong id="conf-paciente">-</strong></div>
            <div class="col-md-4"><span class="text-muted">Médico:</span> <strong id="conf-medico">-</strong></div>
            <div class="col-md-4"><span class="text-muted">Clínica:</span> <strong id="conf-clinica">-</strong></div>
            <div class="col-md-4"><span class="text-muted">Tipo de Atendimento:</span> <strong id="conf-tipo">-</strong></div>
            <div class="col-md-4"><span class="text-muted">Data inicial:</span> <strong id="conf-data">-</strong></div>
            <div class="col-md-4"><span class="text-muted">Semanas:</span> <strong id="conf-semanas-total">0</strong></div>
          </div>

          <h6 class="fw-semibold">Semanas / Aplicações</h6>
          <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered">
              <thead class="table-light">
                <tr>
                  <th style="width:90px;">Semana</th>
                  <th style="width:110px;">Data</th>
                  <th>Medicações</th>
                  <th style="width:130px;">Situação</th>
                </tr>
              </thead>
              <tbody id="conf-semanas-tbody"></tbody>
            </table>
          </div>

          <h6 class="fw-semibold">Financeiro</h6>
          <div class="table-responsive mb-2">
            <table class="table table-sm table-bordered">
              <thead class="table-light">
                <tr>
                  <th style="width:70px;">Parcela</th>
                  <th style="width:90px;">Semana</th>
                  <th style="width:130px;">Vencimento</th>
                  <th class="text-end" style="width:140px;">Valor</th>
                  <th>Obs</th>
                </tr>
              </thead>
              <tbody id="conf-parcelas-tbody"></tbody>
            </table>
          </div>
          <div class="d-flex justify-content-end gap-3 small flex-wrap">
            <span>Valor do tratamento: <strong id="conf-valor-tratamento">R$ 0,00</strong></span>
            <span>Crédito em aberto: <strong id="conf-credito">R$ 0,00</strong></span>
            <span>Total a receber: <strong id="conf-total-receber">R$ 0,00</strong></span>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="confirmar-cadastro-check" />
            <label class="form-check-label fw-semibold" for="confirmar-cadastro-check">
              Confirmo o cadastro deste procedimento
            </label>
          </div>
          <div>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Voltar e corrigir</button>
            <button type="button" id="confirmar-salvar-btn" class="btn btn-primary" disabled>
              <i class="ri-check-double-line me-1"></i>Confirmar e Salvar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Modal: adicionar medicamento/combo/soro --}}
  <div class="modal fade" id="modal-item" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Adicionar medicamento / combo / soro</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tipo</label>
            <select id="modal-tipo" class="form-select">
              <option value="medicamento">Medicamento</option>
              <option value="combo">Combo</option>
              <option value="soro">Soro</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Item <span class="text-danger">*</span></label>
            <select id="modal-item-id" class="form-select"></select>
          </div>
          <div class="mb-3">
            <label class="form-label">Quantidade</label>
            <input type="number" id="modal-item-qtd" class="form-control" value="1" min="0.1" step="any" />
          </div>
          <div class="mb-2">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label mb-0">Semanas que receberão este item</label>
              <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="marcar-todas-semanas" />
                <label class="form-check-label" for="marcar-todas-semanas">Selecionar todas</label>
              </div>
            </div>
            <div id="modal-semanas" class="modal-semanas-list"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" id="modal-add" class="btn btn-primary">Adicionar</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Modal: adicionar medicamento/combo/soro por PERIODICIDADE (sem escolher semana) --}}
  <div class="modal fade" id="modal-item-periodicidade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Adicionar por Periodicidade</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tipo</label>
            <select id="modal-per-tipo" class="form-select">
              <option value="medicamento">Medicamento</option>
              <option value="combo">Combo</option>
              <option value="soro">Soro</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Item <span class="text-danger">*</span></label>
            <select id="modal-per-item-id" class="form-select"></select>
          </div>
          <div class="mb-3">
            <label class="form-label">Quantidade</label>
            <input type="number" id="modal-per-qtd" class="form-control" value="1" min="0.1" step="any" />
          </div>
          <div class="mb-2">
            <label class="form-label">Periodicidade de aplicação</label>
            <select id="modal-per-periodicidade" class="form-select"></select>
            <span class="text-muted small mt-1 d-block"><i class="ri-information-line me-1"></i>O item será aplicado a partir da semana <span id="per-semana-inicial">1</span> e depois a cada <span id="per-periodicidade-info">—</span>, nas semanas existentes.</span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" id="modal-per-add" class="btn btn-primary">Adicionar</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script src="{{ asset('templates/assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
  <script src="{{ asset('templates/assets/vendor/libs/select2/select2.js') }}"></script>
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
      function round2(n) { return Math.round((n + Number.EPSILON) * 100) / 100; }
      function round1(n) { return Math.round(n * 10) / 10; }
      function moedaReal(n) { return 'R$ ' + formatarMoeda(n); }
      function isoDaSemana(num) {
        const w = state.weeks.find(x => x.num === num);
        if (!w) return '';
        const p = w.data.split('/');
        return p[2] + '-' + p[1] + '-' + p[0];
      }
      function aplicarMascaraMoeda($sel) {
        $sel.on('input', function () {
          const digits = this.value.replace(/\D/g, '').slice(0, 14);
          if (!digits) { this.value = ''; return; }
          const cents = parseInt(digits, 10);
          const v = (cents / 100).toFixed(2);
          const partes = v.split('.');
          const intFmt = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
          this.value = 'R$ ' + intFmt + ',' + partes[1];
        });
      }
      function formatarData(dt) {
        const dd = String(dt.getDate()).padStart(2, '0');
        const mm = String(dt.getMonth() + 1).padStart(2, '0');
        return dd + '/' + mm + '/' + dt.getFullYear();
      }

      // ---------- dados de itens (geram aplicação?) ----------
      @php
        $medsJson = $medicamentos->map(fn ($m) => [
            'id' => $m->id,
            'nome' => $m->nome,
            'gera' => strtolower(trim((string) $m->aplicacao)) === 'sim',
            'anexo' => strtolower(trim((string) $m->aplicacao)) === 'sim' && $m->tipo !== 'Procedimento',
            'ferro' => $m->ehFerro(),
        ]);
        $combosJson = $combos->map(fn ($c) => [
            'id' => $c->id,
            'nome' => $c->nome,
            'gera' => (bool) $c->gera_aplicacao,
            'anexo' => $c->medicamentos->contains(fn ($cm) => $cm->medicamento && strtolower(trim((string) $cm->medicamento->aplicacao)) === 'sim' && $cm->medicamento->tipo !== 'Procedimento'),
            'componentes' => $c->medicamentos->filter(fn ($cm) => $cm->medicamento)->map(fn ($cm) => ['nome' => $cm->medicamento->nome, 'quantidade' => (float) $cm->quantidade])->values(),
        ]);
        $sorosJson = $soros->map(fn ($s) => [
            'id' => $s->id,
            'nome' => $s->nome,
            'gera' => (bool) $s->gera_aplicacao,
            'anexo' => $s->medicamentos->contains(fn ($sm) => $sm->medicamento && strtolower(trim((string) $sm->medicamento->aplicacao)) === 'sim' && $sm->medicamento->tipo !== 'Procedimento'),
            'componentes' => $s->medicamentos->filter(fn ($sm) => $sm->medicamento)->map(fn ($sm) => ['nome' => $sm->medicamento->nome, 'quantidade' => (float) $sm->quantidade])->values(),
        ]);
      @endphp
      const MEDS = @json($medsJson);
      const COMBOS = @json($combosJson);
      const SOROS = @json($sorosJson);

      let state = { weeks: [], items: [] };

      // ---------- alerta FERRO (recepção) ----------
      function temFerro() {
        return state.items.some(it => it.tipo === 'medicamento' && it.ferro);
      }
      function atualizarAlertaFerro() {
        const tem = temFerro();
        $('#ferro-alert').toggleClass('d-none', !tem);
        if (!tem) {
          $('#confirmar-ferro').prop('checked', false);
        }
      }
      function validarFerro() {
        if (temFerro() && !$('#confirmar-ferro').prop('checked')) {
          alert('Confirme a dupla checagem do FERRO antes de salvar o cadastro.');
          return false;
        }
        $('#confirmar-ferro-hidden').val($('#confirmar-ferro').prop('checked') ? '1' : '0');
        return true;
      }

      // ---------- flatpickr ----------
      flatpickr('#data_prescricao', {
        locale: {
          firstDayOfWeek: 0,
          weekdays: { shorthand: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'], longhand: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'] },
          months: { shorthand: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'], longhand: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'] }
        },
        dateFormat: 'd/m/Y',
        allowInput: true,
        minDate: 'today'
      });

      // ---------- Select2 paciente ----------
      $('#paciente_id').select2({
        placeholder: 'Buscar paciente por nome/CPF...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
          url: "{{ route('pacientes.buscar') }}",
          dataType: 'json',
          delay: 250,
          data: p => ({ q: p.term }),
          processResults: d => ({ results: d })
        }
      });

      // Observação do paciente: busca e mostra em destaque ao selecionar
      function carregarObsPaciente() {
        const id = $('#paciente_id').val();
        if (!id) { $('#obs-paciente-card').addClass('d-none'); return; }
        const url = "{{ route('pacientes.obs.get', ['paciente' => '__ID__']) }}".replace('__ID__', id);
        $.get(url, function (r) {
          const obs = (r.obs || '').trim();
          if (obs) {
            $('#obs-paciente-texto').text(obs);
            $('#obs-paciente-card').removeClass('d-none');
          } else {
            $('#obs-paciente-card').addClass('d-none');
          }
        }).fail(function () {
          $('#obs-paciente-card').addClass('d-none');
        });
      }
      $('#paciente_id').on('select2:select change', carregarObsPaciente);
      $('#paciente_id').on('select2:clear', function () {
        $('#obs-paciente-card').addClass('d-none');
      });

      // ---------- Select2 médico (da Feegow, carregado via AJAX p/ não travar a página) ----------
      const medicoOld = @json(old('medico'));
      const $medicoSel = $('#medico');
      $medicoSel.select2({
        placeholder: 'Selecione o médico...',
        allowClear: true
      });
      fetch("{{ route('procedimentos.medicos') }}")
        .then(r => r.json())
        .then(lista => {
          lista.forEach(m => $medicoSel.append(new Option(m.text, m.id, false, false)));
          if (medicoOld) { $medicoSel.val(medicoOld).trigger('change'); }
          $medicoSel.trigger('change');
        })
        .catch(function () {
          $('#medico-erro').removeClass('d-none');
        });

      // ---------- gerar semanas ----------
      $('#btn-gerar-semanas').on('click', function () {
        const dtStr = $('#data_prescricao').val();
        const qt = parseInt($('#qt_semanas').val(), 10);
        const periodicidade = parseInt($('#periodicidade_dias').val(), 10);
        if (!dtStr) { alert('Informe a data inicial.'); return; }
        if (!qt || qt < 1) { alert('Informe a quantidade de semanas.'); return; }
        if (!periodicidade || periodicidade < 1) { alert('Informe a periodicidade (dias entre as semanas).'); return; }
        const partes = dtStr.split('/');
        const base = new Date(Number(partes[2]), Number(partes[1]) - 1, Number(partes[0]));
        state.weeks = [];
        for (let i = 0; i < qt; i++) {
          const dt = new Date(base);
          dt.setDate(base.getDate() + i * periodicidade);
          state.weeks.push({ num: i + 1, data: formatarData(dt) });
        }
        state.items = [];
        renderSemanas();
        atualizarFinanceiro();
        calcularQuantitativo();
      });

      function getItemAplicacao(item) {
        if (item.tipo === 'medicamento') return MEDS.find(x => x.id === item.id)?.gera || false;
        if (item.tipo === 'combo') return COMBOS.find(x => x.id === item.id)?.gera || false;
        if (item.tipo === 'soro') return SOROS.find(x => x.id === item.id)?.gera || false;
        return false;
      }

      function semanasComAplicacao() {
        const set = new Set();
        state.items.forEach(item => {
          if (getItemAplicacao(item)) Object.keys(item.qtds).forEach(w => set.add(parseInt(w, 10)));
        });
        return set;
      }

      // Semanas que exigem anexo (aplicação de medicamento NÃO-Procedimento)
      function getItemRequerAnexo(item) {
        if (item.tipo === 'medicamento') return MEDS.find(x => x.id === item.id)?.anexo || false;
        if (item.tipo === 'combo') return COMBOS.find(x => x.id === item.id)?.anexo || false;
        if (item.tipo === 'soro') return SOROS.find(x => x.id === item.id)?.anexo || false;
        return false;
      }
      function semanasRequerAnexo() {
        const set = new Set();
        state.items.forEach(item => {
          if (getItemRequerAnexo(item)) Object.keys(item.qtds).forEach(w => set.add(parseInt(w, 10)));
        });
        return set;
      }

      // ---------- quantitativo total de medicamentos ----------
      function calcularQuantitativo() {
        const total = {};
        const ordem = [];
        function somar(nome, qtd) {
          if (!(nome in total)) {
            total[nome] = 0;
            ordem.push(nome);
          }
          total[nome] += qtd;
        }

        state.items.forEach(item => {
          let totalSemanas = 0;
          Object.values(item.qtds).forEach(q => { totalSemanas += q; });
          if (!totalSemanas) return;

          if (item.tipo === 'medicamento') {
            const med = MEDS.find(x => x.id === item.id);
            if (med) somar(med.nome, totalSemanas);
          } else if (item.tipo === 'combo') {
            const c = COMBOS.find(x => x.id === item.id);
            if (c && c.componentes) c.componentes.forEach(comp => somar(comp.nome, comp.quantidade * totalSemanas));
          } else if (item.tipo === 'soro') {
            const s = SOROS.find(x => x.id === item.id);
            if (s && s.componentes) s.componentes.forEach(comp => somar(comp.nome, comp.quantidade * totalSemanas));
          }
        });

        const nomes = Object.keys(total);
        if (!nomes.length) {
          $('#quantitativo-tbody').html('<tr><td colspan="2" class="text-center text-muted py-3">Nenhum medicamento adicionado.</td></tr>');
          $('#quantitativo-total').text('0,00');
          return;
        }
        nomes.sort((a, b) => total[b] - total[a]);

        let linhas = '';
        let grandTotal = 0;
        nomes.forEach(nome => {
          const qtd = Math.round(total[nome] * 100) / 100;
          grandTotal += qtd;
          linhas += '<tr><td>' + nome + '</td><td class="text-end fw-semibold">'
            + Number(qtd).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td></tr>';
        });
        $('#quantitativo-tbody').html(linhas);
        $('#quantitativo-total').text(Number(Math.round(grandTotal * 100) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      }

      function renderSemanas() {
        const $c = $('#semanas-container');
        $c.empty();
        if (!state.weeks.length) return;
        state.weeks.forEach(wk => {
          const itens = state.items.filter(it => it.qtds[wk.num] !== undefined);
          const temApl = itens.some(it => getItemAplicacao(it));
          const card = $(`
            <div class="col-12">
              <div class="card border shadow-none mb-2" style="background-color:#f6f8fa;">
                <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge ${temApl ? 'bg-primary' : 'bg-secondary'} rounded-circle d-inline-flex align-items-center justify-content-center fw-semibold" style="width:28px;height:28px;">${wk.num}</span>
                    <span class="fw-semibold">Semana ${wk.num}</span>
                    <span class="text-muted small ms-1"><i class="ri-calendar-line me-1"></i>${wk.data}</span>
                  </div>
                  ${temApl
                    ? '<span class="badge bg-label-success"><i class="ri-checkbox-circle-line me-1"></i>Com aplicação</span>'
                    : '<span class="badge bg-label-secondary"><i class="ri-pause-circle-line me-1"></i>Sem aplicação</span>'}
                </div>
                <div class="card-body py-2 px-3">
                  ${itens.length
                    ? `<table class="table table-sm table-hover align-middle mb-2" style="max-width:600px;">
                         <thead>
                           <tr>
                             <th class="text-center" style="width:120px;">Quantidade</th>
                             <th>Medicação</th>
                             <th class="text-end" style="width:36px;"></th>
                           </tr>
                         </thead>
                         <tbody id="tb-semana-${wk.num}"></tbody>
                       </table>`
                    : '<span class="text-muted small"><i class="ri-information-line me-1"></i>Sem aplicação (pausa)</span>'}
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirModalItem(${wk.num})">
                      <i class="ri-add-line me-1"></i>Adicionar medicamento / combo / soro
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirModalPeriodicidade(${wk.num})" title="Adiciona partindo desta semana e depois a cada N dias">
                      <i class="ri-calendar-repeat-line me-1"></i>Adicionar por periodicidade
                    </button>
                  </div>
                </div>
              </div>
            </div>`);
          $c.append(card);

          state.items.forEach((it, idx) => {
            if (it.qtds[wk.num] === undefined) return;
            const tipoBadge = it.tipo === 'combo'
              ? '<span class="badge bg-label-warning me-1">Combo</span>'
              : it.tipo === 'soro'
                ? '<span class="badge bg-label-info me-1">Soro</span>'
                : '';
            $(`#tb-semana-${wk.num}`).append(`
              <tr>
                <td class="text-center" style="width:120px;">
                  <div class="qty-stepper">
                    <button type="button" class="qty-step-btn btn-minus" data-item="${idx}" data-semana="${wk.num}"><i class="ri-subtract-line"></i></button>
                    <input type="number" class="qty-step-input item-qtd" data-item="${idx}" data-semana="${wk.num}" value="${it.qtds[wk.num]}" min="0.1" step="any" />
                    <button type="button" class="qty-step-btn btn-plus" data-item="${idx}" data-semana="${wk.num}"><i class="ri-add-line"></i></button>
                  </div>
                </td>
                <td>${tipoBadge}${it.nome}</td>
                <td class="text-end">
                  <button type="button" class="btn btn-icon btn-outline-danger item-remove item-remove-btn" data-item="${idx}" data-semana="${wk.num}" title="Remover desta semana"><i class="ri-close-line"></i></button>
                </td>
              </tr>`);
          });
        });
      }

      // ---- editar quantidade / remover item por semana (delegado) ----
      $('#semanas-container').on('input', '.item-qtd', function () {
        const idx = parseInt($(this).data('item'), 10);
        const wk = parseInt($(this).data('semana'), 10);
        const v = parseFloat($(this).val());
        if (!isNaN(v) && v > 0) state.items[idx].qtds[wk] = v;
      });
      $('#semanas-container').on('click', '.btn-minus', function () {
        const input = $(this).siblings('.item-qtd');
        const v = Math.max(0.1, round1((parseFloat(input.val()) || 1) - 0.1));
        input.val(v);
        const idx = parseInt(input.data('item'), 10);
        const wk = parseInt(input.data('semana'), 10);
        state.items[idx].qtds[wk] = v;
      });
      $('#semanas-container').on('click', '.btn-plus', function () {
        const input = $(this).siblings('.item-qtd');
        const v = round1((parseFloat(input.val()) || 1) + 0.1);
        input.val(v);
        const idx = parseInt(input.data('item'), 10);
        const wk = parseInt(input.data('semana'), 10);
        state.items[idx].qtds[wk] = v;
      });
      $('#semanas-container').on('click', '.item-remove', function () {
        const idx = parseInt($(this).data('item'), 10);
        const wk = parseInt($(this).data('semana'), 10);
        delete state.items[idx].qtds[wk];
        if (Object.keys(state.items[idx].qtds).length === 0) {
          state.items.splice(idx, 1);
        }
        renderSemanas();
        atualizarFinanceiro();
        atualizarAlertaFerro();
        calcularQuantitativo();
      });

      // ---------- modal item ----------
      let modalWeek = null;
      window.abrirModalItem = function (wk) {
        modalWeek = wk;
        $('#modal-tipo').val('medicamento').trigger('change');
        $('#modal-item-qtd').val('1');
        $('#marcar-todas-semanas').prop('checked', false);
        const $sw = $('#modal-semanas');
        $sw.empty();
        state.weeks.forEach(w => {
          const checked = w.num === wk;
          $sw.append(
            `<div class="form-check">
               <input class="form-check-input semana-check" type="checkbox" value="${w.num}" id="semana-chk-${w.num}" ${checked ? 'checked' : ''}>
               <label class="form-check-label" for="semana-chk-${w.num}">Semana ${w.num} (${w.data})</label>
             </div>`
          );
        });
        $('#modal-item').modal('show');
      };

      // marcar/desmarcar todas as semanas
      $('#marcar-todas-semanas').on('change', function () {
        $('.semana-check').prop('checked', this.checked);
      });
      $('#modal-semanas').on('change', '.semana-check', function () {
        const total = $('.semana-check').length;
        const marcadas = $('.semana-check:checked').length;
        $('#marcar-todas-semanas').prop('checked', total > 0 && marcadas === total);
      });

      $('#modal-tipo').on('change', function () {
        const tipo = this.value;
        const $sel = $('#modal-item-id');
        $sel.empty();
        const lista = tipo === 'medicamento' ? MEDS : tipo === 'combo' ? COMBOS : SOROS;
        lista.forEach(x => $sel.append(new Option(x.nome, x.id)));
      });

      $('#modal-add').on('click', function () {
        const tipo = $('#modal-tipo').val();
        const id = parseInt($('#modal-item-id').val(), 10);
        const qtd = parseFloat($('#modal-item-qtd').val()) || 1;
        if (!id) { alert('Escolha o item.'); return; }
        const semanas = [];
        $('.semana-check:checked').each(function () { semanas.push(parseInt(this.value, 10)); });
        if (!semanas.length) { alert('Marque pelo menos uma semana.'); return; }
        const nome = $('#modal-item-id option:selected').text();
        const qtds = {};
        semanas.forEach(w => { qtds[w] = qtd; });
        state.items.push({ tipo, id, nome, qtds });
        $('#modal-item').modal('hide');
        renderSemanas();
        atualizarFinanceiro();
        atualizarAlertaFerro();
        calcularQuantitativo();
      });

      // ---------- modal item por PERIODICIDADE (sem escolher semana) ----------
      function opcoesPeriodicidade() {
        const base = parseInt($('#periodicidade_dias').val(), 10) || 7;
        const $sel = $('#modal-per-periodicidade');
        $sel.empty();
        for (let k = 1; k <= 10; k++) {
          const dias = base * k;
          $sel.append(new Option(`A cada ${dias} dias (${k} semana${k > 1 ? 's' : ''})`, dias));
        }
        atualizarInfoPeriodicidade();
      }
      function atualizarInfoPeriodicidade() {
        const opt = $('#modal-per-periodicidade option:selected');
        $('#per-periodicidade-info').text(opt.text() || '—');
      }

      let modalPerSemanaInicial = 1;
      window.abrirModalPeriodicidade = function (semanaInicial) {
        if (!state.weeks.length) {
          alert('Gere as semanas do tratamento primeiro.');
          return;
        }
        modalPerSemanaInicial = semanaInicial && semanaInicial > 0 ? semanaInicial : 1;
        $('#per-semana-inicial').text(modalPerSemanaInicial);
        $('#modal-per-tipo').val('medicamento').trigger('change');
        $('#modal-per-qtd').val('1');
        opcoesPeriodicidade();
        $('#modal-item-periodicidade').modal('show');
      };

      $('#modal-per-tipo').on('change', function () {
        const tipo = this.value;
        const $sel = $('#modal-per-item-id');
        $sel.empty();
        const lista = tipo === 'medicamento' ? MEDS : tipo === 'combo' ? COMBOS : SOROS;
        lista.forEach(x => $sel.append(new Option(x.nome, x.id)));
      });

      $('#modal-per-periodicidade').on('change', atualizarInfoPeriodicidade);

      $('#modal-per-add').on('click', function () {
        const tipo = $('#modal-per-tipo').val();
        const id = parseInt($('#modal-per-item-id').val(), 10);
        const qtd = parseFloat($('#modal-per-qtd').val()) || 1;
        const cadencia = parseInt($('#modal-per-periodicidade').val(), 10);
        if (!id) { alert('Escolha o item.'); return; }
        if (!cadencia || cadencia < 1) { alert('Escolha a periodicidade de aplicação.'); return; }

        // Semanas em que o item será aplicado: da semana inicial e depois a cada `cadencia` dias
        const base = parseInt($('#periodicidade_dias').val(), 10) || 7;
        const inicio = modalPerSemanaInicial;
        const semanas = [];
        state.weeks.forEach(w => {
          if (w.num < inicio) return;
          const offset = (w.num - inicio) * base;
          if (offset % cadencia === 0) semanas.push(w.num);
        });
        if (!semanas.length) { alert('Nenhuma semana se enquadra na periodicidade escolhida.'); return; }

        const nome = $('#modal-per-item-id option:selected').text();
        const qtds = {};
        semanas.forEach(w => { qtds[w] = qtd; });
        state.items.push({ tipo, id, nome, qtds });
        $('#modal-item-periodicidade').modal('hide');
        renderSemanas();
        atualizarFinanceiro();
        atualizarAlertaFerro();
        calcularQuantitativo();
      });

      // ---------- financeiro ----------
      function atualizarFinanceiro() {
        const set = semanasComAplicacao();
        const qtParcelas = set.size;
        $('#qt-parcelas-info').text(qtParcelas);
        if (!qtParcelas) {
          $('#financeiro-section').hide();
          $('#financeiro-vazio').show();
          return;
        }
        $('#financeiro-section').show();
        $('#financeiro-vazio').hide();

        const semanasOrd = state.weeks.filter(w => set.has(w.num)).map(w => w.num);
        const total = parseMoeda($('#valor_tratamento').val());
        const credito = parseMoeda($('#credito_em_aberto').val());
        const totalAParcelar = Math.max(0, total - credito);
        const base = qtParcelas ? totalAParcelar / qtParcelas : 0;

        const $tbody = $('#parcelas-tbody');
        $tbody.empty();
        semanasOrd.forEach((wk, i) => {
          const val = i === qtParcelas - 1 ? round2(totalAParcelar - base * (qtParcelas - 1)) : round2(base);
          $tbody.append(`
            <tr data-semana="${wk}">
              <td>${i + 1}</td>
              <td>Semana ${wk}</td>
              <td>
                <input type="date" class="form-control form-control-sm parcela-venc" value="${isoDaSemana(wk)}" />
              </td>
              <td class="text-end">
                <input type="text" class="form-control form-control-sm text-end parcela-valor" value="${formatarMoeda(val)}" inputmode="decimal" />
              </td>
              <td>
                <input type="text" class="form-control form-control-sm parcela-obs" placeholder="Obs da parcela (opcional)" />
              </td>
            </tr>`);
        });
      }

      // Máscara de moeda (R$) no valor do tratamento e crédito em aberto
      aplicarMascaraMoeda($('#valor_tratamento'));
      aplicarMascaraMoeda($('#credito_em_aberto'));

      $('#valor_tratamento, #credito_em_aberto').on('input', atualizarFinanceiro);

      $('#parcelas-tbody').on('input', '.parcela-valor', function () {
        let soma = 0;
        $('.parcela-valor').each(function () { soma += parseMoeda($(this).val()); });
        $('#valor_tratamento').val(moedaReal(soma));
      });

      // ---------- confirmação do cadastro ----------
      function serializarFormulario($f) {
        // normaliza a máscara de moeda p/ o servidor (formato 1.234,56)
        $('#valor_tratamento, #credito_em_aberto').each(function () {
          $(this).val(formatarMoeda(parseMoeda($(this).val())));
        });
        $('.parcela-valor').each(function () {
          $(this).val(formatarMoeda(parseMoeda($(this).val())));
        });
        $('.hidden-item').remove();
        $('.hidden-parcela').remove();
        state.items.forEach((it, i) => {
          $f.append(`<input type="hidden" name="item_tipo[]" value="${it.tipo}" class="hidden-item">`);
          $f.append(`<input type="hidden" name="item_id[]" value="${it.id}" class="hidden-item">`);
          Object.keys(it.qtds).forEach(w => {
            $f.append(`<input type="hidden" name="item_qtd[${i}][${w}]" value="${it.qtds[w]}" class="hidden-item">`);
            $f.append(`<input type="hidden" name="item_semanas[${i}][]" value="${w}" class="hidden-item">`);
          });
        });
        $('#parcelas-tbody tr').each(function () {
          const $tr = $(this);
          const val = $tr.find('.parcela-valor').val();
          const venc = $tr.find('.parcela-venc').val();
          const obs = $tr.find('.parcela-obs').val();
          $f.append(`<input type="hidden" name="valor_parcela[]" value="${val}" class="hidden-parcela">`);
          $f.append(`<input type="hidden" name="dt_vencimento[]" value="${venc}" class="hidden-parcela">`);
          $f.append(`<input type="hidden" name="obs_parcela[]" value="${obs}" class="hidden-parcela">`);
        });
      }

      function montarResumoConfirmacao() {
        $('#conf-paciente').text($('#paciente_id option:selected').text() || '-');
        $('#conf-medico').text($('#medico option:selected').text() || '-');
        $('#conf-clinica').text($('select[name="clinica_id"] option:selected').text() || '-');
        $('#conf-tipo').text($('select[name="tipo_atendimento"] option:selected').text() || '-');
        $('#conf-data').text($('#data_prescricao').val() || '-');
        $('#conf-semanas-total').text(state.weeks.length);

        let semanasHtml = '';
        state.weeks.forEach(wk => {
          const itens = state.items.filter(it => it.qtds[wk.num] !== undefined);
          if (itens.length === 0) {
            semanasHtml += `<tr><td>Semana ${wk.num}</td><td>${wk.data}</td><td class="text-muted"><i class="ri-pause-circle-line me-1"></i>Sem aplicação (pausa)</td><td><span class="badge bg-label-secondary">Sem aplicação</span></td></tr>`;
          } else {
            const meds = itens.map(it => `${it.nome} <span class="badge bg-label-primary ms-1">× ${it.qtds[wk.num]}</span>`).join('<br>');
            const temApl = itens.some(it => getItemAplicacao(it));
            semanasHtml += `<tr><td>Semana ${wk.num}</td><td>${wk.data}</td><td>${meds}</td><td>${temApl ? '<span class="badge bg-label-success">Com aplicação</span>' : '<span class="badge bg-label-secondary">Sem aplicação</span>'}</td></tr>`;
          }
        });
        $('#conf-semanas-tbody').html(semanasHtml);

        let parcelasHtml = '';
        let totalParcelas = 0;
        $('#parcelas-tbody tr').each(function () {
          const $tr = $(this);
          const nr = $tr.find('td').eq(0).text();
          const semana = $tr.find('td').eq(1).text();
          const venc = $tr.find('.parcela-venc').val();
          const valor = parseMoeda($tr.find('.parcela-valor').val());
          const obs = $tr.find('.parcela-obs').val();
          totalParcelas += valor;
          parcelasHtml += `<tr><td>${nr}</td><td>${semana}</td><td>${venc || '-'}</td><td class="text-end">${formatarMoeda(valor)}</td><td>${obs || '-'}</td></tr>`;
        });
        $('#conf-parcelas-tbody').html(parcelasHtml || '<tr><td colspan="5" class="text-center text-muted">Sem parcelas (nenhuma semana com aplicação)</td></tr>');

        $('#conf-valor-tratamento').text(moedaReal(parseMoeda($('#valor_tratamento').val())));
        $('#conf-credito').text(moedaReal(parseMoeda($('#credito_em_aberto').val())));
        $('#conf-total-receber').text(moedaReal(totalParcelas));
      }

      $('#confirmar-cadastro-check').on('change', function () {
        $('#confirmar-salvar-btn').prop('disabled', !this.checked);
      });

      $('#confirmar-salvar-btn').on('click', function () {
        if (! $('#confirmar-cadastro-check').prop('checked')) return;
        $('#modal-confirmacao').modal('hide');
        document.getElementById('form-procedimento').submit();
      });

      // Intercepta o submit: valida, serializa e mostra o modal de confirmação
      $('#form-procedimento').on('submit', function (e) {
        if (!state.weeks.length) {
          alert('Gere as semanas do tratamento antes de salvar.');
          e.preventDefault();
          return false;
        }

        // Anexo obrigatório quando houver aplicação de medicamento NÃO-Procedimento (mesma regra do servidor)
        const temAnexoObrigatorio = semanasRequerAnexo().size > 0;
        const anexo = document.querySelector('input[name="anexo_prescricao"]');
        if (temAnexoObrigatorio && anexo && anexo.files.length === 0) {
          alert('Anexe a prescrição do médico (obrigatório quando há aplicação de medicamento).');
          e.preventDefault();
          return false;
        }

        // Dupla checagem obrigatória quando há FERRO
        if (!validarFerro()) {
          e.preventDefault();
          return false;
        }

        e.preventDefault();
        serializarFormulario($(this));
        montarResumoConfirmacao();
        $('#confirmar-cadastro-check').prop('checked', false).trigger('change');
        $('#modal-confirmacao').modal('show');
        return false;
      });
    });
  </script>
@endsection
