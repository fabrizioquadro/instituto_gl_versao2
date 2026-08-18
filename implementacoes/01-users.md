# 01 — Implantação de Usuários (V2)

> **Status:** Em implementação (parcialmente concluído em 2026-08-12)
> **Data:** 2026-08-12
> **Objetivo:** Unificar as tabelas `users` e `administradors` da V1 em uma única tabela `users` na V2, eliminando os problemas causados pela separação.

### Status da implementação (2026-08-12)

**Já implementado na V2:**

- ✅ **Tabela `clinicas`** criada (migration `2014_10_11_000000_create_clinicas_table.php`, mesma estrutura da V1) + coluna **`id_versao1`** adicionada pela migration `2026_08_12_000000_add_id_versao1_to_clinicas_table.php` (correção). Model `App\Models\Clinica` criado.
- ✅ **Tabela `users`** unificada (migration `2014_10_12_000000_create_users_table.php`) com todas as colunas da seção 3.2.
- ✅ **Model `App\Models\User`** com `role`, helpers e casts (seção 3.3).
- ✅ **Autenticação** (`AuthController` + rotas `/`, `/login`, `/logout`, `/home`) — login validado de ponta a ponta.
- ✅ **Conexão `mysql_versao1`** (`config/database.php` + `DB_V1_*` no `.env`), comando `php artisan migrar:v1` e script `database/migracao/01_users.php`.
- ✅ **Migração de dados executada** em 2026-08-12 via `php artisan migrar:v1` (produção V1 → V2): **4 clínicas** e **78 usuários** (57 `users` + 21 `administradores`). E-mails duplicados tratados como **admin** (decisão do usuário).

**Pendente:**

- ⏳ Remapeamento das FKs `administrador_id`/`usuario_id` quando as demais tabelas (transferencias, procedimento_logs, etc.) forem migradas.

---

## 1. Contexto

Na V1 existem **duas tabelas de usuários**:

| Tabela | Registros | Finalidade |
|---|---|---|
| `users` | 61 | Usuários operacionais do sistema (`tipo`: `Secretária` / `Enfermagem`) |
| `administradors` | 21 | Administradores do sistema (área `/adm`) |

Essa separação foi a causa de diversos problemas relatados. Este documento analisa a situação atual da V1, aponta os problemas e propõe o desenho da tabela unificada `users` para a V2, junto com a estratégia de migração.

---

## 2. Análise da V1 — as duas tabelas

### 2.1 Estrutura atual

**`administradors` (migration `2025_07_06_225158`):**

| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | |
| `nome` | string | |
| `email` | string | **sem `unique`** |
| `password` | string | |
| `imagem` | string nullable | |
| `st_usuario` | string default `Ativo` | adicionado depois (`2026_05_06`) |
| `timestamps` | | |

**`users` (migration `2025_07_07_210000`):**

| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | |
| `clinica_id` | FK → `clinicas` | **obrigatório** |
| `nome` | string | |
| `email` | string **unique** | |
| `password` | string | |
| `tipo` | string | `Secretária` / `Enfermagem` |
| `coren` | string nullable | |
| `imagem` | string nullable | |
| `imagem_carimbo` | string nullable | |
| `senha_certificado` | string nullable | |
| `dashboard_sec` | string(5) nullable | flag |
| `dashboard_enf` | string(5) nullable | flag |
| `controle_medicamentos` | string(5) | permissão |
| `pacientes` | string(5) | permissão |
| `procedimentos` | string(5) | permissão |
| `financeiro` | string(5) | permissão |
| `st_usuario` | string default `Ativo` | adicionado depois (`2026_05_06`) |
| `timestamps` | | |

### 2.2 Como o login funciona hoje (V1)

O `LoginController@login` usa um fluxo **duplo e frágil**:

