# 05 — Procedimentos + Financeiro (V2): o coração do sistema

> **Status:** 📋 PROPOSTA para aprovação (2026-08-12)
> **Data:** 2026-08-12
> **Objetivo:** Redesenhar na V2 o módulo de **Procedimentos** (prescrição médica → semanas → medicações) e o **Financeiro** ligado a ele, corrigindo a estrutura frágil da V1 (coluna `codigo` "no oba oba") e atendendo às demandas da equipe para a V2. Este relatório vai da **estrutura de dados até as telas** (de forma genérica) para aprovação/correções.
>
> **⚠️ Nota:** este relatório se junta ao que já estava informado no **`proposta_v2.md`** (arquivo ainda não presente no workspace — quando você adicioná-lo, eu faço a fusão e incorporo qualquer item adicional).

---

## 0. Resumo executivo (o que está sendo proposto)

1. **Estrutura nova e limpa** em 3 níveis: **Prescrição** (mestre, substitui o `codigo` da V1) → **Semanas** (todas sempre geradas, mesmo sem aplicação) → **Medicações da semana** (só as marcadas "aplicação = Sim" geram aplicação).
2. **Financeiro por parcela**: o valor do tratamento informado pelo médico é dividido **pela quantidade de semanas que têm medicação** (semanas de pausa não contam). Desconto/acréscimo saem da tela de pagamento; o pagamento é **aberto** (pagar N parcelas de uma vez ou dar **entrada** que **replaneja** o saldo) e há **Reajuste** (sem recalcular o passado).
3. **Auditoria total**: qualquer alteração (cadastro, edição, aplicação, financeiro) gera **log com dados antigos/novos** e autor.
4. **Anexos com histórico/rastreio**: prescrição médica, comprovante de pagamento e demonstrativo — com regra de **bloquear finalizar aplicação sem abrir o anexo**.
5. **Relatórios atualizados**: financeiro e imprimir cadastro passam a mostrar entradas/reajuste/parcelas; relatório da enfermagem ganha a **coluna da semana em aplicação**.

---

## 1. Contexto e objetivos

- Hoje o negócio central do Instituto GL é o **ciclo: prescrição médica → semanas de aplicação → aplicação pela enfermagem → pagamento em parcelas**.
- A V1 juntou tudo na tabela `procedimentos` com um agrupador improvisado (`codigo` = concatenação de `paciente_id + datahora`), o que gerou inconsistências (o grupo só "existe" porque várias linhas compartilham o mesmo `codigo`).
- A V2 precisa de um modelo **relacional correto** e que absorva as demandas da equipe (pagamento aberto com entrada/replanejamento, reajuste, anexos, semana no relatório de enfermagem, trava de finalização sem anexo, etc.).

---

## 2. Análise da V1 (como funciona hoje)

### 2.1 Estrutura (tabelas envolvidas)

| Tabela V1 | Papel | Observações |
|---|---|---|
| `procedimentos` | **1 linha por SEMANA** | agrupadas por `codigo`; `nr_procedimento` = nº da semana; `valor` = soma das medicações; `situacao` (Agendado/Aplicado/Aplicação Parcial/Pendente/Atendimento/Cancelado/Fila de Aplicação/Semana Sem Aplicação); `semana_sem_aplicacao` = semana de pausa; campos de pagamento (`st_pagamento`, `vl_pago`, `parcelas`...) replicados por semana |
| `aplicacaos` | **1 linha por MEDICAÇÃO de uma semana** | `procedimento_id`, `medicamento_id`, `quantidade`, `valor`, `total`, `situacao` (Aberta/Aplicada/Cancelada), `is_soro`, `dt_hr_chegada/atendimento`, `user_id_aplicacao` (enfermeira) |
| `aplicacao_lotes` | **lote/código de barras usado na aplicação** | `aplicacao_id`, `lote`, `codigo_barras`, `estoque_aberto_id` (baixa no estoque aberto) |
| `estoque_abertos` | controle de estoque por lote aberto | `procedimento_id` (linka a semana que abriu o lote), `qt_inicial/utilizado/restante`, `lote`, `codigo_barras` |
| `financeiros` | **1 por GRUPO** (`codigo`) | `vl_consulta`, `vl_procedimentos` (soma), `vl_desconto`, `vl_adicional`, `vl_pagamento`, formas/parcelas |
| `financeiro_procedimentos` | vínculo financeiro ↔ semanas | |
| `financeiro_formas_pagamentos` | cada pagamento (forma, parcelas, valor) | usada para distribuir dinheiro entre as semanas |
| `procedimento_anexos` | anexos (prescrição) | `enviado_feegow` |
| `procedimento_observacaos` | observações avulsas por procedimento | |
| `procedimento_logs` | **auditoria** | `acao`, `descricao`, `dados_antigos`, `dados_novos` (json), autor |

### 2.2 Fluxo de cadastro (grupo + semanas + medicações)

1. `ProcedimentoSistemaController@insert` recebe **N semanas** (form dinâmico `contador_procedimentos`), cada uma com data e lista de medicações (`medicamento_id`, `quantidade`, `valor`, `total`, combo, soro).
2. Cada semana vira um `procedimentos` com `codigo` compartilhado e `nr_procedimento` sequencial.
3. Se a semana for marcada como **sem aplicação** (`semana_sem_aplicacao = true`), cria o procedimento com `valor = 0`, `situacao = 'Semana Sem Aplicação'` e **sem medicações**.
4. Para cada medicação: se `medicamento.aplicacao == 'Sim'` → `situacao = 'Aberta'` (precisa ser aplicada); senão → `situacao = 'Aplicada'` (não gera aplicação).
5. Se **nenhuma** medicação gera aplicação, a semana já nasce `Aplicado`/`user_id_aplicacao` preenchido.
6. Após criar, roda `recalcular_semanas_grupo` (reordena `nr_procedimento` colocando aplicadas primeiro) e monta o financeiro.
7. **Regra da V1**: se houver medicamento em **Ampola ou Miligrama**, é **obrigatório anexar a prescrição** (sem anexo, bloqueia o cadastro).

