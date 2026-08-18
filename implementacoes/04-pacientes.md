# 04 — Pacientes (V2): importação da V1 + integração Feegow

> **Status:** ✅ Implementado em 2026-08-12
> **Data:** 2026-08-12
> **Objetivo:** Implementar na V2 o módulo de **Pacientes**, importar os pacientes da **V1** (com `id_versao1`) e organizar a **integração com a Feegow** (que é a fonte dos pacientes) para manter a base atualizada. No menu, uma **guia "Pacientes"** limpa, logo após **Estoque**.

---

## 1. Contexto

Os pacientes do Instituto GL **vêm da Feegow** (sistema de agenda/prontuário). O cadastro local é, na prática, um **espelho** dos pacientes da Feegow (16.706 ids Feegow distintos em 16.890 registros locais). O `paciente_id_feegow` é a **chave de fonte da verdade** e praticamente tudo (procedimentos, financeiro) referencia o paciente pelo id local, que por sua vez guarda o id Feegow.

---

## 2. Análise da V1

### 2.1 Schema (`pacientes`)

| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | id local (referenciado por `procedimentos.paciente_id`) |
| `nm_paciente` | string | nome (ou nome social) |
| `dt_nascimento` | date nullable | |
| `cpf` | string nullable | |
| `paciente_id_feegow` | bigint | **id do paciente na Feegow** (fonte) |
| `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep` | string nullable | endereço |
| `telefone`, `email` | string nullable | concatenados (V1 junta fixo+celular e emails com espaço) |
| `integrado_kamino` | string(10) default 'Sim' | flag integração Kamino (desabilitada na V1) |
| `obs` | text nullable | observação local (adicionada 2026-03-26) |
| `st_google` | integer default 0 | flag (adicionada 2026-04-21) |
| `timestamps` | | |

### 2.2 Dados atuais da V1 (produção)

| Métrica | Valor |
|---|---|
| Total de pacientes | **16.890** |
| Com CPF | 11.636 |
| Sem CPF | 5.254 |
| Com `obs` | 157 |
| `st_google = 1` | 1 |
| **Distinct `paciente_id_feegow`** | **16.706** |

> Como 16.706 dos 16.890 registros têm id Feegow, a base local é essencialmente um espelho da Feegow (com ~184 registros extras/duplicados locais).

### 2.3 Integração Feegow (como a V1 faz)

- **Wrapper**: `ApiFlegowController` (cURL) com o **token hardcoded** no código (JWT, licença 23224).
- **Base**: `https://api.feegow.com/v1/api/`.
- **Endpoints usados**:
  - `patient/list?limit=&offset=` (+ `alterado_em` p/ incremental) → retorna `patient_id`, `nome`, `nascimento`.
  - `patient/search?paciente_id=X&photo=true` → **detalhe completo**: `id`, `nome`/`nome_social`, `documentos.cpf`, `nascimento`, `telefones`, `celulares`, `email`, `endereco`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `cep`, foto.
  - `company/list-unity` → unidades (clínicas).
  - `professional/list` → médicos.
- **Fluxo de sync** (`PacienteSistemaController::atualizar_integracao`):
  1. Lê `configuracaos.ultima_atualizacao_pacientes` (na V1 está `2026-07-21`).
  2. `get_pacientes(ultima_atualizacao)` → lista paginada de pacientes alterados desde a data.
  3. Para **cada** paciente da lista, chama `get_nome_paciente(id)` (detalhe) e faz **upsert** por `paciente_id_feegow` (atualiza se existe, cria se não).
  4. Grava `ultima_atualizacao_pacientes = hoje`.
- **Disparo**: rota/botão manual (`sistema.pacientes.atualizar_integracao`), com `set_time_limit(0)`. Integração Kamino está **desabilitada** na V1 (flag `$integrar_kamino = false`).

### 2.4 Telas da V1

- **Listagem** (`sistema/pacientes`): DataTable server-side com busca por nome/CPF/id Feegow; colunas Nome, ID Feegow, ações (Procedimentos, Obs).
- **Obs**: modal ajax (`salvar_obs_ajax`) — campo local do paciente.
- **Procedimentos do paciente** (`pacientes/procedimentos/{id}`): lista os procedimentos.
- **Select2** (`listar_pacientes_ajax`): busca de paciente usada ao cadastrar procedimentos.

