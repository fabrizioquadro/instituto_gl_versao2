@extends('layouts.sistema')

@section('title', 'Adicionar Semanas - Instituto GL')

@section('styles')
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
    .item-remove-btn {
      width: 28px !important;
      height: 28px !important;
      padding: 0 !important;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
  </style>
@endsection

@section('content')
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h4 class="fw-semibold mb-0">Adicionar Semanas</h4>
    <a href="{{ route('procedimentos.show', $prescricao->id) }}" class="btn btn-sm btn-outline-secondary">
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

  <form id="form-adicionar-semanas" method="POST" action="{{ route('procedimentos.semana.adicionar', $prescricao->id) }}" enctype="multipart/form-data">
    @csrf

    {{-- ===== PRESCRIÇÃO ===== --}}
    <div class="card border shadow-none mb-4">
      <div class="card-header py-3">
        <h6 class="mb-0 fw-semibold"><i class="ri-stethoscope-line me-2 text-primary"></i>Prescrição</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Paciente</label><div class="fw-semibold">{{ $prescricao->paciente?->nm_paciente ?? '-' }}</div></div>
          <div class="col-md-4"><label class="form-label">Médico</label><div class="fw-semibold">{{ $prescricao->medico }}</div></div>
          <div class="col-md-4"><label class="form-label">Clínica</label><div class="fw-semibold">{{ $prescricao->clinica?->nome ?? '-' }}</div></div>
          <div class="col-md-4"><label class="form-label">Semanas atuais</label><div class="fw-semibold">{{ $prescricao->qt_semanas }}</div></div>
          <div class="col-md-4"><label class="form-label">Valor do tratamento</label><div class="fw-semibold">R$ {{ valorDbForm($prescricao->valor_tratamento) }}</div></div>
          <div class="col-md-4"><label class="form-label">Situação</label><div class="fw-semibold">{{ $prescricao->situacao }}</div></div>
        </div>
      </div>
    </div>

    {{-- ===== SEMANAS A ADICIONAR ===== --}}
    <div class="card border shadow-none mb-4">
      <div class="card-header py-3">
        <h6 class="mb-0 fw-semibold"><i class="ri-calendar-check-line me-2 text-primary"></i>Semanas a Adicionar</h6>
      </div>
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Quantidade de semanas <span class="text-danger">*</span></label>
            <input type="number" id="qt_semanas_adicionar" name="qt_semanas_adicionar" class="form-control" min="1" max="52" required value="{{ old('qt_semanas_adicionar', 1) }}" />
          </div>
          <div class="col-md-3">
            <button type="button" id="btn-gerar-semanas" class="btn btn-outline-primary w-100">
              <i class="ri-calendar-check-line me-1"></i>Gerar Semanas
            </button>
          </div>
          <div class="col-md-6">
            <span class="text-muted small"><i class="ri-information-line me-1"></i>As semanas são geradas automaticamente (+7 dias cada) ao final da prescrição. Adicione medicamento/combo/soro e marque em quais semanas ele será aplicado.</span>
          </div>
        </div>

        <hr class="my-4" />

        <div id="semanas-container" class="row g-2"></div>
      </div>
    </div>

    {{-- ===== ANEXO + VALOR ===== --}}
    <div class="card border shadow-none mb-4">
      <div class="card-header py-3">
        <h6 class="mb-0 fw-semibold"><i class="ri-money-dollar-circle-line me-2 text-primary"></i>Anexo e Valor</h6>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Anexo da Prescrição (opcional)</label>
            <input type="file" name="anexo_prescricao" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Valor a ratear nas novas parcelas (R$)</label>
            <input type="text" id="valor_rateio" name="valor" class="form-control text-end" placeholder="0,00" inputmode="decimal" />
            <span class="text-muted small"><i class="ri-information-line me-1"></i>Se informado, é dividido igualmente entre as novas parcelas (a última fica com a diferença).</span>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-4">
      <button type="submit" class="btn btn-primary"><i class="ri-check-line me-1"></i>Adicionar Semanas</button>
      <a href="{{ route('procedimentos.show', $prescricao->id) }}" class="btn btn-outline-secondary">Cancelar</a>
    </div>
  </form>

  {{-- ===== MODAL: ADICIONAR ITEM ===== --}}
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
            <div id="modal-semanas" class="modal-semanas-list">
              <span class="text-muted small">Gere as semanas primeiro.</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" id="modal-add" class="btn btn-primary">Adicionar</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    $(function () {
      // ============ DADOS ============
      @php
        $medsJson = $medicamentos->map(fn ($m) => ['id' => $m->id, 'nome' => $m->nome, 'gera' => strtolower(trim((string) $m->aplicacao)) === 'sim'])->all();
        $combosJson = $combos->map(fn ($c) => ['id' => $c->id, 'nome' => $c->nome, 'gera' => (bool) $c->gera_aplicacao])->all();
        $sorosJson = $soros->map(fn ($s) => ['id' => $s->id, 'nome' => $s->nome, 'gera' => (bool) $s->gera_aplicacao])->all();
      @endphp
      const MEDS = @json($medsJson);
      const COMBOS = @json($combosJson);
      const SOROS = @json($sorosJson);

      const BASE_NR = {{ (int) $prescricao->qt_semanas }};
      const DATA_BASE = '{{ $dataBase->format('Y-m-d') }}';

      let state = { weeks: [], items: [] };

      function formatarData(dt) {
        const dd = String(dt.getDate()).padStart(2, '0');
        const mm = String(dt.getMonth() + 1).padStart(2, '0');
        return dd + '/' + mm + '/' + dt.getFullYear();
      }
      function round1(v) { return Math.round(v * 10) / 10; }
      function round2(v) { return Math.round(v * 100) / 100; }

      function getItemAplicacao(item) {
        if (item.tipo === 'medicamento') return MEDS.find(x => x.id === item.id)?.gera || false;
        if (item.tipo === 'combo') return COMBOS.find(x => x.id === item.id)?.gera || false;
        if (item.tipo === 'soro') return SOROS.find(x => x.id === item.id)?.gera || false;
        return false;
      }

      // ============ GERAR SEMANAS ============
      $('#btn-gerar-semanas').on('click', function () {
        const qt = parseInt($('#qt_semanas_adicionar').val(), 10);
        if (!qt || qt < 1) { alert('Informe a quantidade de semanas.'); return; }

        const base = new Date(DATA_BASE + 'T00:00:00');
        state.weeks = [];
        for (let i = 1; i <= qt; i++) {
          const dt = new Date(base);
          dt.setDate(base.getDate() + i * 7);
          state.weeks.push({ num: i, data: formatarData(dt) });
        }
        state.items = [];
        renderSemanas();
      });

      // ============ RENDER SEMANAS (cards) ============
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
                    <span class="badge ${temApl ? 'bg-primary' : 'bg-secondary'} rounded-circle d-inline-flex align-items-center justify-content-center fw-semibold" style="width:28px;height:28px;">${BASE_NR + wk.num}</span>
                    <span class="fw-semibold">Semana ${BASE_NR + wk.num}</span>
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
                  <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirModalItem(${wk.num})">
                    <i class="ri-add-line me-1"></i>Adicionar medicamento / combo / soro
                  </button>
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
        state.items[parseInt(input.data('item'), 10)].qtds[parseInt(input.data('semana'), 10)] = v;
      });
      $('#semanas-container').on('click', '.btn-plus', function () {
        const input = $(this).siblings('.item-qtd');
        const v = round1((parseFloat(input.val()) || 1) + 0.1);
        input.val(v);
        state.items[parseInt(input.data('item'), 10)].qtds[parseInt(input.data('semana'), 10)] = v;
      });
      $('#semanas-container').on('click', '.item-remove', function () {
        const idx = parseInt($(this).data('item'), 10);
        const wk = parseInt($(this).data('semana'), 10);
        delete state.items[idx].qtds[wk];
        if (Object.keys(state.items[idx].qtds).length === 0) {
          state.items.splice(idx, 1);
        }
        renderSemanas();
      });

      // ============ MODAL ITEM ============
      let modalWeek = null;
      window.abrirModalItem = function (wk) {
        if (!state.weeks.length) { alert('Gere as semanas primeiro.'); return; }
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
               <label class="form-check-label" for="semana-chk-${w.num}">Semana ${BASE_NR + w.num} (${w.data})</label>
             </div>`
          );
        });
        new bootstrap.Modal(document.getElementById('modal-item')).show();
      };

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
        bootstrap.Modal.getInstance(document.getElementById('modal-item')).hide();
        renderSemanas();
      });

      // ============ VALOR (máscara) ============
      function mascaraMoeda(el) {
        const digits = String(el.value).replace(/\D/g, '').slice(0, 14);
        if (!digits) { el.value = ''; return; }
        const v = (parseInt(digits, 10) / 100).toFixed(2);
        const partes = v.split('.');
        el.value = 'R$ ' + partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + partes[1];
      }
      function parseMoeda(v) {
        if (!v) return 0;
        const n = parseFloat(String(v).replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.'));
        return isNaN(n) ? 0 : n;
      }
      $('#valor_rateio').on('input', function () { mascaraMoeda(this); });

      // ============ SUBMIT ============
      $('#form-adicionar-semanas').on('submit', function () {
        if (!state.weeks.length) { alert('Clique em "Gerar Semanas" antes de salvar.'); return false; }

        $('#hidden-itens').remove();
        const $f = $(this);
        const $cont = $('<div id="hidden-itens"></div>');

        state.items.forEach((it, i) => {
          $cont.append(`<input type="hidden" name="item_tipo[]" value="${it.tipo}">`);
          $cont.append(`<input type="hidden" name="item_id[]" value="${it.id}">`);
          Object.keys(it.qtds).forEach(w => {
            $cont.append(`<input type="hidden" name="item_qtd[${i}][${w}]" value="${it.qtds[w]}">`);
            $cont.append(`<input type="hidden" name="item_semanas[${i}][]" value="${w}">`);
          });
        });

        const $v = $f.find('input[name="valor"]');
        const v = parseMoeda($v.val());
        $v.val(v > 0 ? v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '');

        $f.append($cont);
        return true;
      });
    });
  </script>
@endsection