### 2.3 Fluxo de aplicação (enfermagem)

- A semana entra em "Fila de Aplicação" (`enviar_fila_aplicacao`), com `dt_hr_chegada`.
- A técnica informa **chegada/atendimento** (`dt_hr_chegada`/`dt_hr_atendimento`) e marca cada medicação como **Aplicada** (`atualizarAplicacoesLote`), registrando **lote/código de barras** consumido (baixa no `estoque_abertos`).
- Ao aplicar, `user_id_aplicacao` (enfermeira) é gravado e `recalcular_situacao` define a situação da semana (Aplicada / Aplicação Parcial).
- Finaliza com `dt_hr_finalizacao`.

### 2.4 Fluxo financeiro

- **1 `financeiros` por grupo** (`codigo`). `vl_procedimentos` = soma das semanas; `vl_consulta` separado; `vl_desconto`/`vl_adicional`.
- Pagamentos são **formas** (`FinanceiroFormasPagamento`), e `atualiza_financeiro_procedimento` **distribui o dinheiro** entre as semanas na ordem de `nr_procedimento` (o "chunk" mais recente paga as próximas semanas), marcando `st_pagamento = Sim/Parcial/Não` e `data_pagamento` por semana.
- Crédito de tratamento anterior **não existe** — por isso a equipe se confunde: usa-se desconto para compensar, e o desconto não bate com o crédito.

### 2.5 Telas da V1 (resumo)

- **Listagem** (`sistema/procedimentos`): DataTable server-side com busca; grupos pelo `codigo`.
- **Grupo** (`index_grupo`): semanas do grupo + ações (visualizar, financeiro, imprimir).
- **Cadastro** (`adicionar`): formulário dinâmico de semanas/medicações.
- **Editar semana** (`editar`): medicações com editar/excluir (só admin), adicionar combo, anexos.
- **Financeiro** (`financeiros/acessar`): valores, formas de pagamento, pagamentos.
- **Imprimir cadastro** (`imprimir_cadastro`): PDF por grupo com as semanas.
- **Relatório enfermagem** (`RelatorioController`): aplicações por período/clínica/enfermeira.
- **Relatório financeiro** (`financeiro_gerar`): por período.

### 2.6 Pontos fracos da V1

1. **Grupo sem identidade**: o mestre "não existe"; é só um `codigo` repetido. Qualquer mudança de data/medicação exige regra manual (`recalcular_semanas_grupo` reordena semanas aplicadas primeiro — confuso).
2. **Financeiro espalhado**: valor/parcelas/situação de pagamento replicados em **cada semana** (`procedimentos`), em vez de um financeiro de tratamento com parcelas.
3. **Desconto ≠ crédito**: não existe "crédito em aberto" de protocolo anterior; compensa-se com desconto, gerando conferência difícil.
4. **Valor por medicação**: o valor do tratamento é a soma das medicações cadastradas, e não o valor definido pelo médico dividido em parcelas.
5. **Reajuste inexistente**: reajuste de medicação não tem fluxo; editar medicação mexe no valor já lançado.
6. **Semanas de pausa contam no financeiro**: `vl_procedimentos` soma até semanas sem aplicação (valor 0, mas sujam a conferência).
7. **Anexo sem rastreio de leitura**: não há como saber se a técnica "abriu" o anexo antes de aplicar (não dá para travar finalização).
8. **Auditoria parcial**: logs existem, mas não cobrem todos os fluxos de forma padronizada (financeiro, parcelas, reajuste).
9. **N+1 e regras no controller**: lógica de negócio gigante dentro dos controllers (sem service), difícil de manter/testar.
10. **Sem data/hora por medicação**: a V1 só tem horários no nível da semana; se o paciente aplica parte das medicações da semana em um dia e o restante dias depois, não fica registrado **quando cada uma foi aplicada** (dificulta o relatório e a conferência).

---

## 3. Proposta de estrutura V2 (migrations)

### 3.1 Conceito

```
PRESCRIÇÃO (mestre)
   │ 1
   ├── SEMANA 1 ──► MEDICAÇÕES da semana
   ├── SEMANA 2  (pausa — sempre gerada, sem medicações)
   ├── SEMANA 3 ──► MEDICAÇÕES da semana
   └── ...        (cada medicação gera ou não aplicação)
```

- **Prescrição** = o tratamento/prescrição do médico para aquele paciente (substitui o agrupador `codigo`).
- **Semana** = cada semana prevista do tratamento (todas geradas, mesmo as de pausa).
- **Medicação da semana** = cada item (medicamento/combo/soro) daquela semana; `gera_aplicacao` decide se vira aplicação ou não.

### 3.2 Tabela mestre — `prescricaos` (nome em decisão D1)

