# Guia Pratico para Validacao em Sala - UTFPR Arena

Este documento e um roteiro pratico e direto para consulta rapida durante a validacao presencial do projeto **UTFPR Arena**. Ele foi elaborado para cobrir exatamente as solicitacoes mais comuns que os professores fazem para testar a compreensao da arquitetura e do fluxo de dados:
- Adicionar um novo campo/atributo ao modelo e ao banco;
- Criar um novo perfil/role de usuario;
- Criar uma nova rota protegida ou publica;
- Criar uma nova pagina (Controller + View);
- Adicionar ou alterar regras de validacao e middlewares;
- Rodar migracoes, seeds e testes.

---

## 1. Visao Geral da Arquitetura e Ciclo de Vida da Requisicao

O projeto segue o padrao **MVC (Model-View-Controller)** com arquitetura orientada a objetos e **ActiveRecord proprietario**:

```
Requisicao HTTP (ex: GET /dashboard)
       │
       ▼
public/index.php (Front Controller / Bootstrap)
       │
       ▼
Router ([config/routes.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/config/routes.php))
       │
       ├─► Executa Middlewares ([app/Middleware/](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/Middleware))
       │     - Authenticate ('auth') -> Checa se o usuario esta logado na sessao
       │     - AuthorizeAdmin ('admin') -> Checa se $user->isAdmin()
       │
       ▼
Controller ([app/Controllers/](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/Controllers))
       │
       ├─► Interage com Models ([app/Models/User.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/Models/User.php))
       │     - ActiveRecord executa queries via PDO ([core/Database/](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/core/Database))
       │
       ▼
View ([app/views/](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/views))
       │
       └─► Renderiza templates PHP/HTML (.phtml) dentro do layout base
```

---

## 2. Cenario 1: Adicionar um Novo Atributo / Campo

Exemplo comum: o professor pede para adicionar um campo como **`matricula`**, **`ranking`**, **`birth_date`** ou **`category`** no usuario ou na quadra.

Suponha que o professor peca para adicionar o campo **`ranking`** (ex: *Iniciante*, *Intermediario*, *Avancado*) no [User.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/Models/User.php).

### Passo 1: Atualizar o Banco de Dados

Existem duas formas:

**Opcao A (Recomendada para manter o projeto reproduzivel):**
Edite o arquivo [database/schema.sql](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/database/schema.sql) na tabela `users`:
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(191) NOT NULL,
    encrypted_password VARCHAR(255) NOT NULL,
    avatar_name VARCHAR(65) NULL,
    phone VARCHAR(20) NULL,
    cpf VARCHAR(14) NULL,
    ranking VARCHAR(50) NULL DEFAULT 'Iniciante', -- NOVO CAMPO AQUI
    role VARCHAR(30) NOT NULL DEFAULT 'student',
    status VARCHAR(30) NOT NULL DEFAULT 'active',
...
```

Depois, aplique a mudanca executando no terminal:
```bash
./run db:reset
./run db:populate
```

**Opcao B (Rapida via terminal sem resetar o banco):**
Execute a alteracao direta no MySQL:
```bash
docker compose exec db sh -c 'MYSQL_PWD=root mysql -u root utfprarena_development -e "ALTER TABLE users ADD COLUMN ranking VARCHAR(50) NULL DEFAULT \"Iniciante\";"'
```

### Passo 2: Atualizar o Model (PONTO MAIS CRITICO)

Abra o arquivo [app/Models/User.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/Models/User.php).

> **ATENCAO - PEGADINHA DO ACTIVERECORD:**
> O modelo possui a lista `protected static array $columns = [...]`.
> Se voce tentar acessar ou salvar um campo que **nao** esteja nessa lista, o metodo magico `__set` do `Model.php` lanca a excecao:
> `Property ranking not found in App\Models\User`.
> Portanto, e **obrigatorio** registrar a coluna no array!

1. Adicione a propriedade no PHPDoc (topo da classe):
```php
/**
 * ...
 * @property ?string $ranking
 */
```

2. Adicione o nome da coluna no array `$columns`:
```php
    protected static array $columns = [
        'name',
        'email',
        'encrypted_password',
        'avatar_name',
        'role',
        'status',
        'phone',
        'cpf',
        'ranking', // <-- ADICIONE AQUI
        'remember_token',
        'last_login_at',
        'email_verified_at',
        'password_reset_token',
        'password_reset_expires_at'
    ];
```

3. (Opcional) Se quiser um valor padrao no construtor de [User.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/Models/User.php):
```php
    public function __construct(array $params = [])
    {
        parent::__construct($params);

        if ($this->ranking === null) {
            $this->ranking = 'Iniciante';
        }
        ...
    }
```

4. (Opcional) Se o campo for obrigatorio, adicione validacao no metodo `validates()`:
```php
    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::notEmpty('email', $this);
        Validations::notEmpty('ranking', $this); // <-- validacao opcional
        Validations::uniqueness('email', $this);
        ...
    }
