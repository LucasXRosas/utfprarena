# Guia Prático para a Validação do CRUD — UTFPR Arena

Roteiro de consulta rápida para a validação presencial do **CRUD de atletas** (`/admin/users`).

Na validação da autenticação, o professor não pediu "mude um texto". Ele pediu **uma funcionalidade nova que atravessa todas as camadas**: novo tipo de usuário + nova página de login + geração de código aleatório + regra de acesso. Então espere pedidos do mesmo porte para o CRUD, por exemplo:

| # | Pedido provável do professor | Camadas que mexe | Seção |
|---|---|---|---|
| 1 | "Adicione o campo X (data de nascimento, categoria…) no cadastro, na edição e na listagem" | banco + model + controller + 3 views + seed | [§2](#2-cenário-1--novo-campo-no-crud-ponta-a-ponta) |
| 2 | "O CPF precisa ser válido e único" / "senha com no mínimo 6 caracteres" | lib de validação + model + mensagens | [§3](#3-cenário-2--novas-regras-de-validação) |
| 3 | "Coloque uma busca por nome/e-mail e um filtro por status na listagem, sem quebrar a paginação" | paginator + model + controller + view | [§4](#4-cenário-3--busca-filtro-e-ordenação-na-listagem-paginada) |
| 4 | "Crie um botão que gera uma nova senha aleatória para o atleta" (parecido com o código aleatório da validação passada) | rota + controller + model + view | [§5](#5-cenário-4--ação-customizada-gerar-senha-aleatória) |
| 5 | "Não deixe excluir atleta com mensalidade em aberto" / "em vez de excluir, desative" | controller + model + view | [§6](#6-cenário-5--regras-de-negócio-na-exclusão-e-desativar-em-vez-de-excluir) |
| 6 | "Faça um CRUD de outra entidade" (ex.: **mensalidades do atleta**, relação 1xN) | tudo: rotas, controller novo, views novas | [§7](#7-cenário-6--crud-completo-de-outra-entidade-mensalidades-do-atleta) |
| 7 | "Mude a paginação para N por página" / "ordene por nome" | controller | [§8](#8-cenário-7--alterações-rápidas-na-paginação) |
| 8 | "Agora escreva um teste para isso" | testes de controller e aceitação | [§9](#9-cenário-8--testar-o-que-foi-alterado) |

> ⚠️ **Antes de começar qualquer pedido, crie uma branch descartável:**
> `git checkout -b validacao-crud`. Depois da validação, descarte com `git checkout main && git branch -D validacao-crud`.
>
> ⚠️ **Os trechos deste guia não estão aplicados no projeto.** Eles foram escritos em cima do código atual da `main` para serem colados durante a validação. Sempre rode `php -l arquivo.php` e teste no navegador depois de colar.

---

## 1. Mapa do CRUD atual (decore isto)

### 1.1 Fluxo de uma requisição do CRUD

```
Navegador ── POST /admin/users/5  (_method=PUT)
   │
   ▼
public/index.php → config/bootstrap.php → Router::init()
   │
   ▼
config/routes.php  ── middleware('auth') → middleware('admin')
   │                     Authenticate.php     AuthorizeAdmin.php
   ▼
App\Controllers\Admin\UsersController::update(Request $request)
   │   $request->getParam('id')           ← vem do {id} da URL
   │   $this->extractParam($request,'name') ← lê user[name] do formulário
   ▼
App\Models\User  (ActiveRecord: save / update / destroy / findById / paginate)
   │
   ▼
FlashMessage::success(...) + $this->redirectTo(route('admin.users.index'))   ← padrão PRG
```

### 1.2 As 7 ações RESTful + 2 ações extras

| Verbo | URL | Action (`UsersController`) | Linha | View |
|---|---|---|---|---|
| GET | `/admin/users` | `index` (lista paginada + métricas) | 18 | `admin/users/index.phtml` |
| GET | `/admin/users/new` | `new` (form vazio) | 64 | `admin/users/new.phtml` |
| POST | `/admin/users` | `create` | 79 | — (redireciona) |
| GET | `/admin/users/{id}` | `show` | 131 | `admin/users/show.phtml` |
| GET | `/admin/users/{id}/edit` | `edit` | 155 | `admin/users/edit.phtml` |
| PUT | `/admin/users/{id}` | `update` | 177 | — (redireciona) |
| DELETE | `/admin/users/{id}` | `destroy` | 244 | — (redireciona) |
| POST | `/admin/users/{id}/block` | `block` | 276 | — |
| POST | `/admin/users/{id}/activate` | `activate` | 300 | — |

Helpers privados do controller:
- `validateIdParam($request)` (linha 321): lê e valida o `{id}` e redireciona com erro se for inválido.
- `extractParam($request, 'campo', $default)` (linha 333): lê `user[campo]` **ou** `campo` do formulário.

### 1.3 Detalhes que o professor costuma perguntar

- **PUT e DELETE não existem em formulário HTML.** O form manda `POST` com `<input type="hidden" name="_method" value="PUT">`, e o `Request` usa `$_REQUEST['_method']` como verbo (`core/Http/Request.php`, construtor).
- **Ordem das rotas importa:** `/admin/users/new` está **antes** de `/admin/users/{id}`, senão "new" seria lido como id. O roteador testa as rotas na ordem em que foram registradas.
- **`{id}` vira parâmetro:** `Route::isSameUri()` coloca `id` em `$request->getParams()`. O nome do parâmetro só aceita `[a-z_]` (ex.: `{user_id}` funciona, `{userId}` **não**).
- **`save()` × `update([...])`:** `save()` roda `validates()` e grava **todas** as colunas. `update(['coluna' => valor])` grava só aquelas colunas e **não valida**.
- **Senha só é "hasheada" na criação:** `User::__set` só gera `encrypted_password` se `newRecord()`. Na edição use `$user->changePassword($nova)`.
- **Unicidade na edição:** `Validations::uniqueness` compara com o próprio registro, então editar sem mudar o e-mail não gera erro.
- **Mensagem flash não é escapada** (`_flash_message.phtml` faz `<?= $msg ?>`). Sempre use `htmlspecialchars()` em dado que vem do usuário dentro da mensagem.
- **Testes de aceitação dependem do populate:** `UsersCrudCest` usa IDs fixos (`/admin/users/4/edit`, `#btn_delete_user_5`). Se mudar o seed, os testes podem quebrar.

---

## 2. Cenário 1 — Novo campo no CRUD ponta a ponta

**Exemplo:** "Adicione **data de nascimento** (`birth_date`) no cadastro, na edição, na listagem e no detalhe, e mostre a idade."
Serve igual para `category`, `ranking`, `matricula` etc.

### Passo 1 — Banco (`database/schema.sql`)

```sql
    cpf VARCHAR(14) NULL,
    birth_date DATE NULL,          -- NOVO
    role VARCHAR(30) NOT NULL DEFAULT 'student',
```

```bash
./run db:reset && ./run db:populate
```

> Alternativa sem resetar (não é reproduzível, use só se tiver pressa):
> `./run db:console` → `ALTER TABLE users ADD COLUMN birth_date DATE NULL AFTER cpf;`

### Passo 2 — Model (`app/Models/User.php`)

1. PHPDoc no topo: ` * @property ?string $birth_date`
2. **Obrigatório:** registrar a coluna no `$columns` (linha 35). Sem isso dá `Property birth_date not found in App\Models\User`.
   ```php
       'cpf',
       'birth_date',   // NOVO
       'remember_token',
   ```
3. Método de domínio (cole perto de `isActive()`):
   ```php
   public function age(): ?int
   {
       if ($this->birth_date === null || $this->birth_date === '') {
           return null;
       }

       return (new \DateTime($this->birth_date))->diff(new \DateTime('today'))->y;
   }
   ```
4. Validação (veja `Validations::pastDate` no [§3.2](#32-data-válida-e-não-futura)) dentro de `validates()`:
   ```php
   Validations::pastDate('birth_date', $this);
   ```

### Passo 3 — Controller (`app/Controllers/Admin/UsersController.php`)

**`create()`** (linha 79): leia o campo junto com os outros e passe no `new User([...])`:
```php
$birthDate = trim((string) $this->extractParam($request, 'birth_date'));
// ...
$targetUser = new User([
    // ...
    'cpf' => $cpf !== '' ? $cpf : null,
    'birth_date' => $birthDate !== '' ? $birthDate : null,   // NOVO
    // ...
]);
```

**`update()`** (linha 177), perto de `$targetUser->cpf = ...` (linha 219):
```php
$birthDate = trim((string) $this->extractParam($request, 'birth_date'));
$targetUser->birth_date = $birthDate !== '' ? $birthDate : null;
```

> **Por que `'' → null`?** O `<input type="date">` vazio manda `""`. Gravar `""` numa coluna `DATE` dá erro no MySQL em modo estrito.

### Passo 4 — Formulários

`app/views/admin/users/new.phtml` (dentro do `<div class="row g-3">`, perto do CPF):
```php
<div class="col-md-6">
    <label for="user_birth_date" class="form-label fw-semibold">Data de Nascimento</label>
    <input type="date" class="form-control" id="user_birth_date" name="user[birth_date]">
</div>
```

`app/views/admin/users/edit.phtml` (mesmo bloco, **com `value`**):
```php
<div class="col-md-6">
    <label for="user_birth_date" class="form-label fw-semibold">Data de Nascimento</label>
    <input type="date" class="form-control" id="user_birth_date" name="user[birth_date]"
           value="<?= htmlspecialchars($targetUser->birth_date ?? '') ?>">
</div>
```
> O `input type="date"` envia e recebe `AAAA-MM-DD`, o mesmo formato do MySQL, então não precisa converter.

### Passo 5 — Listagem e detalhe

`app/views/admin/users/index.phtml`: na coluna "Atleta / Contato", abaixo do telefone/CPF:
```php
<small class="text-muted d-block">
    <i class="bi bi-cake"></i>
    <?= $u->birth_date ? date('d/m/Y', strtotime($u->birth_date)) . ' (' . $u->age() . ' anos)' : 'Nascimento N/I' ?>
</small>
```

`app/views/admin/users/show.phtml`: novo `<li>` na lista de dados cadastrais:
```php
<li class="list-group-item d-flex justify-content-between align-items-center px-0">
    <span>Nascimento:</span>
    <span class="text-dark fw-semibold" id="athlete_birth_date">
        <?= $targetUser->birth_date ? date('d/m/Y', strtotime($targetUser->birth_date)) . ' (' . $targetUser->age() . ' anos)' : 'Não informado' ?>
    </span>
</li>
```

### Passo 6 — Seed (`database/Populate/populate.php`)

Adicione `'birth_date' => '2000-05-20'` em um ou dois usuários e rode `./run db:populate`.

### ✅ Checklist do campo novo
- [ ] `schema.sql` + `db:reset`
- [ ] `$columns` do model (a pegadinha nº 1)
- [ ] `create()` **e** `update()`
- [ ] `new.phtml` **e** `edit.phtml` (com `value`)
- [ ] `index.phtml` e/ou `show.phtml`
- [ ] seed

---

## 3. Cenário 2 — Novas regras de validação

As validações vivem em `lib/Validations.php` e são chamadas no `validates()` do model. Quando uma falha, ela faz `$obj->addError('campo', 'mensagem')`, o `save()` retorna `false` e o controller mostra `$targetUser->firstError()`.

> 💡 O controller hoje mostra só a mensagem (`'Erro ao cadastrar: ' . $errorMsg`). Para mostrar **qual campo** falhou, troque por:
> ```php
> $errors = $targetUser->getErrors();          // ['cpf' => 'deve estar no formato...']
> $campo = array_key_first($errors);
> FlashMessage::danger('Erro ao cadastrar: ' . $campo . ' ' . $errors[$campo]);
> ```

### 3.1 Formato por expressão regular (CPF, telefone, CEP…)

Em `lib/Validations.php`:
```php
/**
 * Valida o atributo contra uma expressão regular. Campo vazio passa (obrigatoriedade é do notEmpty).
 */
public static function format($attribute, $obj, $regex, $message = 'formato inválido!')
{
    $value = $obj->$attribute;
    if ($value === null || $value === '') {
        return true;
    }

    if (!preg_match($regex, (string) $value)) {
        $obj->addError($attribute, $message);
        return false;
    }

    return true;
}
```

No `User::validates()`:
```php
if ($this->cpf !== null && $this->cpf !== '') {
    Validations::format('cpf', $this, '/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', 'deve estar no formato 000.000.000-00!');
    Validations::uniqueness('cpf', $this);   // CPF único (já existe na lib!)
}
```

> Se o professor pedir **validação real do CPF** (dígitos verificadores), adicione:
> ```php
> public static function cpf($attribute, $obj)
> {
>     $cpf = preg_replace('/\D/', '', (string) $obj->$attribute);
>     if ($cpf === '') {
>         return true;
>     }
>
>     $valid = strlen($cpf) === 11 && !preg_match('/^(\d)\1{10}$/', $cpf);
>     for ($t = 9; $valid && $t < 11; $t++) {
>         $sum = 0;
>         for ($i = 0; $i < $t; $i++) {
>             $sum += (int) $cpf[$i] * (($t + 1) - $i);
>         }
>         $digit = ((10 * $sum) % 11) % 10;
>         $valid = ((int) $cpf[$t] === $digit);
>     }
>
>     if (!$valid) {
>         $obj->addError($attribute, 'é inválido!');
>     }
>
>     return $valid;
> }
> ```
> ⚠️ Os CPFs do `populate.php` (`000.000.000-01`, `111.111.111-02`…) **não são válidos**. Troque-os no seed ou o `db:populate` vai falhar.

### 3.2 Data válida e não futura

```php
public static function pastDate($attribute, $obj)
{
    $value = $obj->$attribute;
    if ($value === null || $value === '') {
        return true;
    }

    $date = \DateTime::createFromFormat('Y-m-d', (string) $value);
    if ($date === false || $date->format('Y-m-d') !== $value) {
        $obj->addError($attribute, 'data inválida!');
        return false;
    }

    if ($date > new \DateTime('today')) {
        $obj->addError($attribute, 'não pode ser uma data futura!');
        return false;
    }

    return true;
}
```
Variação, "idade mínima de 12 anos": `if ($date > new \DateTime('-12 years')) { addError(..., 'idade mínima é 12 anos!') }`.

### 3.3 Tamanho mínimo (ex.: senha com 6+ caracteres)

```php
public static function minLength($attribute, $obj, int $min)
{
    $value = (string) $obj->$attribute;
    if (mb_strlen($value) < $min) {
        $obj->addError($attribute, "deve ter no mínimo {$min} caracteres!");
        return false;
    }

    return true;
}
```

No `User::validates()`, **dentro do `if ($this->newRecord())`**, porque `password` só existe na criação:
```php
if ($this->newRecord()) {
    Validations::minLength('password', $this, 6);
    Validations::passwordConfirmation($this);
}
```
> Funciona porque `password` é uma propriedade `protected` do `User` e o `__get` do `Model` a encontra via `property_exists`.
> Na **edição**, a senha nova não passa pelo model (`changePassword`). Valide no controller: `if ($password !== '' && mb_strlen($password) < 6) { FlashMessage::danger(...); redirect; return; }`.

### 3.4 Valor dentro de uma lista (ex.: categoria)

```php
public static function inclusion($attribute, $obj, array $allowed)
{
    if (!in_array($obj->$attribute, $allowed, true)) {
        $obj->addError($attribute, 'deve ser um destes: ' . implode(', ', $allowed));
        return false;
    }

    return true;
}
```
Uso: `Validations::inclusion('role', $this, [self::ROLE_STUDENT, self::ROLE_MANAGER]);`

> **Novo tipo de usuário (como o da validação passada)?** Além da constante e do `isX()` no model, lembre de **3 lugares do CRUD**: o `<option>` no `<select name="user[role]">` de `new.phtml` e `edit.phtml`, e os dois `in_array($role, [User::ROLE_STUDENT, User::ROLE_MANAGER], true)` em `create()` e `update()`. Senão o controller descarta o papel novo e salva como `student`.

---

## 4. Cenário 3 — Busca, filtro e ordenação na listagem paginada

**Pedido:** "Coloque uma busca por nome ou e-mail e um filtro por status. Ao trocar de página, a busca tem que continuar."

**Por que é difícil:** o `Lib\Paginator` só sabe fazer `WHERE coluna = valor` e os links das páginas só levam `?page=N`, então o filtro se perde na página 2. São 4 arquivos.

### Passo 1 — `lib/Paginator.php`: suportar LIKE e manter parâmetros nos links

1. Três parâmetros novos **no fim** do construtor (os existentes continuam funcionando):
   ```php
           private ?string $route = null,
           private ?string $order = null,
           private array $searchColumns = [],      // NOVO: ex. ['name', 'email']
           private ?string $searchTerm = null,     // NOVO: texto digitado
           private array $queryParams = []         // NOVO: parâmetros mantidos nos links
       ) {
   ```
2. Getter (cole antes de `getRouteName()`):
   ```php
   public function queryParams(): array
   {
       return $this->queryParams;
   }
   ```
3. **Substitua** `buildConditions()` e `bindConditions()` inteiros:
   ```php
   private function buildConditions(): string
   {
       $parts = array_map(function ($column) {
           return "{$column} = :{$column}";
       }, array_keys($this->conditions));

       if ($this->hasSearch()) {
           $likes = [];
           foreach ($this->searchColumns as $index => $column) {
               $likes[] = "{$column} LIKE :search_{$index}";
           }
           $parts[] = '(' . implode(' OR ', $likes) . ')';
       }

       if (empty($parts)) {
           return '';
       }

       return ' WHERE ' . implode(' AND ', $parts);
   }

   private function bindConditions(PDOStatement $stmt): void
   {
       foreach ($this->conditions as $column => $value) {
           $stmt->bindValue($column, $value);
       }

       if ($this->hasSearch()) {
           foreach ($this->searchColumns as $index => $column) {
               $stmt->bindValue("search_{$index}", '%' . $this->searchTerm . '%');
           }
       }
   }

   private function hasSearch(): bool
   {
       return !empty($this->searchColumns) && $this->searchTerm !== null && $this->searchTerm !== '';
   }
   ```
   > O termo vai por **bind** (`%termo%`), nunca concatenado no SQL, o que protege contra SQL Injection. Cada coluna tem seu placeholder (`:search_0`, `:search_1`).

### Passo 2 — `app/views/paginator/_pages.phtml`: levar os filtros nos links

Nas **3** chamadas `route($paginator->getRouteName(), ['page' => ...])`, troque o array por:
```php
route($paginator->getRouteName(), [...$paginator->queryParams(), 'page' => $paginator->previousPage()])
route($paginator->getRouteName(), [...$paginator->queryParams(), 'page' => $page])
route($paginator->getRouteName(), [...$paginator->queryParams(), 'page' => $paginator->nextPage()])
```
O `route()` transforma os extras em query string: `/admin/users?q=ana&status=active&page=2`.

### Passo 3 — `app/Models/User.php`: método de busca

```php
use Lib\Paginator;   // no topo

/**
 * @param array<string, string> $queryParams
 */
public static function search(
    int $page,
    int $perPage,
    ?string $term = null,
    ?string $status = null,
    string $order = 'id ASC',
    array $queryParams = []
): Paginator {
    $conditions = [];
    if ($status !== null && $status !== '') {
        $conditions['status'] = $status;
    }

    return new Paginator(
        class: static::class,
        page: $page,
        per_page: $perPage,
        table: static::$table,
        attributes: static::$columns,
        conditions: $conditions,
        route: 'admin.users.index',
        order: $order,
        searchColumns: ['name', 'email'],
        searchTerm: $term,
        queryParams: $queryParams
    );
}
```

### Passo 4 — `UsersController::index()`: ler filtros e trocar o `User::paginate(...)` (linha 30)

```php
$q = trim((string) $request->getParam('q', ''));

$status = (string) $request->getParam('status', '');
if (!in_array($status, ['', User::STATUS_ACTIVE, User::STATUS_BLOCKED], true)) {
    $status = '';
}

// Ordenação por WHITELIST: nunca concatene o valor recebido direto no ORDER BY
$sortOptions = ['id' => 'id ASC', 'name' => 'name ASC', 'recent' => 'id DESC'];
$sort = (string) $request->getParam('sort', 'id');
if (!array_key_exists($sort, $sortOptions)) {
    $sort = 'id';
}

$queryParams = array_filter(['q' => $q, 'status' => $status, 'sort' => $sort !== 'id' ? $sort : '']);

$perPage = 5;
$paginator = User::search($page, $perPage, $q, $status, $sortOptions[$sort], $queryParams);
```
E acrescente `'q', 'status', 'sort'` no `compact(...)` do `render`.

### Passo 5 — Formulário de busca em `admin/users/index.phtml`

Cole logo acima do card "Gestão de Atletas" (antes da linha 58). É um form **GET**, que vira query string:
```php
<form method="GET" action="/admin/users" class="row g-2 mb-3" id="search_form">
    <div class="col-md-5">
        <input type="text" name="q" class="form-control" placeholder="Buscar por nome ou e-mail"
               value="<?= htmlspecialchars($q) ?>" id="search_q">
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select" id="search_status">
            <option value="">Todos os status</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativos</option>
            <option value="blocked" <?= $status === 'blocked' ? 'selected' : '' ?>>Bloqueados</option>
        </select>
    </div>
    <div class="col-md-2">
        <select name="sort" class="form-select" id="search_sort">
            <option value="id" <?= $sort === 'id' ? 'selected' : '' ?>>Ordem: ID</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Ordem: Nome</option>
            <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Mais recentes</option>
        </select>
    </div>
    <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-dark" id="btn_search"><i class="bi bi-search"></i> Filtrar</button>
    </div>
</form>
```
E troque o `colspan` da linha "Nenhum atleta cadastrado" por uma mensagem melhor: `Nenhum atleta encontrado para o filtro.`

**Para demonstrar:** filtre "Bloqueados" e veja só os 2 bloqueados do seed. Busque "a" e vá para a página 2: a URL continua com `q=a`.

> ⚠️ **Pegadinha:** `UsersCrudCest::paginacaoDosRegistros` clica no link "2". Continua funcionando, porque sem filtro o link é só `?page=2`.

---

## 5. Cenário 4 — Ação customizada: gerar senha aleatória

**Pedido (bem parecido com o código aleatório da validação passada):** "O admin precisa de um botão que gera uma senha nova aleatória para o atleta e mostra para ele anotar."

### Passo 1 — Rota (`config/routes.php`, no grupo `admin`, perto de `block`/`activate`)

```php
Route::post('/admin/users/{id}/reset-password', [AdminUsersController::class, 'resetPassword'])
    ->name('admin.users.reset_password');
```
> É `POST` e não `GET` porque **altera estado**. Um link GET poderia ser disparado por um crawler ou pelo pré-carregamento do navegador.

### Passo 2 — Model (`app/Models/User.php`)

```php
/**
 * Gera uma senha aleatória criptograficamente segura (random_int),
 * sem caracteres ambíguos (0/O, 1/l/I).
 */
public static function generateRandomPassword(int $length = 10): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    return $password;
}
```
> Outras formas válidas de dizer na hora: `bin2hex(random_bytes(5))` (10 caracteres hexadecimais) ou `strtoupper(substr(bin2hex(random_bytes(4)), 0, 6))` (código de 6). **Não use `rand()`/`mt_rand()`/`str_shuffle()`**, que são previsíveis.

### Passo 3 — Controller (`UsersController`, antes de `validateIdParam`)

```php
public function resetPassword(Request $request): void
{
    $id = $this->validateIdParam($request);
    if ($id === null) {
        return;
    }

    $targetUser = User::findById($id);
    if ($targetUser === null) {
        FlashMessage::danger('Usuário não encontrado.');
        $this->redirectTo(route('admin.users.index'));
        return;
    }

    $newPassword = User::generateRandomPassword(10);
    $targetUser->changePassword($newPassword);   // guarda só o HASH (password_hash)
    $targetUser->save();

    FlashMessage::success(
        'Nova senha de ' . htmlspecialchars($targetUser->name) . ': <strong id="generated_password">'
        . $newPassword . '</strong> (anote, ela não será exibida novamente).'
    );
    $this->redirectTo(route('admin.users.show', ['id' => $targetUser->id]));
}
```
> A senha em texto puro **só existe nessa requisição**. O banco guarda o hash BCrypt e, depois que a mensagem flash é exibida, ela some (`FlashMessage::get()` faz `unset`).

### Passo 4 — Botão (`admin/users/show.phtml`, dentro do `<div class="d-grid gap-2">`)

```php
<form action="/admin/users/<?= $targetUser->id ?>/reset-password" method="POST" class="d-grid"
      onsubmit="return confirm('Gerar uma nova senha para este atleta? A senha atual deixará de funcionar.');">
    <button type="submit" class="btn btn-outline-dark btn-sm" id="btn_reset_password">
        <i class="bi bi-key"></i> Gerar Nova Senha
    </button>
</form>
```

**Para demonstrar:** gere a senha, faça logout e entre com o e-mail do atleta e a senha nova. A senha antiga para de funcionar.

**Variação "trocar a senha no primeiro acesso":** use a coluna que já existe, `password_reset_token` (está no `$columns`). Grave `'must_change'` nela ao gerar a senha. No `AuthController::create()`, depois do login, se `$user->password_reset_token === 'must_change'`, redirecione para uma página de troca de senha que, ao salvar, limpa o campo.

---

## 6. Cenário 5 — Regras de negócio na exclusão (e "desativar em vez de excluir")

### 6.1 Bloquear exclusão de atleta com mensalidade em aberto

**Model (`User.php`):**
```php
public function hasOpenInvoices(): bool
{
    if ($this->id === null) {
        return false;
    }

    return Invoice::exists(['user_id' => $this->id, 'status' => Invoice::STATUS_PENDING])
        || Invoice::exists(['user_id' => $this->id, 'status' => Invoice::STATUS_OVERDUE]);
}
```
> `Model::where/exists` só faz `=` com `AND`. Por isso usamos duas consultas unidas por `||` em vez de um `IN (...)`.

**Controller (`destroy()`, antes de `$targetUser->destroy();`, na linha 267):**
```php
if ($targetUser->hasOpenInvoices()) {
    FlashMessage::danger(
        'Não é possível remover ' . htmlspecialchars($targetUser->name) . ': existem mensalidades pendentes ou atrasadas.'
    );
    $this->redirectTo(route('admin.users.index'));
    return;
}
```
**Demonstrar:** tente excluir "Aluno Beach Tennis" (tem fatura `pending`) ou "Aluno Inadimplente" (`overdue`): é barrado. "Carlos Eduardo" (sem faturas) é excluído normalmente.

> ⚠️ `UsersCrudCest::remocaoDoRegistro` exclui o usuário 5 (Carlos Eduardo), que não tem faturas no seed, então o teste continua passando.

**Variação "não remover o último administrador":**
```php
if ($targetUser->isAdmin() && count(User::where(['role' => User::ROLE_MANAGER])) <= 1) {
    FlashMessage::danger('Não é possível remover o único administrador do sistema.');
    $this->redirectTo(route('admin.users.index'));
    return;
}
```

### 6.2 "Desativar" em vez de excluir (exclusão lógica / soft delete)

O model **já tem** `User::STATUS_INACTIVE = 'inactive'`, sem uso. Basta aproveitar:

1. **Model:**
   ```php
   public function isInactive(): bool
   {
       return $this->status === self::STATUS_INACTIVE;
   }

   public function deactivate(): void
   {
       $this->status = self::STATUS_INACTIVE;
   }
   ```
2. **Controller `destroy()`:** troque `$targetUser->destroy();` por:
   ```php
   $targetUser->deactivate();
   $targetUser->save();
   ```
   e a mensagem por `'Usuário ' . $name . ' foi desativado (os dados foram mantidos).'`.
3. **Listagem só com não-inativos:** como o `Paginator` só faz `=`, a forma mais simples é filtrar por status no `index` (com o [§4](#4-cenário-3--busca-filtro-e-ordenação-na-listagem-paginada) pronto, já existe o filtro). Ou acrescente no `<select>` a opção `inactive` ("Desativados").
4. **Login:** em `AuthController::create()` (depois do `isBlocked()`):
   ```php
   if ($user->status === User::STATUS_INACTIVE) {
       FlashMessage::danger('Conta desativada. Procure a secretaria da arena.');
       $this->redirectTo(route('users.login'));
       return;
   }
   ```
5. **Reativar:** a rota `/admin/users/{id}/activate` já existe e volta o status para `active`.
6. **View:** na coluna "Status Catraca" do `index.phtml`, um terceiro caso:
   ```php
   <?php elseif ($u->status === 'inactive') : ?>
       <span class="badge bg-secondary px-2 py-1"><i class="bi bi-archive"></i> DESATIVADO</span>
   ```
   > A view atual usa `if isActive / else BLOQUEADO`. Mude para `if / elseif inactive / else`.
   > ⚠️ Lembre de incluir `User::STATUS_INACTIVE` nos `in_array($status, [...])` do `create()`/`update()` se o select de status do form também ganhar essa opção.

> **Conceito para falar:** exclusão física (`DELETE`) apaga e, com `ON DELETE CASCADE` em `invoices`, **apaga as faturas junto**. A exclusão lógica preserva o histórico financeiro, que é o que uma arena real precisa.

---

## 7. Cenário 6 — CRUD completo de outra entidade: mensalidades do atleta

**Pedido de porte "grande":** "O admin precisa cadastrar, editar, excluir e marcar como paga as mensalidades de cada atleta."
É um **recurso aninhado** (`/admin/users/{user_id}/invoices/...`) que usa a relação **1xN** `User hasMany Invoice` que já existe no model. A listagem já existe no `show.phtml` do atleta.

> Se o pedido for **CRUD de quadras**, a estrutura é a mesma, só que não aninhada: `/admin/courts`, `Court::findById`, campos `name` e `status`.

### Passo 1 — Rotas (`config/routes.php`, grupo `admin`)

```php
use App\Controllers\Admin\InvoicesController as AdminInvoicesController;   // no topo

// Mensalidades do atleta (recurso aninhado, relação 1xN)
Route::get('/admin/users/{user_id}/invoices/new', [AdminInvoicesController::class, 'new'])
    ->name('admin.invoices.new');
Route::post('/admin/users/{user_id}/invoices', [AdminInvoicesController::class, 'create'])
    ->name('admin.invoices.create');
Route::get('/admin/users/{user_id}/invoices/{id}/edit', [AdminInvoicesController::class, 'edit'])
    ->name('admin.invoices.edit');
Route::put('/admin/users/{user_id}/invoices/{id}', [AdminInvoicesController::class, 'update'])
    ->name('admin.invoices.update');
Route::delete('/admin/users/{user_id}/invoices/{id}', [AdminInvoicesController::class, 'destroy'])
    ->name('admin.invoices.destroy');
Route::post('/admin/users/{user_id}/invoices/{id}/pay', [AdminInvoicesController::class, 'pay'])
    ->name('admin.invoices.pay');
```
> Não conflita com `/admin/users/{id}/edit` porque o roteador compara **quantidade de segmentos** e cada trecho fixo (`invoices`, `edit`, `pay`).

### Passo 2 — Model `Invoice` (`app/Models/Invoice.php`): validação de valor e status

No `validates()`:
```php
Validations::notEmpty('user_id', $this);
Validations::notEmpty('amount', $this);
Validations::notEmpty('due_date', $this);

if ($this->amount !== null && $this->amount !== '' && (float) $this->amount <= 0) {
    $this->addError('amount', 'deve ser maior que zero!');
}

if (!in_array($this->status, [self::STATUS_PAID, self::STATUS_PENDING, self::STATUS_OVERDUE], true)) {
    $this->addError('status', 'inválido!');
}
```

### Passo 3 — Controller novo `app/Controllers/Admin/InvoicesController.php`

```php
<?php

namespace App\Controllers\Admin;

use App\Models\Invoice;
use App\Models\User;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class InvoicesController extends Controller
{
    public function new(Request $request): void
    {
        $athlete = $this->findAthlete($request);
        if ($athlete === null) {
            return;
        }

        $title = 'Nova Mensalidade - ' . $athlete->name;
        $invoice = new Invoice(['user_id' => $athlete->id]);
        $this->render('admin/invoices/new', compact('title', 'athlete', 'invoice'));
    }

    public function create(Request $request): void
    {
        $athlete = $this->findAthlete($request);
        if ($athlete === null) {
            return;
        }

        // HasMany::new() já preenche o user_id
        $invoice = $athlete->invoices()->new($this->invoiceParams($request));

        if (!$invoice->save()) {
            FlashMessage::danger('Erro ao cadastrar mensalidade: ' . $this->describeError($invoice));
            $this->redirectTo(route('admin.invoices.new', ['user_id' => $athlete->id]));
            return;
        }

        FlashMessage::success('Mensalidade cadastrada com sucesso!');
        $this->redirectTo(route('admin.users.show', ['id' => $athlete->id]));
    }

    public function edit(Request $request): void
    {
        $athlete = $this->findAthlete($request);
        $invoice = $athlete ? $this->findInvoice($request, $athlete) : null;
        if ($athlete === null || $invoice === null) {
            return;
        }

        $title = 'Editar Mensalidade #' . $invoice->id;
        $this->render('admin/invoices/edit', compact('title', 'athlete', 'invoice'));
    }

    public function update(Request $request): void
    {
        $athlete = $this->findAthlete($request);
        $invoice = $athlete ? $this->findInvoice($request, $athlete) : null;
        if ($athlete === null || $invoice === null) {
            return;
        }

        foreach ($this->invoiceParams($request) as $column => $value) {
            $invoice->$column = $value;
        }

        if (!$invoice->save()) {
            FlashMessage::danger('Erro ao atualizar mensalidade: ' . $this->describeError($invoice));
            $this->redirectTo(route('admin.invoices.edit', ['user_id' => $athlete->id, 'id' => $invoice->id]));
            return;
        }

        FlashMessage::success('Mensalidade #' . $invoice->id . ' atualizada com sucesso!');
        $this->redirectTo(route('admin.users.show', ['id' => $athlete->id]));
    }

    public function destroy(Request $request): void
    {
        $athlete = $this->findAthlete($request);
        $invoice = $athlete ? $this->findInvoice($request, $athlete) : null;
        if ($athlete === null || $invoice === null) {
            return;
        }

        $invoice->destroy();
        FlashMessage::success('Mensalidade removida com sucesso!');
        $this->redirectTo(route('admin.users.show', ['id' => $athlete->id]));
    }

    /**
     * Marca como paga e, se não restar nenhuma em aberto, reativa o atleta bloqueado
     * (é a regra de negócio "reativação instantânea" do README).
     */
    public function pay(Request $request): void
    {
        $athlete = $this->findAthlete($request);
        $invoice = $athlete ? $this->findInvoice($request, $athlete) : null;
        if ($athlete === null || $invoice === null) {
            return;
        }

        $invoice->update(['status' => Invoice::STATUS_PAID]);

        $stillOpen = Invoice::exists(['user_id' => $athlete->id, 'status' => Invoice::STATUS_PENDING])
            || Invoice::exists(['user_id' => $athlete->id, 'status' => Invoice::STATUS_OVERDUE]);

        if (!$stillOpen && $athlete->isBlocked()) {
            $athlete->activate();
            $athlete->save();
            FlashMessage::success('Mensalidade paga! Sem pendências: acesso de ' . htmlspecialchars($athlete->name) . ' reativado.');
        } else {
            FlashMessage::success('Mensalidade #' . $invoice->id . ' marcada como paga.');
        }

        $this->redirectTo(route('admin.users.show', ['id' => $athlete->id]));
    }

    /* ---------------- helpers ---------------- */

    private function findAthlete(Request $request): ?User
    {
        $userId = $request->getParam('user_id');
        $athlete = is_numeric($userId) ? User::findById((int) $userId) : null;

        if ($athlete === null) {
            FlashMessage::danger('Atleta não encontrado.');
            $this->redirectTo(route('admin.users.index'));
            return null;
        }

        return $athlete;
    }

    /**
     * Busca DENTRO da relação: impede editar a fatura de outro atleta trocando o id na URL.
     */
    private function findInvoice(Request $request, User $athlete): ?Invoice
    {
        $id = $request->getParam('id');
        /** @var Invoice|null $invoice */
        $invoice = is_numeric($id) ? $athlete->invoices()->findById((int) $id) : null;

        if ($invoice === null) {
            FlashMessage::danger('Mensalidade não encontrada para este atleta.');
            $this->redirectTo(route('admin.users.show', ['id' => $athlete->id]));
            return null;
        }

        return $invoice;
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceParams(Request $request): array
    {
        $amount = str_replace(',', '.', trim((string) $request->getParam('invoice.amount', '')));
        $dueDate = trim((string) $request->getParam('invoice.due_date', ''));

        return [
            'amount' => $amount !== '' ? $amount : null,
            'due_date' => $dueDate !== '' ? $dueDate : null,
            'status' => (string) $request->getParam('invoice.status', Invoice::STATUS_PENDING),
        ];
    }

    private function describeError(Invoice $invoice): string
    {
        $errors = $invoice->getErrors();
        $field = (string) array_key_first($errors);

        return $field . ' ' . ($errors[$field] ?? 'inválido');
    }
}
```
> `str_replace(',', '.')` aceita "180,50" digitado no padrão brasileiro. A coluna `DECIMAL(10,2)` só aceita ponto.

### Passo 4 — Views novas

**`app/views/admin/invoices/new.phtml`**
```php
<div class="row justify-content-center">
    <div class="col-lg-6">
        <h1 class="h4 fw-bold mb-3">Nova mensalidade — <?= htmlspecialchars($athlete->name) ?></h1>
        <div class="card border-0 shadow-sm p-4">
            <form action="/admin/users/<?= $athlete->id ?>/invoices" method="POST" id="new_invoice_form">
                <?php require __DIR__ . '/_form.phtml'; ?>
                <div class="d-flex justify-content-between mt-3">
                    <a href="/admin/users/<?= $athlete->id ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success" id="btn_submit_invoice">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>
```

**`app/views/admin/invoices/edit.phtml`** (igual, mas com `_method=PUT` e outra action):
```php
<div class="row justify-content-center">
    <div class="col-lg-6">
        <h1 class="h4 fw-bold mb-3">Editar mensalidade #<?= $invoice->id ?> — <?= htmlspecialchars($athlete->name) ?></h1>
        <div class="card border-0 shadow-sm p-4">
            <form action="/admin/users/<?= $athlete->id ?>/invoices/<?= $invoice->id ?>" method="POST" id="edit_invoice_form">
                <input type="hidden" name="_method" value="PUT">
                <?php require __DIR__ . '/_form.phtml'; ?>
                <div class="d-flex justify-content-between mt-3">
                    <a href="/admin/users/<?= $athlete->id ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary" id="btn_update_invoice">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>
```

**`app/views/admin/invoices/_form.phtml`** (campos compartilhados: o mesmo form serve para criar e editar):
```php
<div class="mb-3">
    <label for="invoice_amount" class="form-label fw-semibold">Valor (R$)</label>
    <input type="text" class="form-control" id="invoice_amount" name="invoice[amount]" required
           value="<?= htmlspecialchars((string) ($invoice->amount ?? '')) ?>" placeholder="180,00">
</div>
<div class="mb-3">
    <label for="invoice_due_date" class="form-label fw-semibold">Vencimento</label>
    <input type="date" class="form-control" id="invoice_due_date" name="invoice[due_date]" required
           value="<?= htmlspecialchars((string) ($invoice->due_date ?? '')) ?>">
</div>
<div class="mb-3">
    <label for="invoice_status" class="form-label fw-semibold">Status</label>
    <select class="form-select" id="invoice_status" name="invoice[status]">
        <option value="pending" <?= $invoice->isPending() ? 'selected' : '' ?>>Pendente</option>
        <option value="paid" <?= $invoice->isPaid() ? 'selected' : '' ?>>Paga</option>
        <option value="overdue" <?= $invoice->isOverdue() ? 'selected' : '' ?>>Atrasada</option>
    </select>
</div>
```
> Os `.phtml` não são analisados pelo PHPCS (`phpcs.xml` só olha `.php`), então o `require __DIR__` não gera aviso.

### Passo 5 — Botões na tabela de faturas do atleta (`admin/users/show.phtml`)

1. No cabeçalho do card "Histórico Financeiro" (linha 81), troque o badge por:
   ```php
   <a href="/admin/users/<?= $targetUser->id ?>/invoices/new" class="btn btn-success btn-sm" id="btn_new_invoice">
       <i class="bi bi-plus-circle"></i> Nova Mensalidade
   </a>
   ```
2. Na tabela: um `<th class="text-end">Ações</th>` a mais no `<thead>` (após "Status", linha 99) e, em cada `<tr>`:
   ```php
   <td class="text-end">
       <div class="d-inline-flex gap-1">
           <?php if (!$inv->isPaid()) : ?>
               <form action="/admin/users/<?= $targetUser->id ?>/invoices/<?= $inv->id ?>/pay" method="POST" class="m-0">
                   <button class="btn btn-outline-success btn-sm" id="btn_pay_invoice_<?= $inv->id ?>">Pagar</button>
               </form>
           <?php endif; ?>
           <a href="/admin/users/<?= $targetUser->id ?>/invoices/<?= $inv->id ?>/edit"
              class="btn btn-outline-primary btn-sm" id="btn_edit_invoice_<?= $inv->id ?>">Editar</a>
           <form action="/admin/users/<?= $targetUser->id ?>/invoices/<?= $inv->id ?>" method="POST" class="m-0"
                 onsubmit="return confirm('Remover esta mensalidade?');">
               <input type="hidden" name="_method" value="DELETE">
               <button class="btn btn-outline-danger btn-sm" id="btn_delete_invoice_<?= $inv->id ?>">Excluir</button>
           </form>
       </div>
   </td>
   ```

**Para demonstrar:** entre em `/admin/users/3` (Aluno Inadimplente, bloqueado, 2 faturas atrasadas). Pague as duas: na segunda aparece "acesso reativado" e o atleta consegue logar. Edite com valor `-10` e aparece o erro "amount deve ser maior que zero!".

---

## 8. Cenário 7 — Alterações rápidas na paginação

Tudo em `UsersController::index()` (linha 18):

| Pedido | Alteração |
|---|---|
| "10 por página" | `$perPage = 10;` |
| "Ordenar por nome" | `order: 'name ASC'` no `User::paginate(...)` |
| "Mais recentes primeiro" | `order: 'id DESC'` |
| "O usuário escolhe quantos por página" | `$perPage = (int) $request->getParam('per_page', 5); if (!in_array($perPage, [5, 10, 20], true)) { $perPage = 5; }`. Para manter o valor ao trocar de página, faça o [§4 passo 2](#passo-2--appviewspaginator_pagesphtml-levar-os-filtros-nos-links) |
| "Mostrar só alunos" | `new Paginator(..., conditions: ['role' => User::ROLE_STUDENT], ...)` (o `Model::paginate` não aceita `conditions`, então instancie o `Paginator` direto como no [§4 passo 3](#passo-3--appmodelsuserphp-método-de-busca)) |

> ⚠️ Mudar o `$perPage` ou a ordem quebra `UsersCrudCest::paginacaoDosRegistros` (espera "Eduardo Mendes" na página 2 com 5 por página). Avise o professor ou ajuste o teste.

---

## 9. Cenário 8 — Testar o que foi alterado

### 9.1 Teste de controller (mais rápido de escrever na hora)

Copie o padrão de `tests/Integration/Controllers/AdminControllerTest.php`. O `ControllerTestCase` troca o `redirectTo` por um `echo 'Location: ...'`, então dá para conferir o redirecionamento na resposta e a mensagem em `$_SESSION['flash']`.

```php
public function test_reset_password_generates_new_working_password(): void
{
    $response = $this->post(
        action: 'resetPassword',
        controllerName: 'App\Controllers\Admin\UsersController',
        params: ['id' => $this->student->id]
    );

    $this->assertMatchesRegularExpression('/Location: \/admin\/users\/' . $this->student->id . '/', $response);

    preg_match('/id="generated_password">([A-Za-z0-9]+)</', $_SESSION['flash']['success'] ?? '', $match);
    $this->assertNotEmpty($match[1] ?? null);

    $fresh = User::findById($this->student->id);
    $this->assertTrue($fresh->authenticate($match[1]));        // senha nova funciona
    $this->assertFalse($fresh->authenticate('secret123'));    // senha antiga não
}

public function test_cannot_destroy_user_with_open_invoices(): void
{
    (new \App\Models\Invoice([
        'user_id' => $this->student->id, 'amount' => 100, 'due_date' => '2026-12-10',
        'status' => \App\Models\Invoice::STATUS_PENDING,
    ]))->save();

    $this->post(
        action: 'destroy',
        controllerName: 'App\Controllers\Admin\UsersController',
        params: ['id' => $this->student->id]
    );

    $this->assertNotNull(User::findById($this->student->id));
    $this->assertStringContainsString('mensalidades pendentes', $_SESSION['flash']['danger'] ?? '');
}
```
Teste de model (campo novo / validação) em `tests/Unit/Models/Users/UserTest.php`:
```php
public function test_birth_date_cannot_be_in_the_future(): void
{
    $this->user->birth_date = date('Y-m-d', strtotime('+1 day'));
    $this->assertFalse($this->user->isValid());
    $this->assertEquals('não pode ser uma data futura!', $this->user->errors('birth_date'));
}

public function test_cpf_must_have_valid_format(): void
{
    $this->user->cpf = '12345678900';
    $this->assertFalse($this->user->isValid());
    $this->assertEquals('deve estar no formato 000.000.000-00!', $this->user->errors('cpf'));
}
```
Rodar só um arquivo: `./run test tests/Integration/Controllers/AdminControllerTest.php`

### 9.2 Teste de aceitação (Codeception)

Padrão de `tests/Acceptance/Admin/UsersCrudCest.php` (usa os dados do populate):
```php
public function buscaPorNome(AcceptanceTester $page): void
{
    $this->loginAsAdmin($page);
    $page->amOnPage('/admin/users');
    $page->fillField('q', 'Beatriz');
    $page->click('#btn_search');

    $page->seeInCurrentUrl('q=Beatriz');
    $page->see('Beatriz Lima', '#table_users');
    $page->dontSee('Carlos Eduardo', '#table_users');
}
```
Rodar só um Cest: `./run test:browser tests/Acceptance/Admin/UsersCrudCest.php`

> ⚠️ Antes de rodar a aceitação depois de mexer no schema/seed: `./run db:reset && ./run db:populate`.

---

## 10. Roteiro de execução em sala (qualquer pedido)

1. **Entender e repetir o pedido em voz alta** em termos de camadas: "vou mexer na rota, no controller, no model e na view X".
2. `git checkout -b validacao-crud`
3. **Banco primeiro** (se tiver campo/tabela): `schema.sql` → `./run db:reset && ./run db:populate`.
4. **Model:** `$columns`, PHPDoc, `validates()`, métodos de domínio.
5. **Rota** em `config/routes.php` (dentro do grupo certo: `auth` ou `auth` + `admin`; `new` antes de `{id}`).
6. **Controller:** ler parâmetro → validar → model → `FlashMessage` → `redirectTo` + `return`.
7. **View:** form (`name="user[campo]"`, `_method` para PUT/DELETE), listagem/detalhe com `htmlspecialchars`.
8. **Testar no navegador** o caminho feliz **e** o caminho de erro.
9. `php -l` nos arquivos alterados e, se der tempo, `./run phpcs` e o teste do arquivo mexido.
10. **Explicar** usando o vocabulário: RESTful, PRG (Post/Redirect/Get), method spoofing, ActiveRecord, whitelist, bind de parâmetros, hash de senha.

---

## 11. Pegadinhas e erros comuns (com solução)

| Sintoma | Causa | Solução |
|---|---|---|
| `Property X not found in App\Models\User` | coluna fora do `$columns` | adicionar no array `$columns` |
| `SQLSTATE[42S22]: Unknown column 'X'` | está no `$columns` mas não no banco | `schema.sql` + `./run db:reset` |
| `URI /admin/... not found` (404) | rota não registrada, verbo errado ou faltou `_method` | conferir `Route::put/delete` e o `<input name="_method">` |
| `Route with name X not found` | nome em `route('...')` diferente do `->name('...')` | copiar o nome exato |
| Form envia mas o campo chega vazio | `name` do input diferente do que o controller lê | `name="user[campo]"` ↔ `extractParam($request, 'campo')` |
| `{userId}` não vira parâmetro | o regex da rota só aceita `[a-z_]` | usar `{user_id}` |
| Página em branco / "headers already sent" | faltou `return` depois do `redirectTo` em teste, ou `echo` antes do redirect | sempre `redirectTo(...); return;` |
| Edição troca a senha sem querer | `password` vazio tratado como senha nova | só chame `changePassword` se `$password !== ''` (o código atual já faz isso) |
| `Incorrect date value: ''` | campo de data vazio salvo como `''` | converter `'' → null` no controller |
| Papel novo salvo como `student` | `in_array($role, [...])` do create/update não conhece o papel | incluir a constante nova nos dois `in_array` |
| Filtro some ao trocar de página | links do paginator só levam `page` | [§4 passo 2](#passo-2--appviewspaginator_pagesphtml-levar-os-filtros-nos-links) |
| Aceitação quebrou depois da alteração | testes usam IDs/ordem do seed (`users/4`, `delete_user_5`, "Eduardo" na pág. 2) | `db:reset + db:populate`; não reordenar o seed |
| XSS na mensagem flash | `_flash_message.phtml` imprime sem escapar | `htmlspecialchars()` no dado do usuário |

## 12. Comandos rápidos

| Comando | Para quê |
|---|---|
| `./run up -d` / `./run ps` | subir / ver containers |
| `./run db:reset && ./run db:populate` | recriar banco + seed |
| `./run db:console` | MySQL interativo (`SELECT id, name, status FROM users;`) |
| `./run test tests/Unit` · `./run test tests/Integration` | PHPUnit |
| `./run test caminho/Arquivo.php` | um arquivo de teste só |
| `./run test:browser` · `./run test:browser tests/Acceptance/Admin/UsersCrudCest.php` | aceitação |
| `./run phpcs` / `./run phpcbf` / `./run phpstan` | qualidade |
| `php -l arquivo.php` | checar sintaxe na hora |

**Credenciais do seed:** `admin@arena.com / admin123` · `aluno@arena.com / aluno123` · `bloqueado@arena.com / aluno123`

**IDs do seed (úteis para demonstrar):** 1 Admin · 2 Aluno Beach Tennis (1 paga + 1 pendente) · 3 Aluno Inadimplente (2 atrasadas, bloqueado) · 4 Beatriz · 5 Carlos Eduardo · 6 Daniela (bloqueada, sem faturas) · 7 Eduardo Mendes