### 2.5 Pontos fracos da V1

1. **Token Feegow hardcoded** no controller (e possivelmente expirado — JWT de jul/2025).
2. **Sync lento (N+1)**: para cada paciente da lista faz uma chamada `patient/search` → 16k pacientes = 16k chamadas, com `set_time_limit(0)`. Frágil a timeout/parcial.
3. **Sync manual** (sem agendamento automático).
4. **Sem tratamento de remoção**: paciente que some da Feegow continua na base.
5. **Qualidade dos dados**: telefone/email concatenados com espaços (ex.: `11 99999 11 98888`); nome não normalizado.
6. **`integrado_kamino`** e integração Kamino em código morto/desabilitado (poluição).
7. Sem histórico de quando o paciente foi atualizado da Feegow (só o `ultima_atualizacao_pacientes` global).
8. A listagem não mostra foto nem dados de contato de forma estruturada.

---

## 3. Proposta V2 — Schema

Tabela `pacientes` seguindo o padrão da V2: **`id_versao1`** (id local da V1, pois `procedimentos` futuros vão referenciar) + **`paciente_id_feegow`** (fonte da verdade, único) + colunas da V1 corrigidas.

```php
Schema::create('pacientes', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_versao1')->nullable()->index();
    $table->unsignedBigInteger('paciente_id_feegow')->unique(); // fonte (Feegow)
    $table->string('nm_paciente');
    $table->date('dt_nascimento')->nullable();
    $table->string('cpf', 20)->nullable();
    $table->string('endereco')->nullable();
    $table->string('numero')->nullable();
    $table->string('complemento')->nullable();
    $table->string('bairro')->nullable();
    $table->string('cidade')->nullable();
    $table->string('estado', 2)->nullable();
    $table->string('cep', 10)->nullable();
    $table->string('telefone')->nullable();
    $table->string('email')->nullable();
    $table->text('obs')->nullable();            // observação local
    $table->boolean('st_google')->default(false);
    $table->timestamp('sincronizado_em')->nullable(); // última vez que veio da Feegow (novo)
    $table->timestamps();

    $table->index(['nm_paciente']);
    $table->index(['cpf']);
});
```

> **Notas**:
> - **`integrado_kamino`** não entra na V2 (integração desabilitada/legado) — se um dia voltar, vira tabela própria.
> - `paciente_id_feegow` vira **unique** (não tem duplicado na fonte).
> - `sincronizado_em` (novo) permite saber quando cada paciente foi atualizado da Feegow.

---

## 4. Integração Feegow (V2)

### 4.1 Estrutura recomendada

- **`app/Services/FeegowService.php`**: serviço que centraliza as chamadas à API (usando **Guzzle**, já no composer), com métodos: `unidades()`, `medicos()`, `pacientesDesde($data)`, `detalhePaciente($id)`. **Sem cURL manual**.
- **Credenciais no `.env`** (nunca no código):
  ```
  FEEGOW_BASE_URL=https://api.feegow.com/v1/api
  FEEGOW_TOKEN=<token atual>
  ```
  → `config/services.php` ou `config/feegow.php`.
- **Tabela de controle de sincronização**: usar `configuracaos` (como a V1) OU uma tabela nova `sincronizacaos` (mais limpa: registro por tipo/início/fim/status/quantidade). Sugiro a tabela nova para termos histórico das sincronizações.

### 4.2 Sincronização incremental (upsert)

1. Lê a última data de sincronização (ex.: `sincronizacaos.data_inicio` da última execução bem-sucedida).
2. `FeegowService::pacientesDesde($data)` → lista paginada (`patient/list` + `alterado_em`).
3. Para cada paciente alterado: `FeegowService::detalhePaciente($id)` e **upsert** por `paciente_id_feegow`:
   - **atualiza** os campos (nome, nascimento, cpf, endereço, contato...) e `sincronizado_em = now()`;
   - **cria** se ainda não existe;
   - tratamento de `nome_social` (usa nome social quando não há nome), formatação do CPF (somente dígitos) e datas (`dd/mm/aaaa` → `Y-m-d`).
