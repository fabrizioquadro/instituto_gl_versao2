# 02 — Grupos, Medicamentos, Combos e Soros (V2)

> **Status:** ✅ **Implementado em 2026-08-12** (migrations, models, CRUD, rotas, menu e importação concluídos)
> **Data:** 2026-08-12
> **Objetivo:** Implementar na V2 os módulos de **Grupos**, **Medicamentos** e **Combos** (iguais aos da V1) e criar o módulo novo de **Soros** (mesma estrutura de Combos, com nome "soro"), incluindo a **importação dos dados da V1** no padrão já adotado na V2.

> **Nota de implementação (2026-08-12):** os 2 medicamentos com `unidade='Miligrama'` na V1 eram os **MOUNJARO 90MG e 60MG** — mapeados para `tipo=Vasilhame` com `vasilhame=90` e `60` (preservados da V1). Durante a implementação também foi corrigido um bug de **idempotência** nos scripts de migração (`database/migracao/*.php`): o `$builder->where(...)->exists()` **acumula** as condições no mesmo query builder em Laravel, fazendo a verificação falhar a partir do 2º registro — a correção usa `DB::table(...)` novo a cada iteração.

---

## 1. Contexto

Na V1 estes módulos existem na área administrativa e controlam o **cadastro de medicamentos** (com seus grupos) e os **combos** (conjuntos de medicamentos com quantidade e valor unitário). O usuário pediu:

1. **Grupos, Medicamentos e Combos** — podem ser **iguais aos da V1** (ajustes vêm depois);
2. **Soros** — criar um módulo **novo**, com a **mesma estrutura de Combos**, mas com o nome **"soro"**;
3. Incluir a **importação** dos dados da V1 para a V2, seguindo o padrão que já usamos (tabela `users`/`clinicas`, com `id_versao1`, scripts em `database/migracao/`, comando `php artisan migrar:v1`).

---

## 2. Análise da V1

### 2.1 Estrutura das tabelas (V1)

**`grupos`** (migration `2025_12_22_010151`):

| Coluna | Tipo |
|---|---|
| `id` | bigint PK |
| `nome` | string |
| `timestamps` | |

**`medicamentos`** (migration `2025_07_08_181606` + `2025_12_22_222229`):

| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | |
| `nome` | string | |
| `fabricante` | string | |
| `unidade` | string | ex.: Ampola / Miligrama / Procedimento |
| `vasilhame` | integer nullable | |
| `ultimo_valor_pg` | double nullable | último valor pago |
| `vl_venda` | string(10,2) | valor de venda |
| `estoque_minimo` | double default 0 | |
| `situacao` | string | `Ativo` / `Inativo` |
| `aplicacao` | string(5) | `Sim` / `Não` |
| `aplicacao_feegow_id` | integer nullable | |
| `grupo_id` | FK → `grupos` | adicionado depois |
| `timestamps` | | |

> **Mudanças na V2 (decisão do usuário):**
> - a coluna `unidade` passa a se chamar **`tipo`**, com apenas as opções **`Ampola`**, **`Vasilhame`** e **`Procedimento`**;
> - é criada a coluna **`estoque_medio`** (alerta **amarelo**) — o `estoque_minimo` vira o alerta **vermelho**;
> - o campo **`vasilhame`** só é usado quando `tipo = Vasilhame` e representa o **tamanho do vasilhame** (ex.: 500, 1000...).

**`combos`** (migration `2025_10_03_041210`):

| Coluna | Tipo |
|---|---|
| `id` | bigint PK |
| `nome` | string |
| `timestamps` | |

**`combo_medicamentos`** (pivot, migration `2025_10_03_041221`):

| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | |
| `combo_id` | FK → `combos` | |
| `medicamento_id` | FK → `medicamentos` | |
| `quantidade` | double | |
| `valor_unitario` | double(10,2) | |
| `timestamps` | | |

### 2.2 Contagens atuais na V1 (produção)

| Tabela | Registros |
|---|---|
| `grupos` | 1 |
| `medicamentos` | 69 (68 `Ativo`, 1 `Inativo`; 66 `aplicacao=Sim`, 3 `Não`) |
| `combos` | 43 |
| `combo_medicamentos` | 125 |

### 2.3 Relações (V1)

- `Medicamento` pertence a `Grupo` (`grupo_id`)
- `Combo` tem vários `ComboMedicamento` (`hasMany`)
- `ComboMedicamento` pertence a `Medicamento`

### 2.4 CRUD da V1 (área admin)

Cada módulo segue o mesmo padrão: `index` (listagem), `adicionar`, `editar/{id}`, `excluir/{id}`, `insert`, `update`, `delete`. Os Combos têm recursos extras: adicionar/remover medicamentos dinamicamente e recalcular totais (via Ajax `buscar_medicamentos` / `delete_medicamento`).

---

## 3. Proposta V2 — Schema