1. Busca primeiro em `Administrador` pelo e-mail;
2. Se achou admin: confere a senha com `Hash::check`, grava `session('administrador')`, **cria um objeto `User` fake com `id = 0`**, nome `"Adm"` e `tipo = "Secretária"` e grava também em `session('user')`; redireciona para a área admin;
3. Se não achou admin: usa `Auth::attempt` contra a tabela `users`, grava `session('user')` real e vai para a área sistema.

### 2.3 Problemas identificados (V1)

1. **Autenticação de administrador fora do padrão do Laravel** — admins **não são autenticados de verdade** pelo guard `web`. Eles são um objeto `User` falso com `id = 0` na sessão. Isso quebra qualquer código que use `auth()->user()` ou FK para `users` quando quem agiu foi um admin.

2. **IDs "fantasma"** — como o admin vira um `User` com `id = 0`, registros criados por admin ficam com `user_id = 0` (ou null), gerando inconsistência e impossibilitando rastrear quem fez o quê.

3. **Duas colunas de autor para a mesma coisa** — várias tabelas acabaram com os dois campos:
   - `transferencias`: tem `user_id` **e** `administrador_id`
   - `procedimento_logs`: tem `usuario_id` **e** `administrador_id`
   Isso obriga o código a decidir qual campo usar a cada operação, e as queries/relatórios precisam de lógica dupla.

4. **Permissões e papéis (roles) não centralizados** — admins não têm `tipo`, nem permissões por módulo. As permissões de usuário (`controle_medicamentos`, `pacientes`, `procedimentos`, `financeiro`) são colunas `string(5)` do tipo "S/N", e o admin simplesmente "passa por cima" de tudo. Não existe um modelo único de papel/permissão.

5. **Login com código duplicado e mensagens divergentes** — o mesmo fluxo (checar e-mail, verificar `st_usuario`, resetar senha) está duplicado para as duas tabelas em `login()` e `recuperar_senha()`. Toda correção precisa ser feita 2x.

6. **`email` sem `unique` em `administradors`** — permite e-mails duplicados e colisão na migração.

7. **Clínica obrigatória para usuário, inexistente para admin** — `clinica_id` é NOT NULL em `users`, mas admins não possuem clínica. Isso gera a necessidade de "inventar" um valor (no login fake, o admin recebe a primeira clínica).

8. **Relatórios e auditoria confusos** — para saber quem executou uma ação, é preciso consultar `users` OU `administradors`, dependendo da coluna preenchida.

---

## 3. Proposta: Tabela unificada `users` (V2)

### 3.1 Princípios

- **Um único modelo de usuário** (`App\Models\User`) autenticado pelo guard padrão `web` do Laravel.
- **Um campo `role`** define o nível de acesso: `admin`, `secretaria` ou `enfermagem`.
- **Permissões por módulo** mantidas como campos booleanos, aplicáveis a qualquer papel.
- **`clinica_id` nullable** — admins não são obrigados a pertencer a uma clínica; usuários operacionais sim.
- **Auditoria centralizada**: qualquer ação registra o `id` real do `users` que a executou.