4. Marca `sincronizacaos` como concluída + `ultima_atualizacao_pacientes`.

> **Mitigação do N+1 da V1**: como o `patient/list` só traz id/nome/nascimento, o detalhe exige `patient/search` por paciente. Para não travar (16k chamadas), o sync deve rodar **em background** (artisan command + fila) com **chunking e log de progresso**, e ser **incremental** (só o que mudou desde a última vez). Na primeira carga pode-se importar da **V1** (rápido) e deixar o Feegow apenas para atualizações incrementais.

### 4.3 Disparo

- **Botão manual** na tela de Pacientes ("Atualizar da Feegow") — só admin;
- **Agendamento automático** (Laravel Scheduler): diário (ex.: `php artisan pacientes:sincronizar-feegow`) — e um `schedule:run` a cada minuto no cron do servidor.

### 4.4 Remoção / inatividade (decisão)

- Pacientes que **não retornarem mais** da Feegow (sumiram na fonte) podem ser **marcados como inativos** (coluna `ativo`/`situacao`) em vez de apagados, preservando o histórico de procedimentos.

---

## 5. Menu e telas (V2)

- **Menu**: nova guia de topo **"Pacientes"** logo **após Estoque** (limpa, só pacientes), com submenu:
  - **Pacientes** (listagem com busca) — todos autenticados;
  - **Atualizar da Feegow** (ação, só admin) — pode ser um botão na própria listagem em vez de submenu.
- **Telas**:
  1. **Listagem** (`pacientes`): busca por nome/CPF/id Feegow; tabela com Nome, CPF, Nascimento, Telefone, Id Feegow, ações (Visualizar, Obs, [Procedimentos quando existir o módulo]).
  2. **Visualizar** (`pacientes/{id}`): dados completos + foto (da Feegow) + obs local editável.
  3. **Obs** (campo local) — edição inline/modal.
  4. **Sincronização**: página/button "Atualizar da Feegow" com feedback do resultado (quantos criados/atualizados/erros) e histórico das execuções.
- **CRUD local**: o cadastro do paciente é **gerido pela Feegow** (não criar/editar dados vindos dela manualmente); a V2 só edita campos locais (`obs`, `st_google`). (Se quiser criar paciente manualmente, entra como decisão.)

---

## 6. Importação V1 → V2 (padrão da V2)

| Script | O que faz |
|---|---|
| `15_pacientes.php` | Copia `pacientes` da V1 preservando **ids + `id_versao1`** (necessário p/ futuros `procedimentos.paciente_id`); `paciente_id_feegow` mantido (fonte). Corrige: remove `integrado_kamino` (não existe na V2), `st_google` 0/1 → boolean. |
| `16_configuracao.php` | Copia a última data de sincronização (`configuracaos.ultima_atualizacao_pacientes`) para a tabela de controle da V2, para o Feegow continuar de onde parou. |

> **Atenção (bug conhecido)**: nos scripts de migração, usar `DB::table(...)` novo a cada iteração no `where(...)->exists()` (o `where()` acumula no mesmo builder). Idempotente por `id_versao1`.

---

## 7. Decisões tomadas (2026-08-12)

| # | Decisão | Resposta do usuário |
|---|---|---|
| 1 | Escopo | **"O que achares melhor"** → implementar tudo junto (importação V1 + listagem/obs + integração Feegow com botão) |
| 2 | Token Feegow | **O token da V1 está funcionando** → colocar no `.env` da V2 |
| 3 | Sync automático | **Sim, seria ótimo** → comando `php artisan pacientes:sincronizar` + agendamento diário (Scheduler); documentar como rodar (cron/Task Scheduler) |
| 4 | Pacientes que somem da Feegow | **Não ficam visíveis no sistema** — a linha só permanece no BD para histórico (coluna `ativo`, listagem mostra só ativos) |
| 5 | Foto | **Não** puxar/mostrar foto |
| 6 | Cadastro manual | **Só via Feegow** (a V2 só edita campo local `obs`) |
| 7 | Menu | **Só o item "Pacientes"** (após Estoque), com o botão "Atualizar da Feegow" **dentro da página** (só admin) |