```php
Schema::create('prescricaos', function (Blueprint $table) {
    $table->id();
    $table->string('codigo_versao1')->nullable()->index(); // código antigo da V1 (paciente_id+datahora)
    $table->unsignedBigInteger('paciente_id');
    $table->unsignedBigInteger('clinica_id');
    $table->unsignedBigInteger('user_id_cadastro')->nullable();
    $table->string('medico');
    $table->string('tipo_atendimento')->nullable();
    $table->text('agendamento')->nullable();
    $table->date('data_prescricao');
    $table->integer('qt_semanas')->default(0);       // total de semanas planejadas
    $table->integer('qt_semanas_aplicacao')->default(0); // semanas com medicação que gera aplicação
    $table->integer('qt_parcelas')->default(0);      // nº de parcelas (= qt_semanas_aplicacao)
    $table->integer('semana_atual')->default(0);     // semana em andamento (0 = não iniciado)
    $table->decimal('valor_tratamento', 10, 2)->default(0); // valor total informado pelo médico
    $table->decimal('credito_em_aberto', 10, 2)->default(0); // valor que o paciente tem de outra prescrição (pagou e não usou)
    $table->string('situacao');  // Agendada | Em Andamento | Concluída | Cancelada
    $table->string('situacao_financeira')->default('Em Aberto'); // Em Aberto | Parcial | Pago | Cancelado
    $table->text('obs')->nullable();
    $table->timestamps();

    $table->foreign('paciente_id')->references('id')->on('pacientes');
    $table->foreign('clinica_id')->references('id')->on('clinicas');
    $table->foreign('user_id_cadastro')->references('id')->on('users');
});
```

> **Por que `qt_semanas`, `qt_semanas_aplicacao`/`qt_parcelas` e `semana_atual`?** O cálculo financeiro precisa saber quantas parcelas existem (`qt_parcelas` = semanas com aplicação = `qt_semanas_aplicacao`); a aplicação em andamento (`semana_atual`) permite saber de imediato em qual semana o paciente está (0 = ainda não iniciou, 1..N = semana em andamento), sem precisar varrer as semanas — útil para o relatório da enfermagem.

> **A prescrição é também o cabeçalho financeiro** (não existe tabela `financeiros` separada): `valor_tratamento` (valor total), `qt_parcelas` (nº de parcelas = semanas com aplicação), `credito_em_aberto` (valor que o paciente tem de outra prescrição) e `situacao_financeira` (Em Aberto/Parcial/Pago/Cancelado) ficam aqui. Parcelas, pagamentos, formas e distribuição apontam para `prescricao_id`.

### 3.3 Tabela semanas — `prescricao_semanas`

```php
Schema::create('prescricao_semanas', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_versao1')->nullable()->index(); // id do antigo `procedimentos` (semana)
    $table->unsignedBigInteger('prescricao_id');
    $table->integer('nr_semana');                 // 1..N (ordem real do tratamento)
    $table->date('data_prevista');
    $table->boolean('tem_aplicacao')->default(false); // se a semana tem medicação que gera aplicação
    $table->string('situacao');  // Agendada | Em Atendimento | Aplicada | Aplicação Parcial | Cancelada
    $table->text('obs')->nullable();
    $table->dateTime('dt_hr_chegada')->nullable();
    $table->dateTime('dt_hr_atendimento')->nullable();
    $table->dateTime('dt_hr_finalizacao')->nullable();
    $table->unsignedBigInteger('user_id_aplicacao')->nullable(); // enfermeira que aplicou
    $table->timestamps();

    $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
    $table->foreign('user_id_aplicacao')->references('id')->on('users');
});
```

> A semana **sempre existe** (mesmo pausa). Pausa = `tem_aplicacao = false` e **sem medicações** (R1).

### 3.4 Tabela medicações da semana — `prescricao_semana_medicamentos`

```php
Schema::create('prescricao_semana_medicamentos', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_versao1')->nullable()->index(); // id do antigo `aplicacaos`
    $table->unsignedBigInteger('prescricao_semana_id');
    $table->unsignedBigInteger('medicamento_id');
    $table->unsignedBigInteger('combo_id')->nullable(); // se veio de um combo
    $table->unsignedBigInteger('clinica_id_aplicacao')->nullable(); // clínica onde ESTA medicação foi aplicada
    $table->boolean('is_soro')->default(false);
    $table->boolean('gera_aplicacao')->default(false); // derivado de medicamento.aplicacao == 'Sim'
    $table->double('quantidade');
    $table->string('situacao'); // Aberta | Aplicada | Cancelada
    $table->date('data_prevista')->nullable();        // data prevista DESTA medicação (pode diferir da data da semana)
    $table->dateTime('dt_hr_chegada')->nullable();    // chegada para esta aplicação
    $table->dateTime('dt_hr_atendimento')->nullable();// início desta aplicação
    $table->dateTime('aplicado_em')->nullable();      // quando foi efetivamente aplicada
    $table->unsignedBigInteger('user_id_aplicacao')->nullable(); // enfermeira que aplicou ESTA medicação
    $table->text('obs')->nullable();
    $table->timestamps();

    $table->foreign('prescricao_semana_id')->references('id')->on('prescricao_semanas')->onDelete('cascade');
    $table->foreign('medicamento_id')->references('id')->on('medicamentos');
    $table->foreign('clinica_id_aplicacao')->references('id')->on('clinicas');
    $table->foreign('user_id_aplicacao')->references('id')->on('users');
});
```

> `gera_aplicacao` é **derivado** do campo `aplicacao` do medicamento no momento do cadastro (congela a decisão de "gera ou não aplicação" naquela semana). Se o medicamento mudar depois, as semanas antigas não mudam (só as novas). (Detalhe em R2.)

> **No cadastro de procedimentos os medicamentos NÃO têm valor:** o valor do tratamento vem da **prescrição** (`prescricaos.valor_tratamento`) e é dividido em **parcelas**. `prescricao_semana_medicamentos` **não guarda `valor`/`total`** — no cadastro só se informa o medicamento e a quantidade. (A tabela `medicamentos` continua com `vl_venda` — não mexe nela. Decisão D14.)

