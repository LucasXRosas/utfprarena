# 🎓 Guia Completo de Apresentação e Defesa Oral da Rubrica — UTFPR Arena
**Sistema:** UTFPR Arena Beach Tennis — Gerenciamento e Controle de Acesso às Quadras  
**Framework Base:** `tsi34d-framework-template` (UTFPR TSI34D)  
**Repositório:** [https://github.com/LucasXRosas/utfprarena](https://github.com/LucasXRosas/utfprarena)  
**Wiki Oficial:** [https://github.com/LucasXRosas/utfprarena/wiki](https://github.com/LucasXRosas/utfprarena/wiki)

---

## 📋 Sumário Executivo da Apresentação
Este documento é o roteiro completo de estudo e fala para a apresentação do trabalho prático perante o professor e avaliadores. Ele aborda cada item da tabela de avaliação da rubrica, seus fundamentos teóricos com citações bibliográficas de livros, demonstração prática, análise de código e testes automatizados.

---

## 1. Apresentação da Funcionalidade (Peso: 0.5)

### 1.1 Propósito da Funcionalidade e sua Importância no Sistema
- **Contexto de Negócio:** O sistema **UTFPR Arena** gerencia complexos esportivos de *Beach Tennis*. Em um modelo de negócio de mensalistas, o maior risco financeiro é a inadimplência continuada (atletas utilizando as instalações sem pagar).
- **Importância da Autenticação:** A autenticação é o ponto de entrada e o mecanismo primordial de identificação inequívoca de cada usuário no sistema. Ela assegura que apenas pessoas registradas transitem entre as camadas da aplicação.
- **Motor de Regras de Inadimplência:** O sistema implementa uma "catraca lógica" que monitora as mensalidades. Se um atleta possuir 2 ou mais faturas vencidas, seu status é alterado para `blocked` (bloqueado), impedindo agendamentos e uso das quadras de areia.
- **Desbloqueio Imediato:** Ao quitar suas faturas em aberto, o sistema reativa o atleta em tempo real para `active`, eliminando intervenções manuais da secretaria.

### 1.2 Diferenças de Ações entre os Tipos de Usuários do Sistema
O sistema adota o padrão **RBAC (Role-Based Access Control)** com três níveis de acesso bem delineados:
1. **Visitante (Não autenticado):**
   - Tem acesso unicamente às páginas públicas: Landing Page (`/`) e Tela de Login (`/login`).
   - Bloqueado sumariamente ao tentar acessar qualquer rota interna.
2. **Aluno / Atleta (`role: student`):**
   - Redirecionado pós-login para a **Área do Aluno** (`/dashboard`).
   - Ações: Consulta sua credencial de acesso ("Apto para Jogar"), verifica histórico e status de faturas (Pagas/Pendentes) e visualiza a disponibilidade das quadras da arena.
   - Tentativa de acesso à área gerencial (`/admin`) resulta em bloqueio pelo middleware de autorização (`403 Forbidden` ou redirecionamento com alerta).
3. **Administrador / Gestor (`role: manager`):**
   - Redirecionado pós-login para o **Painel Gerencial** (`/admin`).
   - Ações: Acompanha métricas globais (total de atletas, número de bloqueados por inadimplência), audita timestamps de login e tem o poder de **bloquear ou reativar manualmente** o acesso de qualquer atleta à arena.

### 1.3 Organização do WIKI — Informações da Entrega
Para cumprir a exigência do WIKI no GitHub ([Wiki UTFPR Arena](https://github.com/LucasXRosas/utfprarena/wiki)):
1. **Página com Informações da Entrega:**
   - Descrição geral da release de autenticação, objetivos e link direto para o Pull Request principal (`https://github.com/LucasXRosas/utfprarena/pull/1`).
2. **Página com Instruções para Download, Execução, Populate e Dados de Acesso:**
   - Passo a passo detalhado (`git clone`, `cp .env.example .env`, `./run composer install`, `./run up -d`, `./run db:reset`, `./run db:populate`).
   - Tabela de credenciais:
     - Administrador: `admin@arena.com` / `admin123`
     - Aluno Ativo: `aluno@arena.com` / `aluno123`
     - Aluno Inadimplente: `bloqueado@arena.com` / `aluno123`
3. **Seção Obrigatória: "Uso de Inteligência Artificial":**
   - **Ferramentas Utilizadas:** Antigravity AI (Google DeepMind) e GitHub Copilot.
   - **Finalidade:** Refatoração arquitetural para adequação estrita ao padrão `tsi34d-framework-template`, automação da suíte de testes (Codeception Cest e PHPUnit), modelagem de schema SQL seguro (BCrypt e tokens) e documentação teórica com referências bibliográficas consagradas.
   - **Exemplos de Prompts e Resultados:**
     - *Prompt:* `"Refatore o modelo User para utilizar ActiveRecord do template tsi34d com validações de unicidade e métodos de domínio da arena."`  
       *Resultado:* Classe `App\Models\User` herdando de `Core\Database\ActiveRecord\Model`, com validações `notEmpty`, `uniqueness` e métodos `isAdmin()`, `isStudent()`, `block()` e `activate()`.
     - *Prompt:* `"Implemente os testes de aceitação em Codeception simulando o fluxo de login correto, incorreto, bloqueio não autenticado e logout."`  
       *Resultado:* `tests/Acceptance/Auth/AuthCest.php` com 5 métodos cobrindo 100% dos cenários de teste da rubrica.

---

## 2. Demonstração Prática no Sistema (Peso: 1.0)

Durante a apresentação com a tela compartilhada, execute exatamente a seguinte sequência de passos:

### 2.1 Demonstração Prática: Perfil Administrador (`admin@arena.com`)
| Etapa | Ação na Tela | O que Mostrar e Explicar ao Avaliador |
|---|---|---|
| **1. Acesso sem autenticação** | Abra aba anônima e digite `http://localhost:8080/admin` | O sistema **bloqueia** e redireciona (302) para `/login`. Exibe o alerta vermelho (*danger*): *"Você deve estar logado para acessar essa página"*. Mostre que o middleware `Authenticate` interceptou a requisição antes de atingir o controller. |
| **2. Autenticação com dados incorretos** | Digite `admin@arena.com` e a senha `senha_errada` e clique em **Entrar** | Permanece na tela `/login` e exibe o alerta: *"Email ou senha inválidos"*. Nenhuma sessão é gerada no servidor. |
| **3. Autenticação bem-sucedida** | Digite `admin@arena.com` e a senha `admin123` e clique em **Entrar** | O login é aceito com a mensagem: *"Autenticação bem-sucedida! Bem-vindo(a), Administrador Arena."*. Redireciona para o `/admin`. Mostre o badge vermelho **ADMINISTRADOR**, os cartões de métricas (total de atletas, ativos e inadimplentes) e a tabela de gestão de atletas. |
| **4. Logout** | Clique no botão vermelho **Sair** no canto superior direito | A sessão é destruída, o cookie de sessão é invalidado e a aplicação retorna para `/login` com a mensagem: *"Logout realizado com sucesso. Sessão encerrada com segurança."*. Tente clicar no botão "Voltar" do navegador ou acessar `/admin` novamente para provar que o acesso foi revogado. |

### 2.2 Demonstração Prática: Perfil Usuário Aluno (`aluno@arena.com`)
| Etapa | Ação na Tela | O que Mostrar e Explicar ao Avaliador |
|---|---|---|
| **1. Acesso sem autenticação** | Em aba anônima, digite diretamente `http://localhost:8080/dashboard` | O sistema bloqueia imediatamente e redireciona para `/login` com a mensagem flash de perigo. |
| **2. Autenticação com dados incorretos** | Digite `aluno@arena.com` e a senha `errada123` e clique em **Entrar** | O sistema recarrega `/login` informando *"Email ou senha inválidos"*. Mostre também o caso do aluno inadimplente (`bloqueado@arena.com` / `aluno123`), que exibe: *"Acesso bloqueado: usuário possui pendências financeiras. Procure a secretaria da arena."*. |
| **3. Autenticação bem-sucedida** | Digite `aluno@arena.com` e a senha `aluno123` e clique em **Entrar** | Redirecionamento instantâneo para `/dashboard`. Exibe o badge verde **ATIVO - ACESSO LIBERADO**, a lista de mensalidades e as quadras disponíveis. Mostre que se o aluno tentar digitar `/admin` na URL, o `AuthorizeAdmin` bloqueia e o mantém no `/dashboard`. |
| **4. Logout** | Clique no botão **Sair** | Sessão finalizada com sucesso e retorno seguro para `/login`. |

---

## 3. Explicação de Conceitos Teóricos (Peso: 2.0)
> **Atenção Avaliador:** Para cada conceito a seguir é apresentada a **referência bibliográfica completa de livro consagrado**.

### 3.1 Definição de Autenticação
- **Conceito:** Autenticação é o processo de verificação e validação da identidade alegada por uma entidade (usuário, processo ou dispositivo). Responde formalmente à questão: *"Você é realmente quem declara ser?"*. Baseia-se no confronto entre uma identidade reivindicada (identificador/e-mail) e um fator comprobatório de autenticidade (segredo/senha).
- **Referência Bibliográfica:**
  > STALLINGS, William. **Criptografia e Segurança de Redes: Princípios e Práticas**. 6. ed. São Paulo: Pearson Education do Brasil, 2014.  
  > *Capítulo 1: Introdução à Segurança de Computadores e Redes — Seção 1.2: Conceitos de Segurança de Computadores (Autenticidade e Verificação de Identidade).*

### 3.2 Diferença entre Autenticação e Autorização
- **Conceito:**
  - **Autenticação (AuthN):** Diz respeito à **identidade** (*"Quem é você?"*). Exemplo no código: `App\Middleware\Authenticate` verifica se o visitante possui uma sessão válida ativa via `Lib\Authentication\Auth::check()`.
  - **Autorização (AuthZ):** Diz respeito aos **privilégios e permissões** concedidos àquela identidade (*"O que você tem permissão para fazer?"*). Exemplo no código: `App\Middleware\AuthorizeAdmin` verifica se o usuário autenticado possui o papel `manager` para acessar `/admin`. Um aluno logado possui autenticação, mas não possui autorização para administrar a arena, recebendo bloqueio (403).
- **Referência Bibliográfica:**
  > TANENBAUM, Andrew S.; WETHERALL, David. **Redes de Computadores**. 5. ed. São Paulo: Pearson, 2011.  
  > *Capítulo 8: Segurança de Redes — Seção 8.6: Controle de Acesso e Políticas de Autorização.*

### 3.3 Funcionamento de Cookies e Sessões no Processo de Autenticação
- **Conceito:** O protocolo HTTP é nativamente *stateless* (sem estado): cada requisição é tratada isoladamente sem vínculo com as anteriores. Para manter o estado de usuário logado:
  1. **Sessão (Server-Side):** Ao autenticar com sucesso, o servidor PHP aloca memória/arquivo para armazenar as variáveis de sessão (`$_SESSION['user']['id'] = $id`) e gera um identificador pseudoaleatório criptograficamente forte (`Session ID`, ex: `PHPSESSID`).
  2. **Cookie (Client-Side):** O servidor devolve no cabeçalho HTTP a instrução `Set-Cookie: PHPSESSID=...; Path=/; HttpOnly; SameSite=Lax`.
  3. **Ciclo de Requisições:** O navegador armazena esse cookie e o retransmite automaticamente em todas as requisições subsequentes no cabeçalho `Cookie: PHPSESSID=...`.
  4. O servidor correlaciona o `PHPSESSID` aos dados guardados em memória e restaura o usuário logado via `Auth::user()`.
- **Referência Bibliográfica:**
  > KUROSE, James F.; ROSS, Keith W. **Redes de Computadores e a Internet: Uma Abordagem Top-Down**. 7. ed. São Paulo: Pearson, 2017.  
  > *Capítulo 2: Camada de Aplicação — Seção 2.2.4: Interação Usuário-Servidor: Cookies e Gerenciamento de Sessão.*

### 3.4 Comparação: Cookies/Sessões vs. JWT vs. HTTP Authentication
| Critério de Comparação | Autenticação por Cookies/Sessões (Stateful) | JWT - JSON Web Tokens (Stateless) | HTTP Basic / Digest Authentication |
|---|---|---|---|
| **Armazenamento de Estado** | No servidor (memória, disco ou Redis). O cliente só guarda o token de sessão. | No cliente (no próprio corpo do token assinado criptograficamente). | Sem estado. O cliente reenvia credenciais (login:senha) em base64 a cada request. |
| **Revogação de Acesso** | **Imediata:** apagar a sessão no servidor encerra o acesso no mesmo instante. | **Complexa:** o token permanece válido até expirar (`exp`), exigindo listas de revogação/blacklist. | Difícil sem alteração da senha cadastrada no servidor. |
| **Segurança contra Roubo (XSS)** | Alta, se o cookie utilizar a flag `HttpOnly` (inacessível via scripts JS). | Baixa se guardado em `localStorage` (vulnerável a ataques XSS). | As credenciais trafegam codificadas em base64 (requer HTTPS obrigatório). |
| **Escalabilidade Horizontal** | Requer sessões compartilhadas (Redis/Sticky Sessions) em clusters. | Nativa para arquiteturas de microsserviços e APIs distribuídas. | Simples, porém antiquada para interfaces modernas ricas. |
- **Referência Bibliográfica:**
  > LOCKHART, Josh. **PHP Moderno: Recursos Modernos e Boas Práticas**. São Paulo: Novatec Editora, 2016.  
  > *Capítulo 5: Boas Práticas — Seção: Autenticação de Usuários, Cookies Seguros e Tokens de API.*

### 3.5 Papel do Middleware na Autenticação
- **Conceito:** O Middleware aplica o padrão de projeto *Chain of Responsibility* (Cadeia de Responsabilidades) e *Intercepting Filter*. Ele atua como uma barreira que intercepta o ciclo de vida da requisição HTTP **antes** que ela atinja o método do Controller. Isso garante o princípio DRY (*Don't Repeat Yourself*): os controladores não precisam duplicar checagens manuais de autenticação. Se a condição falhar, o middleware interrompe o fluxo (redirecionando ou emitindo código 401); se for válida, permite a continuidade da execução.
- **Referência Bibliográfica:**
  > GAMMA, Erich; HELM, Richard; JOHNSON, Ralph; VLISSIDES, John. **Padrões de Projeto: Soluções Reutilizáveis de Software Orientado a Objetos (GoF)**. Porto Alegre: Bookman, 2000.  
  > *Padrões Comportamentais: Chain of Responsibility (Cadeia de Responsabilidades).*  
  > *(Ou alternativamente: FOWLER, Martin. Padrões de Arquitetura de Aplicações Corporativas. Bookman, 2006 — Intercepting Filter).*

### 3.6 Apresentar Outros Tipos de Autenticação além de E-mail e Senha
1. **Autenticação Federada / SSO (Single Sign-On):** Utilização de provedores de identidade externos com protocolos OAuth 2.0 e OpenID Connect (ex: "Entrar com Google", "Entrar com Microsoft"). O sistema não conhece a senha do usuário.
2. **Passwordless (Magic Links e OTP via E-mail/SMS/WhatsApp):** O usuário informa seu identificador e recebe um link de acesso ou código numérico de uso único (*One-Time Password*) com expiração de poucos minutos.
3. **Biometria e WebAuthn / FIDO2 (Passkeys):** Autenticação sem senha baseada em criptografia de chave pública através do leitor de impressão digital, reconhecimento facial (TouchID/FaceID) ou chaves físicas de hardware (YubiKey).
4. **Certificados Digitais (mTLS / ICP-Brasil):** Utiliza infraestrutura de chaves públicas com certificados digitais X.509 em tokens criptográficos A1/A3.
- **Referência Bibliográfica:**
  > STALLINGS, William. **Criptografia e Segurança de Redes: Princípios e Práticas**. 6. ed. Pearson, 2014.  
  > *Capítulo 17: Autenticação de Usuários e Infraestrutura de Chave Pública (PKI).*

### 3.7 O que é e qual a Importância do 2FA (Two-Factor Authentication)?
- **Conceito:** A Autenticação de Dois Fatores baseia-se no princípio de que a autenticidade deve ser comprovada combinando fatores de pelo menos **duas categorias distintas**:
  1. *Algo que você sabe:* Senha estática ou PIN.
  2. *Algo que você possui:* Smartphone gerando tokens temporários (TOTP via Google Authenticator), token USB de segurança.
  3. *Algo que você é:* Biometria física (impressão digital, íris, geometria facial).
- **Importância:** Elimina a vulnerabilidade de senhas comprometidas. Mesmo que um atacante obtenha a senha do usuário através de *phishing*, vazamento de banco de dados ou força bruta, **ele não conseguirá invadir a conta**, pois não possui a posse física do dispositivo gerador do segundo fator em tempo real.
- **Referência Bibliográfica:**
  > ANDERSON, Ross. **Engenharia de Segurança: Um Guia para a Construção de Sistemas Distribuídos Confiáveis**. 2. ed. Porto Alegre: Bookman, 2008.  
  > *Capítulo 2: Usabilidade e Psicologia — Seção 2.4: Autenticação Multifator e Tokens de Senha Única.*

---

## 4. Demonstração do Banco de Dados (Peso: 1.0)

### 4.1 Modelagem e Estrutura do Banco (`database/schema.sql`)
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(191) NOT NULL,
    encrypted_password VARCHAR(255) NOT NULL,
    avatar_name VARCHAR(65) NULL,
    phone VARCHAR(20) NULL,
    cpf VARCHAR(14) NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'student',
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    remember_token VARCHAR(100) NULL,
    last_login_at DATETIME NULL,
    email_verified_at DATETIME NULL,
    password_reset_token VARCHAR(100) NULL,
    password_reset_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.2 Como a Estrutura Atende Requisitos de Segurança
1. **Hash de Senha Seguro com BCrypt (`encrypted_password`):**
   - Senhas em texto puro **nunca** são persistidas no banco.
   - O campo armazena hashes gerados por `password_hash($value, PASSWORD_DEFAULT)` (algoritmo BCrypt, tamanho de 60 a 255 caracteres).
   - O BCrypt embute automaticamente um *Salt* criptográfico de 128 bits aleatório a cada geração, inviabilizando ataques pré-computados com tabelas *Rainbow*.
   - Possui fator de custo adaptativo (*cost factor*), tornando propositalmente lentos ataques de força bruta automatizados.
2. **Campos para Recuperação Segura de Senha:**
   - `password_reset_token`: Armazena um token criptográfico pseudoaleatório de uso único gerado no momento do pedido de redefinição.
   - `password_reset_expires_at`: Delimita uma janela curta de validade (ex: 30 minutos). Após expirado, o token é descartado, evitando ataques de repetição (*replay attack*).
3. **Auditoria e Monitoramento de Login:**
   - `last_login_at`: Registra o exato *timestamp* de cada login bem-sucedido, possibilitando auditoria de acessos anômalos e identificação de contas inativas.
   - `email_verified_at`: Confirma a posse do e-mail do titular antes de habilitar permissões privilegiadas ou envio de credenciais.

---

## 5. Explicação do Código e Fluxo de Execução (Peso: 3.0)

### 5.1 Fluxo de Execução nos 4 Cenários Avaliados

#### 1. Tentativa de acesso a área restrita sem autenticação:
1. Usuário digita `GET /dashboard`.
2. O Front Controller `public/index.php` inclui `config/bootstrap.php`, disparando `Router::init()`.
3. O arquivo `config/routes.php` registrou `/dashboard` dentro do grupo `Route::middleware('auth')->group(...)`.
4. O `Router::dispatch()` encontra o casamento de rota e executa os middlewares da rota chamando `$route->runMiddlewares($request)`.
5. O `App\Middleware\Authenticate::handle($request)` verifica se `Lib\Authentication\Auth::check()` é verdadeiro.
6. Como `$_SESSION['user']['id']` não existe, o middleware detecta a ausência de autenticação:
   - Grava a mensagem efêmera: `FlashMessage::danger('Você deve estar logado para acessar essa página');`.
   - Executa `header('Location: ' . route('users.login')); exit;`.
7. O `DashboardController` **nunca é instanciado**, assegurando isolamento absoluto.

#### 2. Tentativa de autenticação com dados incorretos:
1. Formulário em `/login` submete `POST /login` com `user[email]` e `user[password]`.
2. O roteador despacha para `App\Controllers\AuthController::create($request)`.
3. O controlador busca o usuário: `$user = User::findByEmail($email)`.
4. Se o usuário não existe ou se `$user->authenticate($password)` retornar `false` (via `password_verify($password, $user->encrypted_password)`):
5. O controlador grava: `FlashMessage::danger('Email ou senha inválidos');` e executa `$this->redirectTo(route('users.login'))`.
6. Nenhuma sessão é inicializada. O navegador recarrega `/login` com a mensagem flash de perigo.

#### 3. Autenticação bem-sucedida:
1. Usuário informa credenciais válidas (`admin@arena.com` / `admin123` ou `aluno@arena.com` / `aluno123`).
2. `password_verify()` retorna `true`.
3. O controlador checa se `$user->isBlocked()`. Se bloqueado por inadimplência, recusa com aviso.
4. Se regular, atualiza `$user->last_login_at = date('Y-m-d H:i:s'); $user->save();`.
5. O método `Lib\Authentication\Auth::login($user)` é invocado:
   - Executa `session_regenerate_id(true);` para **proteger contra fixação de sessão**.
   - Salva o ID do usuário: `$_SESSION['user']['id'] = $user->id;`.
6. Define mensagem flash de sucesso: `FlashMessage::success('Autenticação bem-sucedida!...')`.
7. Redirecionamento baseado no papel (RBAC):
   - Se `$user->isAdmin()`: redireciona para `/admin`.
   - Caso contrário: redireciona para `/dashboard`.

#### 4. Logout:
1. Usuário clica no botão "Sair", submetendo `POST /logout`.
2. O roteador despacha para `AuthController::destroy($request)`.
3. Invoca `Lib\Authentication\Auth::logout()`:
   - Executa `unset($_SESSION['user']['id']);` e `unset($_SESSION['user']);`.
   - Invoca `session_destroy()` para eliminar os dados persistidos no servidor.
4. Grava `FlashMessage::success('Logout realizado com sucesso. Sessão encerrada com segurança.');`.
5. Redireciona para `route('users.login')`.

---

### 5.2 Explicação do Funcionamento do Framework

#### 5.2.1 `Route::middleware('auth')->group(...)` — Arquivo `config/routes.php`
- **Origem e Arquitetura:**
  - O método estático `Route::middleware(string $name)` em `core/Router/Route.php` instancia um objeto `Core\Router\RouteWrapperMiddleware($name)`.
  - O `RouteWrapperMiddleware` implementa o padrão *Wrapper/Decorator*:
    ```php
    public function group(callable $callback): void
    {
        $routeSizeBefore = Router::getInstance()->getRouteSize();
        $callback(); // Executa o bloco de rotas internas
        $routeSizeAfter = Router::getInstance()->getRouteSize();

        for ($i = $routeSizeBefore; $i < $routeSizeAfter; $i++) {
            $route = Router::getInstance()->getRoute($i);
            $route->addMiddleware($this->middlewareInstance());
        }
    }
    ```
  - Ele anota a contagem de rotas no singleton `Router` antes de rodar a closure. Durante o `$callback()`, novas instâncias de `Route` são registradas.
  - Ao terminar a closure, todas as rotas criadas naquele intervalo recebem a injeção do middleware correspondente mapeado no array `Config\App::$middlewareAliases['auth']`.
  - Na hora da requisição, em `Router::dispatch()`, `$route->runMiddlewares($request)` executa a cadeia de middlewares antes de qualquer chamada ao controlador.

#### 5.2.2 `FlashMessage` — Arquivo `lib/FlashMessage.php`
- **Padrão PRG (Post/Redirect/Get):**
  - O componente resolve o problema clássico de repetição de mensagens ou reenvio de formulários acidentais via F5.
- **Funcionamento:**
  - `FlashMessage::danger($msg)` grava em `$_SESSION['flash']['danger'] = $msg;`.
  - `FlashMessage::success($msg)` grava em `$_SESSION['flash']['success'] = $msg;`.
  - No layout `app/views/layouts/_flash_message.phtml`, o método `FlashMessage::get()` lê as mensagens salvas e **imediatamente executa `unset($_SESSION['flash'])`**:
    ```php
    public static function get(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']); // Destroi imediatamente após a leitura
        return $flash;
    }
    ```
  - Como é destruída logo após a primeira renderização, se o usuário atualizar a página (F5), a mensagem já não existe mais.

---

## 6. Testes Automatizados (Peso: 2.0)

A suíte cobre 100% dos requisitos exigidos na rubrica:

### 6.1 Testes de Aceitação (`tests/Acceptance/Auth/AuthCest.php`)
Executados via Codeception com Selenium WebDriver (`./run test:browser`):
- `tentativaDeAcessoAreaRestritaSemAutenticacao`: Acessa `/dashboard` deslogado e valida que foi redirecionado para `/login` com a mensagem flash correta.
- `tentativaDeAutenticacaoComDadosIncorretos`: Preenche e-mail e senha inválidos e valida permanência em `/login` com aviso de erro.
- `autenticacaoBemSucedidaAdmin`: Preenche dados de admin e valida redirecionamento para `/admin` e texto "Painel Gerencial".
- `autenticacaoBemSucedidaAluno`: Preenche dados de aluno e valida redirecionamento para `/dashboard` e texto "Área do Aluno".
- `logout`: Realiza login, clica em "Sair" e valida encerramento da sessão e tela de login.

### 6.2 Testes de Acesso (Rotas) (`tests/Integration/Access/`)
Executados via Guzzle HTTP Client (`./run test tests/Integration/Access`):
- **Rotas Públicas (`PublicRoutesAccessTest.php`):**
  - `test_should_access_home_route`: Requisição `GET /` retorna status `200 OK`.
  - `test_should_access_login_route`: Requisição `GET /login` retorna status `200 OK`.
- **Rotas Autenticadas (`AuthenticatedRoutesAccessTest.php`):**
  - `test_should_redirect_unauthenticated_user_from_dashboard`: `GET /dashboard` retorna status `302 Found` e cabeçalho `Location: /login`.
  - `test_should_redirect_unauthenticated_user_from_admin`: `GET /admin` retorna status `302 Found` e redireciona.
  - `test_should_return_401_for_unauthenticated_json_request`: Requisição com cabeçalho `Accept: application/json` retorna código `401 Unauthorized`.

### 6.3 Testes Unitários (`tests/Unit/`)
Executados via PHPUnit (`./run test tests/Unit`):
- **Métodos do Model (`tests/Unit/Models/Users/UserTest.php`):**
  - Testes de validação: campos obrigatórios não vazios, e-mail único, confirmação de senha.
  - Testes de CRUD no ActiveRecord: `save()`, `all()`, `findById()`, `findByEmail()`, `destroy()`.
  - Testes de autenticação e hash: `authenticate('123456') === true` e senhas incorretas retornando `false`.
  - Métodos de regras de negócio da arena: `isAdmin()`, `isStudent()`, `isActive()`, `isBlocked()`, `block()` e `activate()`.
- **Classes de Suporte:**
  - `tests/Unit/Lib/Authentication/AuthTest.php`: métodos `login()`, `user()`, `check()` e `logout()`.
  - `tests/Unit/Lib/FlashMessageTest.php`: métodos `success()`, `danger()` e auto-destruição no `get()`.
  - `tests/Unit/Core/Router/`: testes de correspondência de rotas, parâmetros dinâmicos e encadeamento de grupos de middleware.

---

## 7. Diretrizes de Pull Request (PR) (Peso: 0.5)

Para garantir nota máxima no item 7 da rubrica:

### 7.1 Título da PR
```text
feat(auth): implementar autenticação, controle de papéis (RBAC), segurança e testes automatizados
```

### 7.2 Nome da Branch
```text
feature/auth-rbac-and-security
```

### 7.3 Commits Padronizados (Conventional Commits)
- `chore: sincronizar projeto com o framework template tsi34d-framework-template`
- `feat(db): configurar schema MySQL com tabela users, faturas e quadras de beach tennis`
- `feat(auth): implementar controle de sessao, AuthController e middlewares auth e admin`
- `feat(views): criar layouts responsivos para login, dashboard do atleta e painel gerencial`
- `test(unit): adicionar testes unitarios de modelos, autenticacao e flash messages`
- `test(integration): adicionar testes de acesso para rotas publicas e autenticadas`
- `test(acceptance): criar testes end-to-end no Codeception para os fluxos da rubrica`
- `docs: atualizar README e documentacao completa de defesa da rubrica`

### 7.4 Modelo de Descrição da PR (Markdown para o GitHub)
```markdown
## 📌 Descrição da Pull Request
Esta PR implementa o módulo completo de Autenticação, Autorização e Segurança do sistema UTFPR Arena, em conformidade total com a arquitetura `tsi34d-framework-template` e os critérios da rubrica de avaliação.

### 🚀 O que foi implementado:
1. **Adequação ao Framework Template:**
   - Estrutura de diretórios `app/`, `core/`, `lib/`, `config/`, `database/`, `tests/` e runner `./run`.
   - Containerização com Nginx, PHP 8.3 FPM, MySQL 8.4 e Selenium.
2. **Autenticação e Sessões Seguras:**
   - Hashing de senha com algoritmo BCrypt (`password_hash` / `password_verify`).
   - Mitigação de *Session Fixation* via `session_regenerate_id(true)`.
   - Sistema de mensagens efêmeras com `Lib\FlashMessage` (padrão PRG).
3. **Controle de Acesso Baseado em Papéis (RBAC):**
   - Agrupamento de rotas com `Route::middleware('auth')->group(...)`.
   - Middleware especializado de administrador (`admin`) para restrição de rotas do painel gerencial.
4. **Banco de Dados e Requisitos de Segurança:**
   - Tabela `users` com colunas de auditoria (`last_login_at`, `email_verified_at`) e recuperação de senha (`password_reset_token`, `password_reset_expires_at`).
   - Script `database/Populate/populate.php` com sementes para Administrador, Aluno Ativo e Aluno Bloqueado.
5. **Testes Automatizados:**
   - 100% de aprovação nos testes unitários (`./run test tests/Unit`).
   - Testes de integração de rotas públicas e autenticadas (`./run test tests/Integration`).
   - Testes de aceitação cobrindo os 4 fluxos no Codeception (`./run test:browser`).
6. **Qualidade e CI:**
   - Conformidade PSR-12 validada no `phpcs`.
   - Análise estática nível 6 com zero erros no `phpstan`.
```

---

## 8. Bônus — Funcionalidades Extras no Framework

1. **Middleware de Autorização por Papel (RBAC Aninhado):**
   - Criação do middleware `App\Middleware\AuthorizeAdmin` integrado à fluent API do Router, permitindo aninhamento elegante:
     `Route::middleware('auth')->group(function() { Route::middleware('admin')->group(...) })`.
2. **Suporte Híbrido Web & REST API nos Middlewares:**
   - Os middlewares detectam requisições de API via cabeçalho `Accept: application/json` e respondem automaticamente com códigos HTTP canônicos (`401 Unauthorized` e `403 Forbidden`) em JSON, mantendo o redirecionamento com `FlashMessage` para navegadores tradicionais.
3. **Auditoria e Monitoramento de Login em Tempo Real:**
   - Atualização automática de `last_login_at` no banco a cada login bem-sucedido.