### 3.2 Desenho da nova tabela `users`

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('id_versao1')->nullable()->index();     // id original na V1 (rastreabilidade)
    $table->string('origem_versao1')->nullable();                      // tabela de origem na V1: 'users' | 'administradores'
    $table->unsignedBigInteger('clinica_id')->nullable();          // admins podem ficar sem clínica
    $table->string('nome');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('role')->default('secretaria');                 // admin | secretaria | enfermagem
    $table->string('coren')->nullable();                           // enfermagem
    $table->string('imagem')->nullable();
    $table->string('imagem_carimbo')->nullable();                  // enfermagem
    $table->string('senha_certificado')->nullable();
    $table->boolean('dashboard_secretaria')->default(false);       // substitui dashboard_sec
    $table->boolean('dashboard_enfermagem')->default(false);       // substitui dashboard_enf
    $table->boolean('controle_medicamentos')->default(false);
    $table->boolean('pacientes')->default(false);
    $table->boolean('procedimentos')->default(false);
    $table->boolean('financeiro')->default(false);
    $table->boolean('ativo')->default(true);                       // substitui st_usuario (Ativo/Inativo)
    $table->rememberToken();
    $table->timestamps();

    $table->foreign('clinica_id')->references('id')->on('clinicas')->onDelete('set null');
});
```

> **Nota de design:** preferi `boolean` para as permissões e para `ativo`, em vez dos `string(5)` (`'S'/'N'`) e `st_usuario` (`'Ativo'/'Inativo'`) da V1 — mais limpo e com menos chance de erro de digitação. O mapeamento está na seção 4.

### 3.3 Modelo único

```php
// app/Models/User.php (V2)
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'id_versao1', 'origem_versao1',
        'clinica_id', 'nome', 'email', 'password', 'role',
        'coren', 'imagem', 'imagem_carimbo', 'senha_certificado',
        'dashboard_secretaria', 'dashboard_enfermagem',
        'controle_medicamentos', 'pacientes', 'procedimentos', 'financeiro',
        'ativo',
    ];

    protected $casts = [
        'password' => 'hashed',
        'ativo' => 'boolean',
        'dashboard_secretaria' => 'boolean',
        'dashboard_enfermagem' => 'boolean',
        'controle_medicamentos' => 'boolean',
        'pacientes' => 'boolean',
        'procedimentos' => 'boolean',
        'financeiro' => 'boolean',
    ];

    public function clinica() { return $this->belongsTo(Clinica::class); }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isSecretaria(): bool { return $this->role === 'secretaria'; }
    public function isEnfermagem(): bool { return $this->role === 'enfermagem'; }
}
```

### 3.4 Padrão das colunas legadas da V1 (regra do sistema V2)

- **`id_versao1`** — **todas** as tabelas migradas da V1 (mapeamento 1:1) terão esta coluna, guardando o id original da V1. Ex.: `users` e `clinicas`.
- **`origem_versao1`** — apenas quando houver **fusão** de duas ou mais tabelas da V1 em uma só na V2 (ex.: `users` + `administradors` → `users`), para registrar a tabela de origem.

---

## 4. Estratégia de migração de dados (V1 → V2)

### 4.1 Mapeamento por registro

Cada registro de `administradors` vira um `users` com:

| `administradors` (V1) | `users` (V2) | Regra |
|---|---|---|
| `id` | `id` | **novo id** gerado (ver 4.3) |
| `id` | `id_versao1` | id original da V1 |
| — | `origem_versao1` | `'administradores'` |
| `nome` | `nome` | direto |
| `email` | `email` | direto (checar conflito, ver 4.2) |
| `password` | `password` | direto (já é hash) |
| `imagem` | `imagem` | direto |
| `st_usuario` | `ativo` | `'Ativo'` → `true`; `'Inativo'` → `false` |
| — | `role` | `'admin'` |
| — | `clinica_id` | `NULL` |
| — | `coren`, `imagem_carimbo`, `senha_certificado` | `NULL` |
| — | `dashboard_*` | `true` para ambos (admin vê tudo) |
| — | permissões | `true` para todas (admin tem acesso total) |

Cada registro de `users` (V1) vira um `users` (V2) com:

| `users` (V1) | `users` (V2) | Regra |
|---|---|---|
| `id` | `id` | **novo id** (ver 4.3) |
| `id` | `id_versao1` | id original da V1 |
| — | `origem_versao1` | `'users'` |
| `clinica_id` | `clinica_id` | direto |
| `nome`, `email`, `password`, `coren`, `imagem`, `imagem_carimbo`, `senha_certificado` | idem | direto |
| `tipo` | `role` | `'Secretária'` → `'secretaria'`; `'Enfermagem'` → `'enfermagem'` |
| `st_usuario` | `ativo` | `'Ativo'` → `true`; `'Inativo'` → `false` |
| `dashboard_sec` | `dashboard_secretaria` | `'S'` → `true`, senão `false` |
| `dashboard_enf` | `dashboard_enfermagem` | `'S'` → `true`, senão `false` |
| `controle_medicamentos`, `pacientes`, `procedimentos`, `financeiro` | idem (boolean) | `'S'` → `true`, senão `false` |

### 4.2 Conflito de e-mails

Como `users.email` é `unique` e `administradors.email` **não** era, é preciso verificar antes da migração se existe algum e-mail repetido entre as duas tabelas. Se houver, o caso deve ser tratado manualmente (renomear, inativar duplicado, etc.). **É obrigatório validar antes de rodar o script de migração.**

### 4.3 Remapeamento de IDs (crítico)

Os ids de `users` (1..61) e `administradors` (1..21) **são sequências independentes e podem colidir** (ex.: admin id 5 ≠ user id 5). Ao unificar:

1. Migrar primeiro os `users` da V1, preservando os ids originais (1..61) — sem conflito, pois são a base.
2. Migrar os `administradors` com **novos ids** (61+1 .. 61+21). O mapeamento `antigo_id_admin → novo_id_user` fica **gravado na própria tabela** via `id_versao1 = antigo id` e `origem_versao1 = 'administradores'` — **dispensa a tabela de mapeamento separada**.
3. Atualizar todas as referências:

**Tabelas com `administrador_id` que precisam ser remapeadas:**
- `transferencias.administrador_id`
- `procedimento_logs.administrador_id`

**Tabelas com `user_id` / `usuario_id` que já apontam para `users`:** não precisam de remapeamento, mas os registros gravados por admins (que ficaram com `id = 0` ou null na V1) devem ser revisados — o `id = 0` não existe na V2.

> **Sugestão:** na V2, **todas** as referências de autor passam a usar uma única coluna (ex.: `user_id`). As colunas `administrador_id` e `usuario_id` **não existem** na V2.

### 4.4 Infraestrutura de migração de dados (conexão V1 + pasta `database/migracao/`)

As migrations de **dados** (V1 → V2) ficam **separadas** das migrations de **schema** (`database/migrations`), para não misturar os dois fluxos no `php artisan migrate`.

**1. Conexão com o banco antigo via `.env`**

```dotenv
# Conexão com o banco da V1 (somente leitura)
DB_V1_HOST=127.0.0.1
DB_V1_PORT=3306
DB_V1_DATABASE=u528878205_dev
DB_V1_USERNAME=
DB_V1_PASSWORD=
```

Registrada em `config/database.php` como conexão secundária:

```php
'mysql_versao1' => [
    'driver' => 'mysql',
    'host' => env('DB_V1_HOST', '127.0.0.1'),
    'port' => env('DB_V1_PORT', '3306'),
    'database' => env('DB_V1_DATABASE'),
    'username' => env('DB_V1_USERNAME'),
    'password' => env('DB_V1_PASSWORD'),
    // charset, collation etc. iguais à conexão padrão
],
```

**2. Pasta dedicada para os scripts de migração de dados**

```
database/migracao/
├── 01_users.php        <- usuários + administradores (este documento)
├── 02_clinicas.php     <- futuras migrações de dados
└── 03_...
```

> Recomendo `database/migracao/` **sem acento** para evitar problemas de encoding/caminho em namespaces e comandos.

**3. Comando Artisan para executar em ordem**

```bash
php artisan migrar:v1
```

O comando `app/Console/Commands/MigrarV1.php`:
- carrega os scripts da pasta `database/migracao/` em ordem numérica;
- executa cada um dentro de uma transação no banco **destino** (V2);
- usa a conexão `mysql_versao1` para **ler** os dados antigos;
- é **idempotente** (verifica se já migrou via `id_versao1` + `origem_versao1`).

Assim, `php artisan migrate` continua cuidando **só do schema** da V2, e a importação da V1 fica isolada e reexecutável.

---

## 5. Autenticação unificada (V2)

```php
// routes/web.php
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// AuthController@login (exemplo)
public function login(Request $request)
{
    $credentials = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials + ['ativo' => true], $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended($this->homePorRole(auth()->user()));
    }

    return back()->withErrors(['email' => 'E-mail ou senha inválidos.']);
}