> **Horários por medicação (correção de um problema da V1):** as medicações da MESMA semana podem ser aplicadas em **dias/horários diferentes** (ex.: paciente aplica 2 hoje e deixa 2 pendentes para daqui a 2 dias ou na semana seguinte). Por isso cada medicação guarda a própria `data_prevista` e os horários de chegada/atendimento/`aplicado_em` — a semana pode ficar em `Aplicação Parcial` enquanto tiver pendência, **sem perder a data/hora real de cada aplicação**. (Decisão D12.)

> **Onde marcamos a aplicação:** `prescricao_semana_medicamentos` é a tabela da aplicação — cada linha = uma medicação da semana; marcamos `situacao = Aplicada` + `aplicado_em` + `user_id_aplicacao` (enfermeira) + `clinica_id_aplicacao` (clínica onde foi aplicada). O **lote/código usado** fica em `aplicacao_lotes` (por medicação). A semana (`prescricao_semanas`) guarda apenas o resumo (situação, horários de chegada/atendimento/finalização). A clínica da aplicação **não fica na prescrição** — fica aqui, onde a aplicação é marcada.

### 3.5 `aplicacao_lotes` (já existe na V2 — só ajustar a FK)

- Hoje: `aplicacao_lotes.aplicacao_id` **sem FK** (adiado até existir o módulo).
- Proposta: trocar o vínculo para `prescricao_semana_medicamento_id` (a "aplicação" agora é a medicação da semana) e criar a FK.

```php
Schema::table('aplicacao_lotes', function (Blueprint $table) {
    $table->renameColumn('aplicacao_id', 'prescricao_semana_medicamento_id');
    $table->foreign('prescricao_semana_medicamento_id')->references('id')->on('prescricao_semana_medicamentos');
});
```

### 3.6 Anexos — `anexos` (unificando prescrição + financeiro)

```php
Schema::create('anexos', function (Blueprint $table) {
    $table->id();
    $table->string('tipo'); // prescricao | comprovante_pagamento | demonstrativo_pagamento
    $table->unsignedBigInteger('prescricao_id')->nullable();
    $table->unsignedBigInteger('pagamento_id')->nullable();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('nm_anexo');
    $table->string('arquivo');
    $table->string('mime')->nullable();
    $table->string('extensao')->nullable();
    $table->dateTime('visualizado_em')->nullable(); // rastreio: técnico abriu o anexo
    $table->unsignedBigInteger('visualizado_por')->nullable();
    $table->timestamps();

    $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users');
});
```

- `tipo = prescricao` → prescrição médica (aparece no **imprimir cadastro**).
- `tipo = comprovante_pagamento` / `demonstrativo_pagamento` → **só no financeiro** (NÃO no imprimir cadastro).
- `visualizado_em/por` → base da regra **R3** (travar finalização sem abrir).

### 3.7 Auditoria — `prescricao_logs`

```php
Schema::create('prescricao_logs', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('prescricao_id')->index();
    $table->string('entidade');      // prescricao | semana | medicamento | parcela | pagamento | reajuste | anexo
    $table->unsignedBigInteger('entidade_id');
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('acao');          // criado | editado | excluido | aplicado | cancelado | pago | reajustado | anexado ...
    $table->text('descricao')->nullable();
    $table->json('dados_antigos')->nullable();
    $table->json('dados_novos')->nullable();
    $table->timestamps();

    $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users');
});
```

- Toda mudança (inclusive em filhos) grava com `prescricao_id` para facilitar a linha do tempo do tratamento.
- `dados_antigos`/`dados_novos` guardam o **diff** (json) dos campos alterados.

---

## 4. Regras de negócio

### R1 — Semanas sempre geradas (item 2 da sua lista)

- No cadastro o usuário informa a **quantidade total de semanas** (ex.: 6) e marca em quais há aplicação.
- O sistema **gera as 6 semanas** (`prescricao_semanas`), preenchendo as datas (data inicial + 7 dias por semana — ou intervalo configurável).
- Semanas sem medicação = `tem_aplicacao = false`, `situacao = 'Agendada'`, sem medicações. **Contam como semana no planejamento**, mas **não contam como parcela** no financeiro.
- Ex.: 6 semanas, aplicação nas semanas 1, 3 e 5 → nascem 6 semanas; parcelas = 3.

### R2 — O que gera aplicação (item 3 da sua lista)

- **Fonte da verdade**: `medicamentos.aplicacao` (Sim/Não) — já existe na V2.
- No cadastro de cada medicação na semana, o sistema calcula `gera_aplicacao = (medicamento.aplicacao == 'Sim')` e congela.
  - `gera_aplicacao = true` → `situacao = 'Aberta'` (entra na fila da enfermagem, precisa ser aplicada).
  - `gera_aplicacao = false` → `situacao = 'Aplicada'` (só registrada na semana; **não gera aplicação**, não bloqueia, não entra na fila).
- `prescricao_semanas.tem_aplicacao = true` se **pelo menos uma** medicação da semana tem `gera_aplicacao = true`.
- Combos/soros: ao inserir um combo, cada medicamento do combo herda `gera_aplicacao` do próprio medicamento.
- **Aplicações de procedimento puro** (ex.: "Procedimento" como unidade) entram na semana como `gera_aplicacao = false` (a V1 já marcava como Aplicada direto).

### R3 — Travar finalização sem abrir o anexo (demanda da equipe)

- Antes de **finalizar a aplicação de uma semana que exige anexo**, o sistema verifica se existe `anexos` do tipo `prescricao` com `visualizado_em` preenchido para aquela prescrição.
- Se não foi aberto → **bloqueia** a finalização com mensagem ("Abra o anexo da prescrição antes de finalizar a aplicação").
- O "abrir" é registrado ao clicar/baixar o anexo (`visualizado_em` + `visualizado_por` + log).
- **Anexo obrigatório (D7 aprovada):** quando houver **qualquer** medicamento com `aplicacao = Sim` (`gera_aplicacao = true`).