---

## 8. Resumo

- Os **pacientes vêm da Feegow** (`paciente_id_feegow` = chave da fonte); a V1 tem 16.890 registros (16.706 ids Feegow distintos) e um **espelho local** atualizado por um sync manual (lento, N+1, token hardcoded).
- A V2 deve **importar da V1** preservando ids + `id_versao1` (referência futura de procedimentos) e manter a **sincronização incremental com a Feegow** de forma robusta: token no `.env`, serviço `FeegowService` (Guzzle), upsert por `paciente_id_feegow`, botão manual + agendamento, e histórico de sincronização.
- **Menu**: guia **"Pacientes"** após **Estoque**, limpa, com listagem/visualização/obs e ação de atualização.

---

## 9. Implementação (2026-08-12)

### O que foi construído

| Componente | Detalhe |
|---|---|
| Migrations | `000130` (pacientes), `000140` (configuracaos com `ultima_atualizacao_pacientes`), `000150` (sincronizacaos), `000160` (alarga `estado` para 60 — V1 grava "São Paulo") |
| Models | `Paciente` (fillable incl. `ativo`, `scopeAtivos`), `Configuracao`, `Sincronizacao` |
| Serviços | `FeegowService` (Guzzle: `pacientesDesde()`, `detalhePaciente()`), `PacienteSincronizacaoService` (upsert incremental, log, sanitiza CPF/datas) |
| Config | `config/feegow.php` + `.env` (`FEEGOW_BASE_URL`, `FEEGOW_TOKEN` = token da V1) |
| Import | `database/migracao/15_pacientes.php` (dedupe por `paciente_id_feegow` mantendo menor id; sanitiza `0000-00-00`), `16_configuracao.php` |
| Controller | `PacienteSistemaController` (index, **datatable** DataTables server-side, show, obs PUT, atualizar POST admin, listarAjax) |
| Comando | `php artisan pacientes:sincronizar` + schedule `dailyAt('04:00')` em `app/Console/Kernel.php` |
| Rotas | `pacientes.*` (index, dados, atualizar admin, buscar/ajax, obs, show) |
| Menu | Item único **Pacientes** (ri-user-3-line) após Estoque; **sem classe active** |
| Views | `pacientes/index.blade.php` (card, botão "Atualizar da Feegow" admin, badge último sync, **DataTables server-side** com assets locais `templates/assets/vendor/libs/datatables-bs5/`, busca/ordenação/paginação no servidor, idioma pt-BR, coluna Ações), `pacientes/show.blade.php` (dados + obs local PUT) |

### Resultados da importação e sync

- **Importação V1**: 16.706 pacientes migrados, **184 duplicados ignorados** (dedupe por `paciente_id_feegow`), `ultima_atualizacao_pacientes = 2026-07-21`.
- **Sync Feegow validado** (comando + botão): `20 criados / 12 atualizados / 0 erros` na 1ª execução via botão (total 16.706 → **16.726**); log em `sincronizacaos` (status, criados, atualizados, erros).
- **Datas da Feegow** chegam como `dd/mm/aaaa` **ou** `dd-mm-aaaa` → `formatarData()` converte para `Y-m-d` (bug de formato corrigido após teste: `09-08-1996`).

### Pendências / anotações

- **Cron em produção**: o agendamento diário (04:00) depende de um cron executando `php artisan schedule:run` a cada minuto no servidor (XAMPP dev não tem cron). Documentar no deploy.
- **Remoção**: pacientes que sumirem da Feegow não são apagados — ficam no BD com `ativo` para histórico (listagem mostra só ativos).
- **Sem foto** (decisão) e **sem cadastro manual** — só via Feegow.
- **Próximo passo natural**: módulo de **Procedimentos** (precisa de `pacientes`/`procedimentos` da V1), que também desbloqueará o script `13_aplicacao_lotes` (adiado).