### 3.1 Tabelas (migrations)

Seguindo o **padrão da V2** (seção 3.4 do relatório 01): `id_versao1` em **todas** as tabelas migradas 1:1 da V1; `origem_versao1` **apenas** quando houver fusão (não é o caso aqui).

```php
// grupos (1:1 da V1 -> id_versao1)
Schema::create('grupos', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_versao1')->nullable()->index();
    $table->string('nome');
    $table->timestamps();
});

// medicamentos (1:1 da V1 -> id_versao1)
Schema::create('medicamentos', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_versao1')->nullable()->index();
    $table->unsignedBigInteger('grupo_id')->nullable();
    $table->string('nome');
    $table->string('fabricante');
    $table->string('tipo');                          // Ampola | Vasilhame | Procedimento (era 'unidade')
    $table->integer('vasilhame')->nullable();        // tamanho do vasilhame (somente p/ tipo=Vasilhame)
    $table->double('ultimo_valor_pg', 10, 2)->nullable();
    $table->string('vl_venda', 10, 2);
    $table->double('estoque_minimo')->default(0);    // alerta VERMELHO
    $table->double('estoque_medio')->default(0);     // alerta AMARELO (novo)
    $table->string('situacao')->default('Ativo');
    $table->string('aplicacao', 5)->default('Não');
    $table->integer('aplicacao_feegow_id')->nullable();
    $table->timestamps();

    $table->foreign('grupo_id')->references('id')->on('grupos')->onDelete('set null');
});

// combos (1:1 da V1 -> id_versao1)
Schema::create('combos', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_versao1')->nullable()->index();
    $table->string('nome');
    $table->timestamps();
});

// combo_medicamentos (1:1 da V1 -> id_versao1)
Schema::create('combo_medicamentos', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_versao1')->nullable()->index();
    $table->unsignedBigInteger('combo_id');
    $table->unsignedBigInteger('medicamento_id');
    $table->double('quantidade');
    $table->double('valor_unitario', 10, 2);
    $table->timestamps();

    $table->foreign('combo_id')->references('id')->on('combos')->onDelete('cascade');
    $table->foreign('medicamento_id')->references('id')->on('medicamentos');
});
```

> **Nota:** mantive `situacao` como string (`Ativo`/`Inativo`) e `aplicacao` como `Sim`/`Não`, **iguais à V1**, conforme pedido. (Se depois quisermos, dá para converter `situacao` em boolean como fizemos em `users.ativo` — fica registrado como mudança futura.)

### 3.2 Novo módulo: **Soros** (V2, sem origem na V1)

Como o usuário pediu "um igual aos combos, mas com o nome **soro**", criamos:

```php
// soros (NOVO na V2 -> sem id_versao1)
Schema::create('soros', function (Blueprint $table) {
    $table->id();
    $table->string('nome');
    $table->timestamps();
});

// soro_medicamentos (pivot do soro)
Schema::create('soro_medicamentos', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('soro_id');
    $table->unsignedBigInteger('medicamento_id');
    $table->double('quantidade');
    $table->double('valor_unitario', 10, 2);
    $table->timestamps();

    $table->foreign('soro_id')->references('id')->on('soros')->onDelete('cascade');
    $table->foreign('medicamento_id')->references('id')->on('medicamentos');
});
```

> Por ser **nativo da V2** (não existe na V1), a tabela `soros` **não** recebe `id_versao1`. Se no futuro houver uma fonte V1 para soros, adiciona-se o campo na migration.

### 3.3 Models

```php
// Grupo
class Grupo extends Model {
    protected $fillable = ['id_versao1', 'nome'];
    public function medicamentos() { return $this->hasMany(Medicamento::class); }
}

// Medicamento
class Medicamento extends Model {
    protected $fillable = [
        'id_versao1', 'grupo_id', 'nome', 'fabricante', 'tipo', 'vasilhame',
        'ultimo_valor_pg', 'vl_venda', 'estoque_minimo', 'estoque_medio',
        'situacao', 'aplicacao', 'aplicacao_feegow_id',
    ];
    public function grupo() { return $this->belongsTo(Grupo::class); }
}

// Combo
class Combo extends Model {
    protected $fillable = ['id_versao1', 'nome'];
    public function medicamentos() { return $this->hasMany(ComboMedicamento::class); }
}

// ComboMedicamento
class ComboMedicamento extends Model {
    protected $fillable = ['id_versao1', 'combo_id', 'medicamento_id', 'quantidade', 'valor_unitario'];
    public function medicamento() { return $this->belongsTo(Medicamento::class); }
}

// Soro (igual ao Combo)
class Soro extends Model {
    protected $fillable = ['nome'];
    public function medicamentos() { return $this->hasMany(SoroMedicamento::class); }
}

// SoroMedicamento
class SoroMedicamento extends Model {
    protected $fillable = ['soro_id', 'medicamento_id', 'quantidade', 'valor_unitario'];
    public function medicamento() { return $this->belongsTo(Medicamento::class); }
}
```