### R4 — Situações e transições (proposta)

**Prescrição (mestre):** `Agendada` → `Em Andamento` (1ª semana entrou em atendimento) → `Concluída` (todas as semanas aplicadas) | `Cancelada`.

- `prescricaos.semana_atual`: começa em **0** (não iniciado). Ao entrar a 1ª semana em atendimento, `semana_atual = 1`; a cada semana **aplicada**, avança para a próxima (`semana_atual = nr_semana` seguinte com aplicação).

**Semana:** `Agendada` → `Em Atendimento` (chegou à clínica / fila) → `Aplicada` (todas as medicações aplicadas) | `Aplicação Parcial` (parte aplicada) | `Cancelada`.

**Medicação da semana:** `Aberta` → `Aplicada` (com lote/código + enfermeira + `aplicado_em`) | `Cancelada`.

- Ao **aplicar** cada medicação, grava `aplicado_em`, `user_id_aplicacao` (enfermeira) e o lote/código usado. Medicações da mesma semana podem ser aplicadas em **dias/horários diferentes**.
- A semana só vira `Aplicada` quando **todas** as medicações `gera_aplicacao = true` estiverem aplicadas (grava `dt_hr_finalizacao` na semana). Enquanto houver pendência, fica `Aplicação Parcial` — e `semana_atual` **não avança**.
- Ao aplicar a 1ª semana do grupo, a prescrição vira `Em Andamento`; ao concluir a última, `Concluída`.

### R5 — Edição e correções do cadastro (opções que você pediu)

Toda correção **entra para a fila de decisões de quem pode editar** (a V1 só permitia excluir medicação para admin). Proposta:

| Operação | Quem pode (sugestão) | Regra |
|---|---|---|
| Editar dados da prescrição (médico, obs, agendamento) | Recepção / Admin | Sempre com log |
| Mudar data de uma semana **não aplicada** | Recepção / Admin | Sempre com log |
| Editar medicação de semana **não aplicada** | Recepção / Admin | Recalcula valor + parcela se ainda não paga (D6) |
| Editar/excluir medicação de semana **já aplicada** | **Somente Admin** | Bloqueado para os demais (regra da V1) |
| Cancelar prescrição | Admin (com motivo obrigatório) | Só semanas não aplicadas viram `Cancelada`; financeiro fica "parcial" se já pagou |
| Excluir prescrição | **Somente Admin** + motivo | Bloqueado se houver qualquer semana aplicada OU pagamento (regra da V1); em vez de apagar, **cancelar** (histórico) |
| Excluir uma semana do meio | **Somente Admin** | Reordena `nr_semana` e **recalcula parcelas futuras** (D6) |
| Reabrir semana cancelada | **Somente Admin** | Retorna para `Agendada` |

> Todas as operações geram `prescricao_logs` com diff.

---

## 5. Financeiro (PROPOSTA — em construção, tabela por tabela)

### 5.1 Conceito (resumo do que você pediu)

- **A prescrição é o cabeçalho financeiro** (`prescricaos`): valor total, nº de parcelas, situação financeira.
- **Parcelas** = a previsão do que vamos receber (1 por semana com aplicação).
- **Pagamentos** = eventos; cada um tem suas **formas** (ex.: R$ 1.000 = R$ 500 Pix + R$ 500 Cartão).
- **Distribuição** = tabela que diz **qual parcela** cada valor cobre (ex.: R$ 1.000 → parcela 1; R$ 1.500 → R$ 1.000 parcela 1 + R$ 500 parcela 2).
- **Redividir** = refaz as parcelas em aberto + **log** do que foi feito.
- **Crédito em Aberto** = valor que o paciente tem de **outra prescrição** (pagou e não usou) — campo simples no cadastro, abate no valor a parcelar.

### 5.2 Tabelas (definidas 1 a 1 — aprovadas até agora)

**✅ `financeiro_parcelas` (aprovada) — a previsão do que vamos receber:**

```php
Schema::create('financeiro_parcelas', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('prescricao_id');
    $table->unsignedBigInteger('prescricao_semana_id');  // semana que a parcela representa
    $table->integer('nr_parcela');
    $table->decimal('valor_parcela', 10, 2);
    $table->decimal('valor_pago', 10, 2)->default(0);
    $table->string('situacao');  // Em Aberto | Parcial | Paga | Cancelada
    $table->date('dt_vencimento')->nullable();
    $table->timestamps();

    $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
    $table->foreign('prescricao_semana_id')->references('id')->on('prescricao_semanas');
});
```

> Uma parcela por **semana com aplicação** (semana de pausa não gera parcela).

**✅ `prescricao_pagamentos` (aprovada) — o evento de pagamento (guarda o total; as formas vêm na próxima):**

```php
Schema::create('prescricao_pagamentos', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('prescricao_id');
    $table->date('dt_pagamento');
    $table->decimal('vl_total', 10, 2);          // total recebido no pagamento
    $table->text('obs')->nullable();
    $table->unsignedBigInteger('user_id')->nullable(); // quem lançou
    $table->timestamps();

    $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users');
});
```

> O `id_transacao` (TID/autorização) **não fica aqui** — vai na tabela de formas (cada forma tem o seu).

**✅ `prescricao_pagamento_formas` (aprovada) — COMO o pagamento foi pago:**

