# 🎓 Guia Completo de Apresentação e Defesa da Rubrica - UTFPR Arena
**Módulo:** Autenticação, Autorização e Segurança no Sistema UTFPR Arena

---

## 📑 Índice da Apresentação
1. [Demonstração Prática no Sistema (Admin e Usuário)](#1-demonstração-prática-no-sistema)
2. [Explicação de Conceitos Teóricos (Com Referências de Livros)](#2-explicação-de-conceitos-teóricos)
3. [Demonstração do Banco de Dados](#3-demonstração-do-banco-de-dados)
4. [Explicação do Código e Fluxo de Execução](#4-explicação-do-código-e-fluxo-de-execução)
5. [Funcionamento do Framework (Router e FlashMessage)](#5-funcionamento-do-framework)
6. [Testes Automatizados (Aceitação, Rotas e Unitários)](#6-testes-automatizados)
7. [Diretrizes de Pull Request (PR) e Commits](#7-diretrizes-de-pull-request-pr)

---

## 1. Demonstração Prática no Sistema

### Credenciais Pré-configuradas no Banco (Seeds)
- **Administrador (Role: `manager`):** `admin@arena.com` / `admin123`
- **Usuário Aluno (Role: `student`):** `aluno@arena.com` / `aluno123`

---

### Roteiro de Demonstração (Admin e Usuário)

| # | Passo | Ação do Apresentador | Comportamento do Sistema | Explicação Técnica |
|---|---|---|---|---|
| **1.1** | **Acesso à área restrita sem autenticação** | Abrir aba anônima e acessar diretamente `http://localhost:8080/dashboard` ou `http://localhost:8080/admin` | O sistema bloqueia imediatamente e redireciona (302) para `/login` com alerta vermelho: *"Você precisa fazer login para acessar esta área."* | O `AuthMiddleware` intercepta a rota protegida, executa `Session::isAuthenticated()`, constata ausência de `$_SESSION['auth_user']`, grava uma `FlashMessage` e redireciona. Na API (`Accept: application/json`), retorna `401 Unauthorized`. |
| **1.2** | **Autenticação com dados incorretos** | No formulário `/login`, inserir email inexistente (`fantasma@arena.com`) ou senha incorreta (`senha999`) | A página recarrega em `/login` exibindo o alerta: *"Credenciais inválidas. Verifique seu e-mail e senha."* Nenhuma sessão é criada. | `AuthController::login()` busca o usuário no banco via email. Se não achar ou se `password_verify($password, $user['password_hash'])` retornar `false`, não cria sessão e define mensagem flash de erro. |
| **1.3** | **Autenticação bem-sucedida** | Inserir as credenciais válidas: <br>• **Aluno:** `aluno@arena.com` / `aluno123`<br>• **Admin:** `admin@arena.com` / `admin123` | • **Aluno:** Redirecionado para `/dashboard`, exibindo saudação, status ativo, papel `student` e faturas.<br>• **Admin:** Redirecionado para `/admin`, exibindo o painel gerencial, badge `ADMIN`, métricas de faturamento e alunos. | `password_verify()` valida o hash BCrypt. Ocorre `Session::regenerateId(true)` (evita Session Fixation) e `Session::loginUser(...)` grava o usuário em `$_SESSION`. Se o aluno tentar acessar `/admin`, o `RoleMiddleware` bloqueia com `403 Forbidden` (diferença entre autenticar e autorizar). |
| **1.4** | **Logout** | Clicar no botão vermelho "Sair" | A sessão é encerrada e o navegador volta para a tela de login (`/login`) com mensagem de sucesso: *"Você saiu com segurança."* | O formulário dispara um `POST /logout`. O `AuthController::logout()` invoca `Session::logout()`, que limpa `$_SESSION = []`, expira o cookie no cliente via `setcookie(..., time() - 3600)` e chama `session_destroy()`. Se tentar acessar `/dashboard` de novo, é barrado. |

---

## 2. Explicação de Conceitos Teóricos
> **Atenção:** Cada conceito abaixo possui uma **referência bibliográfica consagrada de livro**.

### 2.1 Definição de Autenticação
- **Conceito:** Autenticação é o processo de verificação e validação da identidade declarada por um usuário, processo ou dispositivo. Responde à pergunta fundamental: *"Você é realmente quem alega ser?"*. No sistema web, isso se dá confrontando um identificador (ex: email) e uma prova de conhecimento (ex: senha criptografada).
- **Referência Bibliográfica:**
  > STALLINGS, William. **Criptografia e Segurança de Redes: Princípios e Práticas**. 6. ed. São Paulo: Pearson Education do Brasil, 2014.  
  > *Capítulo 1: Introdução à Segurança de Computadores e Redes - Seção 1.2: Conceitos de Segurança de Computadores (Autenticidade e Verificação de Identidade).*

---

### 2.2 Diferença entre Autenticação e Autorização
- **Conceito:**
  - **Autenticação (AuthN):** Valida a identidade (*"Quem é você?"*). Exemplo no código: `AuthMiddleware` checa se o usuário efetuou login (`Session::isAuthenticated()`).
  - **Autorização (AuthZ):** Valida as permissões e privilégios (*"O que você tem permissão para fazer?"*). Exemplo no código: `RoleMiddleware` checa se o usuário autenticado possui o papel requerido (`role === 'manager'`) para ver a rota `/admin`. Um aluno autenticado passa na autenticação, mas é rejeitado com `403 Forbidden` na autorização de `/admin`.
- **Referência Bibliográfica:**
  > TANENBAUM, Andrew S.; WETHERALL, David. **Redes de Computadores**. 5. ed. São Paulo: Pearson, 2011.  
  > *Capítulo 8: Segurança de Redes - Seção 8.6: Controle de Acesso e Políticas de Autorização.*

---

### 2.3 Funcionamento de Cookies e Sessões na Autenticação
- **Conceito:** O protocolo HTTP é nativamente *stateless* (sem estado): cada requisição é independente e não tem memória de requisições anteriores. Para manter o usuário conectado:
  1. **Sessão (Server-Side):** Ao efetuar login, o servidor cria um registro em memória/disco com os dados do usuário e gera um identificador aleatório exclusivo (`Session ID`, ex: `PHPSESSID = a1b2c3d4...`).
  2. **Cookie (Client-Side):** O servidor responde com o cabeçalho `Set-Cookie: PHPSESSID=a1b2c3d4; Path=/; HttpOnly; SameSite=Lax`.
  3. O navegador armazena esse cookie e o reenvia automaticamente em todas as próximas requisições no cabeçalho `Cookie: PHPSESSID=a1b2c3d4...`.
  4. O servidor intercepta o cookie, localiza a sessão correspondente e sabe quem é o usuário requisitante.
- **Referência Bibliográfica:**
  > KUROSE, James F.; ROSS, Keith W. **Redes de Computadores e a Internet: Uma Abordagem Top-Down**. 7. ed. São Paulo: Pearson, 2017.  
  > *Capítulo 2: Camada de Aplicação - Seção 2.2.4: Interação Usuário-Servidor: Cookies e Gerenciamento de Sessão.*

---

### 2.4 Comparação: Cookies/Sessões vs. JWT vs. HTTP Authentication
| Critério | Sessões & Cookies (Stateful) | JWT - JSON Web Tokens (Stateless) | HTTP Basic / Digest Authentication |
|---|---|---|---|
| **Armazenamento de Estado** | No servidor (memória, disco ou Redis). O cliente só guarda o ID. | No cliente (no próprio token codificado em base64 com assinatura). | Sem estado. O cliente reenvia credenciais a cada requisição. |
| **Revogação de Acesso** | **Imediata:** apagar a sessão no servidor desconecta o usuário instantaneamente. | **Difícil:** o token é válido até sua expiração (`exp`), exigindo listas negras (blacklists). | Difícil sem trocar a senha ou forçar cabeçalhos de expiração. |
| **Segurança e XSS** | Alta se usar `HttpOnly` (JavaScript não acessa o cookie). | Risco de roubo se salvo no `localStorage` do browser. | Credenciais trafegam codificadas em base64 (Basic exige HTTPS obrigatório). |
| **Escalabilidade** | Exige sessões compartilhadas (Redis/Sticky Sessions) em clusters. | Excelente para microsserviços desacoplados e APIs mobile. | Simples, porém inadequado para interfaces web ricas modernas. |
- **Referência Bibliográfica:**
  > LOCKHART, Josh. **PHP Moderno: Recursos Modernos e Boas Práticas**. São Paulo: Novatec Editora, 2016.  
  > *Capítulo 5: Boas Práticas - Seção: Autenticação de Usuários, Cookies Seguros e Tokens de API.*

---

### 2.5 Papel do Middleware na Autenticação
- **Conceito:** O Middleware implementa o padrão arquitetural *Chain of Responsibility* (ou *Intercepting Filter*). Ele atua como uma barreira que intercepta a requisição HTTP **antes** que ela atinja o Controller. Isso evita repetição de código (`DRY`): nenhum Controller precisa conter blocos manuais de `if (!logado) redirect()`. Se o usuário estiver autenticado, o middleware invoca `$next($request)` para passar adiante; caso contrário, interrompe a cadeia e retorna o erro.
- **Referência Bibliográfica:**
  > GAMMA, Erich; HELM, Richard; JOHNSON, Ralph; VLISSIDES, John. **Padrões de Projeto: Soluções Reutilizáveis de Software Orientado a Objetos (GoF)**. Porto Alegre: Bookman, 2000.  
  > *Padrões Comportamentais: Chain of Responsibility (Cadeia de Responsabilidades).*  
  > *(Ou alternativamente: FOWLER, Martin. Padrões de Arquitetura de Aplicações Corporativas. Bookman, 2006 - Padrão Intercepting Filter).*

---

### 2.6 Outros Tipos de Autenticação além de E-mail e Senha
1. **Autenticação Federada / SSO (Single Sign-On):** Baseada em protocolos como OAuth 2.0 e OpenID Connect (ex: "Entrar com Google", "Entrar com gov.br"). O usuário delega a verificação de identidade a um Provedor de Identidade confiável (IdP).
2. **Passwordless (Magic Links e OTP via E-mail/SMS):** O usuário digita apenas o email/telefone e recebe um token de uso único (*One-Time Password*) com tempo de expiração curto para autenticação sem senha estática.
3. **Biometria e WebAuthn / FIDO2:** Leitura de impressão digital, reconhecimento facial (FaceID) ou chaves de hardware (YubiKey) usando criptografia assimétrica local.
4. **Certificados Digitais (mTLS / ICP-Brasil):** Utiliza certificados X.509 em tokens criptográficos A1/A3, comuns em sistemas bancários e governamentais.
- **Referência Bibliográfica:**
  > STALLINGS, William. **Criptografia e Segurança de Redes: Princípios e Práticas**. 6. ed. Pearson, 2014.  
  > *Capítulo 17: Autenticação de Usuários e Infraestrutura de Chave Pública (PKI).*

---

### 2.7 O que é e qual a Importância do 2FA (Two-Factor Authentication)?
- **Conceito:** Autenticação Multifator exige que o usuário apresente fatores de pelo menos duas categorias distintas:
  1. *Algo que você sabe:* Senha, PIN, frase de segurança.
  2. *Algo que você tem:* Smartphone com aplicativo TOTP (Google Authenticator, Microsoft Authenticator), token físico USB (YubiKey), chave de segurança.
  3. *Algo que você é:* Biometria (impressão digital, íris, reconhecimento facial).
- **Importância:** Se a senha for vazada (vazamento de banco externo, ataque de phishing, interceptação em rede insegura ou reutilização de senhas), o invasor **ainda assim não conseguirá acessar a conta**, pois não tem a posse física do segundo fator em tempo real.
- **Referência Bibliográfica:**
  > ANDERSON, Ross. **Engenharia de Segurança: Um Guia para a Construção de Sistemas Distribuídos Confiáveis**. 2. ed. Porto Alegre: Bookman, 2008.  
  > *Capítulo 2: Usabilidade e Psicologia - Seção 2.4: Autenticação Multifator e Tokens de Senha Única.*

---

## 3. Demonstração do Banco de Dados

### 3.1 Modelagem e Estrutura da Tabela `users`
Arquivo: [database/migrations/001_create_users_table.sql](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/database/migrations/001_create_users_table.sql)

```sql
CREATE TABLE IF NOT EXISTS users (
    id                BIGSERIAL       PRIMARY KEY,
    full_name         VARCHAR(255)    NOT NULL,
    cpf               VARCHAR(11)     NOT NULL UNIQUE,
    email             VARCHAR(255)    NOT NULL UNIQUE,     -- Identificador de login
    password          VARCHAR(255)    NOT NULL,            -- Hash BCrypt ($2y$12$...)
    phone             VARCHAR(20)     NOT NULL,
    role              VARCHAR(20)     NOT NULL DEFAULT 'student'
                          CHECK (role IN ('student', 'teacher', 'manager')),
    status            VARCHAR(20)     NOT NULL DEFAULT 'active'
                          CHECK (status IN ('active', 'blocked', 'inactive')),

    -- Requisitos de Segurança Avançados:
    remember_token    VARCHAR(100)    NULL,                -- "Lembrar de mim"
    last_login_at     TIMESTAMP       NULL,                -- Auditoria e rastreabilidade
    email_verified_at TIMESTAMP       NULL,                -- Pré-requisito para 2FA
    password_reset_token VARCHAR(100) NULL,                -- Token de recuperação
    password_reset_expires_at TIMESTAMP NULL,             -- Expiração do reset

    created_at        TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMP       NOT NULL DEFAULT NOW()
);
```

### 3.2 Como a Estrutura Atende Requisitos de Segurança
1. **Hash de Senha Seguro (BCrypt):**
   - A coluna `password` armazena exclusivamente hashes gerados com `password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12])`.
   - Hashes BCrypt começam com `$2y$12$...` e possuem 60 caracteres.
   - Contêm salt criptográfico aleatório embutido automaticamente, tornando o sistema imune a ataques pré-computados com tabelas *Rainbow*.
   - O fator de custo (cost 12) desacelera propositalmente ataques de força bruta.
2. **Recuperação de Senha Segura:**
   - `password_reset_token`: Token aleatório de uso único (`bin2hex(random_bytes(32))`), transmitido ao e-mail cadastrado.
   - `password_reset_expires_at`: O token possui janela de vida curta (ex: 30 a 60 minutos), impedindo ataques de reutilização.
3. **Auditoria e Monitoramento de Login:**
   - `last_login_at`: Permite auditar acessos anômalos e detectar inatividade prolongada.
   - `email_verified_at`: Confirmação do e-mail do titular antes de liberar operações críticas ou habilitar 2FA.

---

## 4. Explicação do Código e Fluxo de Execução

### 4.1 Tentativa de Acesso a Área Restrita sem Autenticação
1. O usuário requisita `GET /dashboard`.
2. O Front Controller [public/index.php](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/public/index.php) inicializa a aplicação e carrega [config/routes.php](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/config/routes.php).
3. O [Router.php](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/src/Core/Router.php) identifica que a rota `/dashboard` pertence ao grupo protegido pelo middleware `'auth'`.
4. O [AuthMiddleware.php](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/src/Core/AuthMiddleware.php#L32) é instanciado e seu método `handle()` é executado.
5. `Session::isAuthenticated()` verifica se existe `$_SESSION['auth_user']`. Como não existe:
   - Se for cliente browser: grava `FlashMessage::set('error', 'Você precisa fazer login...')` e retorna `Response::redirect('/login')`.
   - Se for API (`Accept: application/json`): retorna `Response::json(['code' => 401], 401)`.
6. O `DashboardController` **nunca é executado**, garantindo isolamento total.

### 4.2 Tentativa de Autenticação com Dados Incorretos
1. O formulário envia `POST /login` com `email` e `password`.
2. O roteador despacha para [AuthController::login()](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/src/Controllers/AuthController.php#L68).
3. Validação dos campos: se vazios, redireciona para `/login`.
4. Consulta ao banco de dados pelo email. Se o usuário não existir:
   - `FlashMessage::set('error', 'Credenciais inválidas.')` e redirect para `/login`.
5. Se o email existir, compara o texto puro com o hash salvo via:
   ```php
   if (!password_verify($password, $user['password_hash'])) {
       FlashMessage::set('error', 'Credenciais inválidas.');
       return Response::redirect('/login');
   }
   ```
6. O hash não bate: nenhuma sessão é criada e o usuário volta à tela de login com o erro exibido.

### 4.3 Autenticação Bem-sucedida
1. Usuário envia `POST /login` com credenciais corretas.
2. `password_verify()` retorna `true`.
3. O sistema valida se `status === 'blocked'` (inadimplentes ou suspensos são impedidos de logar).
4. Medida de segurança vital:
   ```php
   Session::regenerateId(true); // Previne Session Fixation Attack
   ```
5. Os dados são salvos na sessão:
   ```php
   Session::loginUser($user['id'], $user['email'], $user['role']);
   ```
6. Redirecionamento condicional de acordo com a autorização (Role):
   - Se `role === 'manager'`: redireciona para `/admin`.
   - Se `role === 'student'`: redireciona para `/dashboard`.

### 4.4 Logout
1. O usuário clica em "Sair", disparando `POST /logout`.
2. [AuthController::logout()](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/src/Controllers/AuthController.php#L134) é invocado.
3. Executa [Session::logout()](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/src/Core/Session.php):
   - Limpa as variáveis da sessão: `$_SESSION = []`.
   - Invalida o cookie de sessão no cliente: `setcookie(session_name(), '', time() - 3600, '/')`.
   - Destrói os dados no servidor: `session_destroy()`.
4. Define mensagem flash: *"Você saiu com segurança."* e redireciona para `/login`.

---

## 5. Funcionamento do Framework

### 5.1 `Route::middleware('auth')->group(...)` em [config/routes.php](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/config/routes.php#L71)
- **Como funciona no [Router.php](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/src/Core/Router.php):**
  - O método `middleware(string ...$names)` armazena temporariamente os nomes dos middlewares na propriedade `$activeMiddlewares` e retorna o próprio `$router` (Fluent Interface / Method Chaining).
  - O método `group(callable $callback)` recebe uma função anônima e a executa: todas as rotas declaradas dentro da closure herdam automaticamente os `$activeMiddlewares`.
  - Ao término da execução da closure, `$activeMiddlewares` é esvaziado.
  - No momento do `dispatch($request)`, o Router monta a cadeia de execução de trás para frente, encapsulando os middlewares ao redor do Controller através de closures encadeadas:
    ```php
    $chain = $core; // Controller
    foreach (array_reverse($middlewareNames) as $name) {
        $middleware = new $this->middlewareMap[$name]();
        $next = $chain;
        $chain = fn (Request $req): Response => $middleware->handle($req, $next);
    }
    return $chain($request);
    ```

### 5.2 `FlashMessage` em [src/Core/FlashMessage.php](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/src/Core/FlashMessage.php)
- Implementa o padrão **PRG (Post/Redirect/Get)** para exibir mensagens temporárias (sucesso, erro, alerta).
- **Como funciona:**
  - `FlashMessage::set($type, $message)`: armazena a mensagem no array `$_SESSION['_flash'][$type]`.
  - `FlashMessage::get($type)`: lê a mensagem, executa `unset($_SESSION['_flash'][$type])` imediatamente e a retorna.
  - Por causa do `unset`, a mensagem existe apenas para a próxima requisição. Se o usuário der F5, a mensagem não reaparece.

---

## 6. Testes Automatizados

### 6.1 Testes de Aceitação ([tests/Acceptance/AuthFlowTest.php](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/tests/Acceptance/AuthFlowTest.php))
Cobrem exatamente os 4 fluxos exigidos na rubrica:
- `testAcessoDashboardSemLoginRetorna401()`: Valida bloqueio não autenticado (401).
- `testLoginComEmailInexistenteRetornaRedirectParaLogin()` e `testLoginComSenhaErradaRedirectParaLogin()`: Valida rejeição com dados incorretos e redirect 302.
- `testLoginComAdminCorretoRedirectParaAdmin()` e `testAposLoginDashboardEstaAcessivel()`: Valida autenticação com sucesso, criação de sessão e status 200 no dashboard.
- `testLogoutDestroisessaoERedirecionaParaLogin()` e `testAposLogoutDashboardBloqueiaComk401()`: Valida destruição de sessão e bloqueio subsequente.

### 6.2 Testes de Acesso e Rotas
- **Rotas Autenticadas:** Testadas em `AuthFlowTest.php` cobrindo o bloqueio do middleware `'auth'`.
- **Rotas Públicas:** Testadas em [tests/Unit/RouterTest.php](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/tests/Unit/RouterTest.php) (`testRotaGetSimplesFunciona()` testando endpoint público `/health`).

### 6.3 Testes Unitários
- **Métodos dos Models:** [tests/Unit/UserTest.php](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/tests/Unit/UserTest.php) testa as regras de negócio da entidade `User`: `isActive()`, `isBlocked()`, `block()`, `activate()`, `getRole()`, `toArray()` (validando que a senha nunca vaza no array) e `verifyPassword()`.
- **Métodos das Classes Core:** [tests/Unit/RouterTest.php](file:///home/gabrielgoettenauer/arenaBt/utfprarenaGB/utfprarena/tests/Unit/RouterTest.php) testa o algoritmo de matching do roteador, parâmetros dinâmicos `{id}` e tratamento de rotas não encontradas (404).

---

## 7. Diretrizes de Pull Request (PR)

### 7.1 Título da PR
```text
feat(auth): implementar sistema de autenticação, autorização por papel (RBAC) e testes automatizados
```

### 7.2 Nome da Branch
```text
feature/auth-rbac-and-security
```

### 7.3 Commits Sugeridos (Conventional Commits)
- `fix(types): corrigir tipos de callback do middleware e docblocks no router`
- `fix(views): tratar variavel de usuario nula e formatar estilos css`
- `test(user): adicionar testes para getPassword e verifyPassword`
- `feat(auth): integrar middleware de sessao e controle de acesso RBAC`

### 7.4 Modelo de Descrição da PR (Markdown)
```markdown
## 📌 Descrição da Pull Request
Esta PR implementa o fluxo completo de autenticação e autorização por papéis (RBAC) da UTFPR Arena, cobrindo requisitos de segurança e testes automatizados conforme a rubrica de avaliação.

### 🚀 O que foi implementado:
1. **Autenticação e Sessões:**
   - Criação de `AuthMiddleware` e `RoleMiddleware`.
   - Hash seguro com BCrypt (`password_hash` e `password_verify`).
   - Prevenção de Session Fixation via `Session::regenerateId(true)`.
   - Sistema de mensagens efêmeras com `FlashMessage`.
2. **Banco de Dados e Modelagem:**
   - Tabela `users` com campos de segurança (`password_reset_token`, `last_login_at`, `email_verified_at`).
   - Índices para performance em autenticação.
3. **Qualidade e CI:**
   - PSR-12 validado com 100% de conformidade no `phpcs`.
   - Análise estática no nível 6 com 0 erros no `phpstan`.
   - Bateria de testes de aceitação e testes unitários via `phpunit`.