---

## 4. CRUD, rotas e menu (V2)

- Controllers: `GrupoAdmController`, `MedicamentoAdmController`, `ComboAdmController`, `SoroAdmController` (padrão `Route::resource` + métodos extra para o combo/soro).
- Todos **somente admin** (middleware `admin`, já existente) — mesma área de Configurações.
- Rotas (padrão):
  ```
  configuracoes/grupos        (config.grupos.*)
  configuracoes/medicamentos  (config.medicamentos.*)
  configuracoes/combos        (config.combos.*)
  configuracoes/soros         (config.soros.*)
  ```
- Menu: adicionar subitens **Grupos**, **Medicamentos**, **Combos** e **Soros** dentro de **Configurações** (só admin).
- Views em `resources/views/config/{grupos,medicamentos,combos,soros}/*` (listar/adicionar/visualizar/editar), usando o mesmo layout e o dropdown de ações (três pontinhos) já adotado.

**Combos/Soros** precisam de UI dinâmica para adicionar vários medicamentos (linhas com medicamento + quantidade + valor + total), como na V1.

---

## 5. Importação V1 → V2 (padrão da V2)

Scripts em `database/migracao/` (executados pelo `php artisan migrar:v1`, em ordem numérica, em transação, idempotentes):

| Script | O que faz |
|---|---|
| `02_grupos.php` | Copia `grupos` da V1 (preserva ids + `id_versao1`) |
| `03_medicamentos.php` | Copia `medicamentos` (preserva ids + `id_versao1`; `grupo_id` continua válido pois grupos preservaram ids). **Mapeia `unidade` → `tipo`**: `Ampola`→`Ampola`, `Procedimento`→`Procedimento`, `Miligrama`→`Vasilhame` (decisão do usuário 2026-08-12). Seta `estoque_medio = 0` (não existe na V1). |
| `04_combos.php` | Copia `combos` **e** `combo_medicamentos` (ids preservados; FKs continuam válidas) |

Regras (mesmas de `00_clinicas`/`01_users`):
- Preservar os **ids originais** de `grupos`, `medicamentos`, `combos` e `combo_medicamentos` (as FKs entre eles dependem disso).
- Preencher `id_versao1` com o id antigo (origem única → sem `origem_versao1`).
- **Idempotente**: verificar por `id_versao1` antes de inserir.
- Leitura via conexão `mysql_versao1` (env `DB_V1_*`).

**Soros** não entra na importação (é módulo novo da V2).

---

## 6. Passos de implementação (V2)

1. Migrations: `grupos`, `medicamentos`, `combos`, `combo_medicamentos` (com `id_versao1`) + `soros`, `soro_medicamentos`.
2. Models: `Grupo`, `Medicamento`, `Combo`, `ComboMedicamento`, `Soro`, `SoroMedicamento`.
3. Controllers + rotas (`config.*`, middleware `admin`) + menu em Configurações.
4. Views (listar/adicionar/visualizar/editar) + UI dinâmica de medicamentos para Combo/Soro.
5. Scripts de migração de dados (`02_grupos`, `03_medicamentos`, `04_combos`) + rodar `php artisan migrar:v1`.
6. Validar CRUD e importação.

---

## 7. Riscos e considerações

| Risco | Mitigação |
|---|---|
| FKs entre grupos/medicamentos/combos | Preservar ids originais na importação |
| **2 medicamentos com `unidade='Miligrama'` na V1** | Mapeados para `tipo=Vasilhame` (decisão do usuário 2026-08-12) |
| Campo `vasilhame` condicional ao `tipo` | No formulário, exibir `vasilhame` **apenas quando `tipo=Vasilhame`** (JS) e validar no backend |
| `estoque_medio` não existe na V1 | Iniciar com `0` na importação; valores de alerta serão definidos manualmente |
| `situacao`/`aplicacao` como string (legado) | Manter igual à V1 por ora; converter p/ boolean depois se desejado (como `users.ativo`) |
| Combos/Soros com UI dinâmica (várias linhas) | Replicar a lógica de Ajax da V1 (buscar medicamento + recalcular totais) |
| Ordem de importação (grupos antes de medicamentos; medicamentos antes de combos) | Scripts numerados em ordem: `02_grupos` → `03_medicamentos` → `04_combos` |

---

## 8. Resumo

- **Grupos, Medicamentos e Combos** são replicados da V1 (iguais, com `id_versao1` para rastreio) e importados via `migrar:v1`.
- **Soros** é um módulo **novo da V2**, espelhando a estrutura de Combos (`soros` + `soro_medicamentos`).
- Tudo sob a área de **Configurações** (admin), com o mesmo padrão de CRUD, rotas e layout já adotado na V2.