```php
Schema::create('prescricao_pagamento_formas', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('pagamento_id');      // prescricao_pagamentos.id
    $table->string('forma_pagamento');              // Dinheiro | Pix | Cartão | Débito ...
    $table->decimal('vl_pagamento', 10, 2);         // valor pago nessa forma
    $table->integer('parcelas')->default(1);        // p/ cartão crédito (1x, 2x...)
    $table->string('id_transacao')->nullable();     // TID / nº autorização dessa forma
    $table->text('obs')->nullable();
    $table->timestamps();

    $table->foreign('pagamento_id')->references('id')->on('prescricao_pagamentos')->onDelete('cascade');
});
```

> Sem tabela mestre de formas — a forma é um campo com as opções (Dinheiro, Pix, Cartão, Débito...). Ex.: evento de R$ 2.000 → 2 linhas: (Pix, R$ 1.000) + (Cartão, 2x, R$ 1.000, id_transacao X).

**✅ `pagamento_parcelas` (aprovada) — O QUE o pagamento pagou (qual parcela cada valor cobre):**

```php
Schema::create('pagamento_parcelas', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('pagamento_id');          // prescricao_pagamentos.id
    $table->unsignedBigInteger('financeiro_parcela_id'); // parcela que esse valor cobre
    $table->decimal('valor', 10, 2);                     // valor que o pagamento fez naquela parcela
    $table->timestamps();

    $table->foreign('pagamento_id')->references('id')->on('prescricao_pagamentos')->onDelete('cascade');
    $table->foreign('financeiro_parcela_id')->references('id')->on('financeiro_parcelas');
});
```

> Ex.: pagamento de R$ 1.500 → 2 linhas: (parcela 1, R$ 1.000) + (parcela 2, R$ 500).

**✅ Financeiro fechado (4 tabelas aprovadas):** `financeiro_parcelas` + `prescricao_pagamentos` + `prescricao_pagamento_formas` + `pagamento_parcelas`.

> **Redividir (entrada/reajuste/correção):** recalcula as parcelas **em aberto** e **grava um log** em `prescricao_logs` (o que era, o que ficou, motivo, quem). **Não há tabela de replanejamento por enquanto** — se precisar depois, a gente cria.

### 5.3 Regras de cálculo (com as 4 tabelas)

- **Geração das parcelas**: 1 parcela por semana com aplicação; `valor_parcela = valor_tratamento / qt_parcelas` (diferença de centavos ajustada na última).
- **Parcela `Paga`** quando `valor_pago >= valor_parcela`; `Parcial` quando pagou parte (soma das linhas de `pagamento_parcelas`).
- **Pagamento**: evento (`prescricao_pagamentos`) com as formas (`prescricao_pagamento_formas`) e a distribuição por parcela (`pagamento_parcelas`).
- **Entrada**: paga a 1ª parcela em aberto; o resto reduz o saldo devedor → recalcula as parcelas em aberto (`saldo_devedor ÷ parcelas_restantes`) e **loga** em `prescricao_logs`.
- **Reajuste**: recalcula somente as parcelas em aberto com o novo valor; as **pagas nunca mudam**; **loga**.
- **Crédito em aberto**: se informado no cadastro, abate no valor a parcelar (ex.: `valor_tratamento - credito_em_aberto`).
- **Bloqueio**: não permite pagar mais que o saldo devedor.




---

## 6. Logs de auditoria (detalhe — item 5 da sua lista)

- **Toda** movimentação grava `prescricao_logs`:
  - Cadastro da prescrição (com as semanas e medicações).
  - Edição de qualquer campo (diff em `dados_antigos`/`dados_novos`).
  - Aplicação de medicação (lote, enfermeira, horários).
  - Lançamento/edição/exclusão de pagamento.
  - Entrada/replanejamento, reajuste, cancelamento, anexo (inclusive abertura/visualização).
- O log é **imutável** (não há edição/exclusão de log).
- A tela da prescrição terá uma **aba "Histórico"** mostrando a linha do tempo (quem, o quê, quando, valores antes/depois).

---

## 7. Menu e telas (genericamente)

### 7.1 Menu

- Adicionar **"Procedimentos"** após **Pacientes** e **"Financeiro"** logo após Procedimentos (sem submenu pesado; itens dentro das páginas).

```
Dashboard | Configurações | Estoque | Pacientes | Procedimentos | Financeiro
```

### 7.2 Listagem de Procedimentos (Prescrições)

- DataTable server-side (padrão V2, assets locais) com: Paciente, Médico, Data da prescrição, Nº semanas, Valor tratamento, Situação, Ações (Visualizar, Financeiro, Imprimir).
- Filtros por período, clínica, situação; busca por paciente/CPF.
- Botão **"Novo Procedimento"** (recepção/admin).

### 7.3 Cadastro de Prescrição (o coração)

**Fluxo proposto (cards por semana — como você descreveu):**

1. **Dados gerais** (iguais à V1): paciente (select2), médico, clínica, tipo de atendimento, agendamento, anexos (prescrição).
2. **Data inicial + quantidade de semanas**: o usuário informa a **data inicial** e **quantas semanas** (ex.: 6). O sistema **gera 1 card por semana** (S1..S6), cada um com a data automática (+7d).
3. **Cada card de semana começa como "Sem aplicação"** (pausa) e tem um botão **"Adicionar medicamento / combo / soro"**.
4. **Adicionar medicamento** abre um seletor onde se escolhe:
   - o **medicamento** (ou **combo** ou **soro**),
   - a **quantidade**,
   - e **quais semanas** vão receber aquele item (multi-seleção: S1, S2, S3... — marca de uma vez todas as semanas que terão aquele medicamento).
   - Botão **"Adicionar"** → o item entra em **todos os cards** das semanas marcadas.
   - O card de cada semana que receber medicamento com `gera_aplicacao = true` deixa de ser "Sem aplicação" e passa a mostrar as medicações; as que **não receberem nada continuam "Sem aplicação"** (pausa — não geram parcela).