```

### Passo 3: Atualizar o Seed de Dados

Abra [database/Populate/populate.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/database/Populate/populate.php) e forneca o novo campo para os usuarios:
```php
$aluno = new User([
    'name' => 'Aluno Beach Tennis',
    'email' => 'aluno@arena.com',
    'password' => 'aluno123',
    'password_confirmation' => 'aluno123',
    'role' => User::ROLE_STUDENT,
    'status' => User::STATUS_ACTIVE,
    'phone' => '(41) 98888-0002',
    'cpf' => '111.111.111-02',
    'ranking' => 'Intermediario' // <-- NOVO CAMPO
]);
$aluno->save();
```

Recarregue os dados:
```bash
./run db:populate
```

### Passo 4: Exibir o Novo Campo na View

Abra a view correspondente:
- Para a area do aluno: [app/views/dashboard/index.phtml](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/views/dashboard/index.phtml)
- Para a area administrativa: [app/views/admin/index.phtml](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/views/admin/index.phtml)

Exemplo de exibicao na lista de informacoes do atleta em `dashboard/index.phtml`:
```php
<li class="list-group-item d-flex justify-content-between align-items-center px-0">
    <span>Nivel no Beach Tennis:</span>
    <span class="badge bg-info text-dark"><?= htmlspecialchars($user->ranking ?? 'Iniciante') ?></span>
</li>
```

---

## 3. Cenario 2: Adicionar um Novo Papel (Role) ou Novo Usuario

Se o professor pedir para criar um novo tipo de usuario (ex: `coach` para professor/treinador):

### 1. Definir a constante e metodos no Model [User.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/Models/User.php)

```php
    public const ROLE_STUDENT = 'student';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_COACH = 'coach'; // <-- NOVO PAPEL

    public function isCoach(): bool
    {
        return $this->role === self::ROLE_COACH;
    }
```

### 2. Cadastrar o usuario no seed [populate.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/database/Populate/populate.php)

```php
$coach = new User([
    'name' => 'Professor Carlos Silva',
    'email' => 'coach@arena.com',
    'password' => 'coach123',
    'password_confirmation' => 'coach123',
    'role' => User::ROLE_COACH,
    'status' => User::STATUS_ACTIVE,
    'phone' => '(41) 97777-5555',
    'cpf' => '333.333.333-04'
]);
$coach->save();
```
Execute `./run db:populate` para inserir no banco.

---

## 4. Cenario 3: Criar uma Nova Rota e uma Nova Pagina

Exemplo: o professor pede para criar a pagina `/quadras` ou `/perfil` ou `/ajuda`.

### Passo 1: Registrar a Rota em [config/routes.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/config/routes.php)

Se for uma rota **publica**:
```php
Route::get('/ajuda', [HomeController::class, 'ajuda'])->name('ajuda');
```

Se for uma rota **protegida por login**:
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/perfil', [DashboardController::class, 'perfil'])->name('perfil'); // <-- NOVA ROTA
});
```

Se for uma rota **exclusiva de administrador**:
```php
Route::middleware('auth')->group(function () {
    Route::middleware('admin')->group(function () {
        Route::get('/admin/relatorios', [AdminController::class, 'relatorios'])->name('admin.relatorios');
    });
});
```

### Passo 2: Implementar a Action no Controller

Abra o controller desejado (ex: [app/Controllers/DashboardController.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/Controllers/DashboardController.php)):

```php
    public function perfil(Request $request): void
    {
        $user = $this->currentUser();
        $title = 'Meu Perfil - UTFPR Arena';

        $this->render('dashboard/perfil', compact('title', 'user'));
    }
```

> **DICA IMPORTANTE DO CONTROLLER:**
> - `$this->currentUser()`: retorna a instancia do `User` logado no momento.
> - `$this->render('nome_da_pasta/arquivo', compact(...))`: renderiza a view localizada em `app/views/nome_da_pasta/arquivo.phtml`.
> - `$this->redirectTo(route('nome.da.rota'))`: redireciona o usuario.

### Passo 3: Criar o Arquivo de View (.phtml)

Crie o arquivo em `app/views/dashboard/perfil.phtml`:
```php
<div class="card border-0 shadow-sm p-4">
    <h2 class="h4 fw-bold mb-3"><?= htmlspecialchars($title) ?></h2>
    <p>Nome: <strong><?= htmlspecialchars($user->name) ?></strong></p>
    <p>E-mail: <strong><?= htmlspecialchars($user->email) ?></strong></p>
    <p>Telefone: <strong><?= htmlspecialchars($user->phone ?? 'Nao informado') ?></strong></p>
    <a href="/dashboard" class="btn btn-secondary btn-sm mt-3">Voltar ao Dashboard</a>
</div>
```

---

## 5. Cenario 4: Criar ou Modificar Middlewares de Acesso