private function homePorRole(User $user): string
{
    return match ($user->role) {
        'admin'       => route('adm.dashboard'),
        'secretaria'  => route('sistema.dashboard'),
        'enfermagem'  => route('sistema.dashboard'),
    };
}
```

**O que melhora em relação à V1:**
- Um único `Auth::attempt` cobre admin e usuário (não há mais objeto `User` fake);
- `auth()->user()` funciona para todos, com `id` real;
- O redirect pós-login é decidido pelo `role`;
- `st_usuario` vira o filtro `ativo = true` na autenticação (usuário inativo não loga);
- Reset de senha: um único fluxo (Laravel `ForgotPasswordController` padrão) para todos.

---

## 6. Passos de implementação (V2)

1. **Criar migration** da tabela unificada `users` (desenho da seção 3.2) — ✅ **concluído**. Também foi criada a tabela **`clinicas`** (migration `2014_10_11_000000_create_clinicas_table.php`, mesmo desenho da V1), pois `users.clinica_id` referencia `clinicas`.
2. **Criar/ajustar o model** `User` (seção 3.3) com `role` e helpers.
3. **Configurar `config/auth.php`** — guard `web` → `App\Models\User` (padrão já é esse).
4. **Criar tela de login** usando o template (já temos a view `auth/login.blade.php` pronta — basta ligar o POST `/login` ao controller).
5. **Definir o fluxo pós-login** por `role` (seção 5) e os middlewares de autorização (ex.: `can:admin`, `role:admin`).
6. **Migração de dados** (V1 → V2) — via comando `php artisan migrar:v1`, scripts da pasta `database/migracao/` (ver seção 4.4):
   - script que valida e-mails duplicados (4.2);
   - insere `users` da V1;
   - insere `administradors` como novos `users` com `role = admin`, registrando o mapeamento de ids (4.3);
   - remapeia `transferencias.administrador_id` e `procedimento_logs.administrador_id`;
   - revisa registros com `user_id = 0`/null.
7. **Unificar as colunas de autor** nas tabelas novas da V2 (sempre `user_id`).
8. **Ajustar CRUD de usuários/administradores** — na V2 há um único gerenciamento com campo `role`, em vez de duas telas separadas.
9. **Relatórios e auditoria** passam a consultar só `users`.

---

## 7. Riscos e considerações

| Risco | Mitigação |
|---|---|
| E-mail duplicado entre as duas tabelas | Script de pré-validação (4.2) antes de migrar; tratar manualmente |
| Colisão de ids ao unificar | Remapear admins com novos ids; mapeamento gravado via `id_versao1` + `origem_versao1` na própria tabela (4.3) |
| Registros antigos com `user_id = 0` (admin fake) | Auditoria/limpeza na V1 antes da extração, ou tratar como "usuário removido" |
| Mudança de `st_usuario`/permissões string→boolean | Mapeamento explícito no script de migração |
| Perda de referências em tabelas com `administrador_id` | Remapeamento das FKs (4.3) |
| Acúmulo de dados legados (`id_versao1`/`origem_versao1`) | Manter por período de estabilização/auditoria; remover depois (opcional) |

---

## 8. Resumo

Unificar `users` + `administradors` em uma única tabela `users` com campo `role` **resolve a raiz dos problemas da V1**:
- autenticação 100% padrão Laravel (sem usuário fake);
- auditoria com id real e uma única coluna de autor;
- permissões centralizadas por papel + módulo;
- um único CRUD de pessoas;
- código de login/reset de senha sem duplicação.

A migração é viável com os mapeamentos descritos (seção 4), sendo a validação de e-mails duplicados e o remapeamento de ids os dois pontos de atenção.