5. **Financeiro (montado na parte inferior, editável)**: o sistema já monta o financeiro padrão — `valor_tratamento`, `qt_parcelas` (semanas com aplicação), `valor_parcela = valor_tratamento ÷ qt_parcelas` e `credito_em_aberto` (se o paciente tiver de outra prescrição) — com a **opção de alterar** os valores/parcelas antes de salvar.
6. **Anexo prescrição**: upload (obrigatório quando há medicação que exige, R3/D7).
7. **Salvar** → cria prescrição + semanas + medicações + parcelas + log.

> **Por que é melhor que a V1:** na V1 o mesmo medicamento era adicionado semana por semana (repetitivo e propenso a erro). Aqui você adiciona **uma vez** e marca **todas as semanas** que o usam — bem mais rápido e fiel à prescrição real (ex.: mesma medicação nas semanas 1–5).

### 7.4 Visualizar Prescrição (abas)

- **Resumo**: paciente, médico, situação, valores, parcelas.
- **Semanas**: grade com cada semana (nº, data, situação, medicações, quem aplicou, horários).
- **Financeiro**: parcelas e pagamentos (aba específica — ver 7.6).
- **Anexos**: prescrição (aqui aparece, e também no imprimir cadastro).
- **Histórico**: logs (auditoria).

### 7.5 Editar / Correções

- Botão **Editar** na prescrição/semana respeitando as permissões e regras de R5 (data, médico, obs, medicações de semana não aplicada).
- Medicação de semana aplicada: só admin; exclusão só admin.
- Cancelar/excluir com motivo e validações da V1 (não pode se já aplicado/pago → sugere cancelar).

### 7.6 Financeiro da Prescrição (abas)

- **Parcelas**: tabela parcela × semana, valor, situação, botão "Pagar".
- **Pagamentos**: lançamento **aberto** — escolhe as **parcelas** (das semanas) que o valor cobre (marcar 1, 2, 3...) ou **entrada**; formas e valores parciais por parcela num mesmo evento (`pagamento_parcelas`); com **anexo de comprovante** (upload) e **demonstrativo** no lançamento.
- **Entrada/Redivisão**: ao dar entrada, mostra a prévia do recálculo (saldo devedor ÷ parcelas restantes) antes de confirmar; também cobre o **Reajuste** (recalcula as parcelas em aberto). Tudo registrado no **log** (`prescricao_logs`).
- **Anexos financeiros** (comprovante/demonstrativo) — visíveis aqui, **não** no imprimir cadastro.

### 7.7 Aplicação (enfermagem)

- Fila de aplicação (semanas `Em Atendimento`): lista com paciente, **semana atual** (destaque), medicações abertas.
- Registrar chegada/atendimento, **abrir anexo** (obrigatório p/ finalizar, R3), informar **lote/código de barras** (baixa em `estoque_abertos`/`aplicacao_lotes`), marcar **Aplicada**.
- Cada medicação registra a própria data/hora (`aplicado_em`); o paciente pode aplicar parte da semana e voltar depois para concluir as pendentes (a semana fica `Aplicação Parcial` até todas aplicadas).
- Não poderá finalizar a semana sem abrir o anexo (bloqueio + mensagem).

### 7.8 Impressões e Relatórios

- **Imprimir cadastro** (PDF): prescrição + semanas + medicações + valores + parcelas + entradas/reajuste + **anexos do tipo prescricao** (não os financeiros).
- **Relatório financeiro**: por período, com parcelas, pagamentos, entradas/replanejamentos, reajustes.
- **Relatório enfermagem**: aplicações por período/clínica/enfermeira com a **coluna "Semana"** (ex.: "Semana 3 de 6") indicando em qual semana o paciente está sendo aplicado (demanda).

---

## 8. Migração V1 → V2 (ADIADA)

> A migração dos dados da V1 será feita **em um relatório separado** — o fluxo será testado primeiro, sem dados. O mapeamento de origem/destino já está levantado (scripts `17_prescricaos` → `25_observacoes`).

---

## 9. Relatórios (detalhe)

### 9.1 Relatório financeiro
- Filtros: período, clínica, médico, situação.
- Colunas: paciente, prescrição, valor tratamento, parcelas (paga/total), saldo devedor (parcelas em aberto), valores recebidos, histórico de reajustes (log).
- Exportação PDF/Excel (padrão da V2).

### 9.2 Imprimir cadastro
- Cabeçalho do paciente + prescrição; tabela de semanas (nº, data, situação, medicações com quantidade/valor); resumo financeiro (parcelas, entradas, reajuste); **anexos de prescrição**.

### 9.3 Relatório de enfermagem
- Filtros: período, clínica, enfermeira.
- Colunas: paciente, **semana em aplicação** (vem de `prescricaos.semana_atual` — "Semana X de Y", demanda), medicação aplicada, lote, **data/hora real da aplicação** (`aplicado_em`, por medicação), **enfermeira** (`user_id_aplicacao` da medicação).

---

## 10. Perguntas e decisões a confirmar (para você aprovar/corrigir)