Middlewares interceptam a requisicao antes de chegar no Controller.
Os middlewares existentes estao em [app/Middleware/](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/Middleware):
- [Authenticate.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/Middleware/Authenticate.php): verifica se o usuario esta autenticado. Se nao estiver, redireciona para `/login` (ou retorna 401 se JSON).
- [AuthorizeAdmin.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/Middleware/AuthorizeAdmin.php): verifica se o usuario e admin (`$user->isAdmin()`). Se nao for, redireciona para `/dashboard` (ou retorna 403 se JSON).

### Como criar um novo middleware (ex: AuthorizeActive):
Para impedir que alunos inadimplentes/bloqueados acessem certas rotas:

1. Crie o arquivo `app/Middleware/AuthorizeActive.php`:
```php
<?php

namespace App\Middleware;

use Core\Http\Middleware\Middleware;
use Core\Http\Request;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class AuthorizeActive implements Middleware
{
    public function handle(Request $request): void
    {
        $user = Auth::user();
        if ($user !== null && $user->isBlocked()) {
            FlashMessage::danger('Seu acesso esta bloqueado por inadimplencia.');
            header('Location: /dashboard');
            exit;
        }
    }
}
```

2. Registre o alias em [config/App.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/config/App.php):
```php
    public static array $middlewareAliases = [
        'auth' => \App\Middleware\Authenticate::class,
        'admin' => \App\Middleware\AuthorizeAdmin::class,
        'active' => \App\Middleware\AuthorizeActive::class, // <-- NOVO ALIAS
    ];
```

3. Utilize no [config/routes.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/config/routes.php):
```php
Route::middleware('auth')->group(function () {
    Route::middleware('active')->group(function () {
        Route::get('/reservar-quadra', [CourtController::class, 'create'])->name('courts.book');
    });
});
```

---

## 6. Cenario 5: Adicionar Regras de Validacao no Model

A biblioteca [Lib/Validations.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/lib/Validations.php) oferece metodos prontos:
- `Validations::notEmpty('campo', $this);`
- `Validations::uniqueness('campo', $this);`
- `Validations::passwordConfirmation($this);`

No metodo `validates()` de qualquer modelo que herda de `Model`:
```php
    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::notEmpty('email', $this);
        Validations::uniqueness('email', $this);
        
        // Exemplo: validar que o telefone nao pode estar vazio
        Validations::notEmpty('phone', $this);
    }
```
Se a validacao falhar, `$model->isValid()` retorna `false` e `$model->errors()` contera as mensagens de erro.

---

## 7. Comandos de Terminal Essenciais (Guia Rapido)

Execute estes comandos a partir da raiz do projeto (`project/utfprarena`):

| Comando | O que faz |
| :--- | :--- |
| `./run up -d` | Sobe todos os containers Docker em segundo plano |
| `./run ps` | Lista os containers e status de portas |
| `./run db:reset` | Recria todas as tabelas a partir de `database/schema.sql` |
| `./run db:populate` | Executa o seed populando usuarios padrao, quadras e faturas |
| `./run db:console` | Abre sessao interativa direta no MySQL |
| `./run test` | Executa os testes unitarios e de integracao via PHPUnit |
| `./run test:browser` | Executa os testes de aceitacao (Codeception / Selenium) |
| `./run phpcs` | Valida padrao de codigo (PSR-12 / PHP_CodeSniffer) |
| `./run phpcbf` | Corrige formatacao de codigo automaticamente |
| `./run phpstan` | Executa analise estatica de tipos |

### Credenciais Padrao Cadastradas no Seed:
- **Gestor/Admin:** `admin@arena.com` / Senha: `admin123`
- **Aluno Ativo:** `aluno@arena.com` / Senha: `aluno123`
- **Aluno Bloqueado:** `bloqueado@arena.com` / Senha: `aluno123`

---

## 8. Principais Pegadinhas e Como Resolver Rapido

1. **Erro: `Property <nome> not found in App\Models\User`**
   - **Causa:** Voce adicionou a coluna no banco, mas esqueceu de adiciona-la no array `protected static array $columns = [...]` no topo da classe [app/Models/User.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/app/Models/User.php).
   - **Solucao:** Adicione a string com o nome da coluna dentro do array `$columns`.

2. **Erro: `Route not defined` ou 404 Not Found**
   - **Causa:** Rota nao foi registrada em [config/routes.php](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/config/routes.php) ou o nome chamado em `route('nome')` esta com digite errado.
   - **Solucao:** Confira o verbo HTTP (`Route::get` vs `Route::post`) e a URL exata em `config/routes.php`.

3. **Como acessar o usuario logado no Controller:**
   - Basta chamar `$this->currentUser()`. Retorna `?User`.

4. **Como enviar mensagens de alerta para a tela:**
   - Sucesso: `\Lib\FlashMessage::success('Operacao realizada com sucesso!');`
   - Erro: `\Lib\FlashMessage::danger('Ocorreu um erro.');`
   - O layout base ja renderiza essas mensagens automaticamente via Bootstrap alerts.

5. **Apos alterar `schema.sql`, o banco nao mudou:**
   - O arquivo `schema.sql` nao roda sozinho. Voce deve rodar `./run db:reset` seguido de `./run db:populate`.