- ✅ **D1 — Nome da tabela mestre:** `prescricaos` (confirmado).
- ✅ **D2 — Menu:** "Procedimentos" → lista todas as prescrições (DataTables) + botão "Adicionar".
- ✅ **D3 — Financeiro:** 4 tabelas aprovadas 1 a 1 (seção 5).
- ✅ **D4/D5/D6/D11 — Simplificação:** sem desconto/acréscimo; valor de outra prescrição = campo **`credito_em_aberto`** (simples, no cadastro); reajuste só em parcelas futuras; arredondar na última parcela.
- ✅ **D7 — Anexo obrigatório:** quando houver medicamento com `aplicacao = Sim`.
- ✅ **D8 — Rastreio "abriu o anexo":** registrar `visualizado_em/por` ao abrir o PDF/imagem.
- ✅ **D9 — Importação:** adiada (relatório separado).
- ✅ **D10 — Datas das semanas:** automáticas, +7 dias.
- **D12 — Horários por medicação:** confirmar que cada medicação da semana terá a própria `data_prevista` + horários (chegada/atendimento/`aplicado_em`), permitindo aplicar em dias diferentes (correção do problema da V1). A semana só é `Aplicada` quando todas as medicações forem aplicadas.
- **D13 — Estrutura financeira (fechada):** **4 tabelas** — `financeiro_parcelas`, `prescricao_pagamentos` (evento com total), `prescricao_pagamento_formas` (COMO pagou, forma como texto + obs + id_transacao) e `pagamento_parcelas` (O QUE pagou — qual parcela cada valor cobre). **Redividir** = recalcular parcelas em aberto + **log** em `prescricao_logs` (sem tabela de replanejamento por enquanto).
- **D14 — Procedimento sem valor por medicamento:** no **cadastro de procedimentos**, cada medicamento **não tem valor** (só medicamento + quantidade) — o valor do tratamento vem da prescrição (`prescricaos.valor_tratamento`) dividido em parcelas. A tabela `medicamentos` **continua com `vl_venda`** (não mexe nela).

---

## 11. Resumo

- A V1 tratava o grupo de semanas como um "código repetido" e o financeiro como soma de medicações com desconto/acréscimo — frágil e confuso para a equipe.
- A V2 propõe: **Prescrição → Semanas → Medicações** (com `gera_aplicacao`), **semanas sempre geradas** (pausa conta no plano, não na parcela), **aplicação com data/hora por medicação** (medicações da mesma semana podem ser aplicadas em dias diferentes), **financeiro direto na prescrição** (sem tabela `financeiros` separada: valor total e nº de parcelas ficam em `prescricaos`), com **parcelas** (previsão a receber), **pagamentos com formas** (COMO pagou) e `pagamento_parcelas` (O QUE pagou — qual parcela cada valor cobre), **pagamento aberto** (pagar N semanas de uma vez OU entrada + replanejamento do saldo), **reajuste só em parcelas futuras**, **anexos com rastreio** (trava finalização sem abrir), **auditoria completa** e relatórios (financeiro, imprimir cadastro, enfermagem com coluna de semana).
- Próximo passo: você revisa e responde as decisões **D1–D11** (e envia o `proposta_v2.md` se houver itens extras) para eu fechar o plano de implementação.

---

## 12. Implementação (STATUS — 13/08/2026)

> Módulo **Procedimentos (cadastro + financeiro)** implementado e validado no navegador. **Enfermagem (aplicações) é o PRÓXIMO módulo** — não foi construído aqui.

### ✅ Feito e validado
- **10 migrations** criadas e rodadas (todas as tabelas: `prescricaos`, `prescricao_semanas`, `prescricao_semana_medicamentos`, `financeiro_parcelas`, `prescricao_pagamentos`, `prescricao_pagamento_formas`, `pagamento_parcelas`, `anexos`, `prescricao_logs` + rename `aplicacao_lotes.aplicacao_id → prescricao_semana_medicamento_id`).
- **9 Models** (com relações/casts) + `AplicacaoLote` atualizado.
- **Menu "Procedimentos"** + rotas + `ProcedimentoSistemaController`.
- **Listagem** (DataTables server-side) com filtros por clínica/situação/período e busca por paciente/CPF/médico/código; botão "Novo Procedimento".
- **Cadastro** no padrão dos cards por semana: dados gerais → gerar semanas (+7d) → adicionar medicamento/combo/soro marcando as semanas → financeiro montado na parte inferior (editável: valor, crédito em aberto, valor por parcela) → anexo de prescrição (obrigatório se houver aplicação) → salvar (cria prescrição + semanas + medicações + parcelas + log).
- **Médico vindo da Feegow**: campo é um **Select2** alimentado por `professional/list` (mesma origem da V1); grava o **nome do profissional** em `prescricaos.medico`. Se a API falhar, o select fica vazio com aviso (não quebra a página). Método `FeegowService::medicos()`.
- **Financeiro na prescrição**: abas Resumo / Semanas / Financeiro / Anexos / Histórico; parcelas (1 por semana com aplicação); **Registrar Pagamento** (formas Dinheiro/Pix/Cartão/Débito + distribuição automática por parcela com bloqueio de sobrepagamento + comprovante opcional); **Reajustar/Redividir** (recalcula parcelas em aberto, pagas não mudam, com motivo + log); **Cancelar** (com motivo).
- **Anexos**: upload prescrição e comprovante; visualizar (rastreia `visualizado_em/por` + log) e baixar.
- **Logs** em `prescricao_logs` (criado/pago/reajuste/visualizado/cancelado).

### ⏳ Pendente (próximos)
- **Enfermagem (aplicações)**: fila de aplicação, chegada/atendimento, lote/estoque (`aplicacao_lotes`/`estoque_abertos`), marcar aplicada com `aplicado_em` por medicação, trava de finalização sem abrir anexo, `semana_atual`/`situacao` das semanas. → **próximo módulo**.
- **Imprimir cadastro** (PDF) e **relatório financeiro** (9.1/9.2) — relatórios/impressões.
- **Relatório de enfermagem** com coluna "Semana X de Y" (9.3) — depende do módulo de enfermagem.
- **Migração V1 → V2** (relatório separado).
- Filtro de busca na listagem por **código da prescrição** já funciona; falta o **botão Editar** de prescrição (R5) — a ser avaliado junto do módulo de enfermagem.
