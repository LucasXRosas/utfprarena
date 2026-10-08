# 🎾 UTFPR Arena Beach Tennis — Backend Framework

> **Disciplina:** TSI34D — Frameworks Web / Backend  
> **Arquitetura Base:** Template Oficial UTFPR `tsi34d-framework-template`  
> **Linguagem & Ambiente:** PHP 8.3 | Nginx | MySQL 8.4 | Docker & Docker Compose | Selenium WebDriver  
> **Documentação Completa (Wiki):** [https://github.com/LucasXRosas/utfprarena/wiki](https://github.com/LucasXRosas/utfprarena/wiki)

---

## 📌 1. Apresentação da Funcionalidade

### 1.1 Propósito e Importância no Sistema
O **UTFPR Arena** é um sistema de gerenciamento de arenas esportivas de *Beach Tennis*. O sistema automatiza o controle de acesso de atletas às quadras de areia, o ciclo de faturamento recorrente de mensalidades e a mitigação ativa de inadimplência:
- **Catraca Lógica de Acesso:** O sistema bloqueia automaticamente o acesso de alunos com pendências financeiras prolongadas (mais de 2 meses de atraso).
- **Reativação Instantânea:** Assim que as faturas em aberto são quitadas, o motor de regras restabelece o status do atleta para `ATIVO` em tempo real.
- **Segurança e Isolamento:** Implementação robusta de autenticação baseada em sessões com hashes de senha seguros via BCrypt, proteção contra *Session Fixation* e controle de acesso baseado em papéis (RBAC).

### 1.2 Diferenças de Ações entre os Tipos de Usuários
| Tipo de Usuário | Papel (`role`) | Área Exclusiva | Permissões e Ações Permitidas |
|---|---|---|---|
| **Visitante** | Não autenticado | `/` e `/login` | Visualizar página inicial pública e efetuar login. Bloqueado de qualquer área restrita. |
| **Aluno (Atleta)** | `student` | `/dashboard` | Visualizar credencial de acesso ("Apto para Jogar"), histórico de mensalidades e quadras disponíveis da arena. |
| **Administrador (Gestor)** | `manager` | `/admin` | Acesso ao Painel Gerencial, métricas de faturamento e atletas, e controle manual de status (bloquear / ativar atleta). |

---

## 🚀 2. Passo a Passo para Executar o Projeto

### Pré-requisitos
- [Docker](https://docs.docker.com/engine/install/) instalado
- [Docker Compose](https://docs.docker.com/compose/install/) instalado

---

### Passo 1: Clonar o Repositório
```bash
git clone https://github.com/LucasXRosas/utfprarena.git
cd utfprarena
```

### Passo 2: Configurar o Arquivo de Ambiente (`.env`)
Copie o arquivo de exemplo de variáveis de ambiente:
```bash
cp .env.example .env
```

### Passo 3: Instalar as Dependências do PHP (Composer via Docker)
Utilize o runner do projeto para baixar e instalar as dependências dentro do contêiner isolado:
```bash
./run composer install
```

### Passo 4: Subir os Contêineres da Aplicação
Inicie os serviços do Docker (Nginx, PHP-FPM, MySQL e Selenium):
```bash
./run up -d
```
*(ou equivalentemente: `docker compose up -d`)*

Para checar se todos os contêineres subiram com sucesso:
```bash
./run ps
```

### Passo 5: Criar o Banco de Dados e as Tabelas
Execute o reset do schema MySQL para criar as tabelas `users`, `courts` e `invoices`:
```bash
./run db:reset
```

### Passo 6: Popular o Banco de Dados com Dados Iniciais (Seeds)
Execute o script de população para semear os perfis da demonstração (Admin, Aluno Ativo, Aluno Inadimplente, quadras e faturas):
```bash
./run db:populate
```

### Passo 7: Configurar Permissões de Uploads (Opcional)
```bash
sudo chown -R www-data:www-data public/assets/uploads
```

### Passo 8: Acessar a Aplicação
Abra seu navegador em:
👉 **[http://localhost:8080](http://localhost:8080)** *(ou [http://localhost](http://localhost))*

---

## 🔑 3. Dados de Acesso Pré-configurados (Seeds)

| Perfil | E-mail | Senha | Área Redirecionada | Cenário de Demonstração |
|---|---|---|---|---|
| **Administrador** | `admin@arena.com` | `admin123` | `/admin` | Acesso completo ao painel gerencial e bloqueio de usuários. |
| **Aluno Ativo** | `aluno@arena.com` | `aluno123` | `/dashboard` | Acesso regular liberado às quadras e faturas em dia. |
| **Aluno Inadimplente** | `bloqueado@arena.com` | `aluno123` | N/A (Bloqueado) | Demonstração de bloqueio imediato na tela de login por pendência financeira. |

---

## 🧪 4. Execução dos Testes Automatizados

O projeto conta com suíte completa de testes automatizados conforme a rubrica de avaliação:

### 4.1 Testes Unitários e de Integração (PHPUnit)
Executa todos os testes de Models, Libs, Core e Acesso a Rotas:
```bash
./run test
```

Para rodar apenas os testes unitários de Models:
```bash
./run test tests/Unit/Models
```

Para rodar apenas os testes de integração de acesso a rotas:
```bash
./run test tests/Integration/Access
```

### 4.2 Testes de Aceitação / End-to-End (Codeception + Selenium)
Executa a bateria de testes de navegador simulando os 4 fluxos exigidos na rubrica:
```bash
./run test:browser
```
*(ou equivalentemente: `./run codecept run acceptance`)*

---

## 🔍 5. Linters e Análise Estática de Código

Para garantir conformidade rigorosa com PSR-12 e tipagem estática nível 6:

- **PHP CodeSniffer (PSR-12):**
  ```bash
  ./run phpcs
  ```
  *(Para correção automática: `./run phpcbf`)*

- **PHPStan (Análise Estática Nível 6):**
  ```bash
  ./run phpstan
  ```

---

## 📡 6. Testes de API (cURL / HTTP REST)

### 6.1 Rota Não Autenticada (Bloqueio 401)
```bash
curl -i -H "Accept: application/json" http://localhost:8080/dashboard
```
**Resposta esperada:** `HTTP/1.1 401 Unauthorized` com JSON `{ "error": "Não autenticado", "code": 401 }`.

### 6.2 Rota Autenticada com Sessão (Status 200)
Substitua pelo ID da sessão retornado no cookie após login:
```bash
curl -i -H "Accept: application/json" -b "PHPSESSID=SEU_SESSION_ID_AQUI" http://localhost:8080/dashboard
```
**Resposta esperada:** `HTTP/1.1 200 OK`.

---

## 📚 7. Roteiro e Guia de Apresentação da Rubrica

Para detalhes aprofundados sobre **todos os pontos avaliados na rubrica**:
- Roteiro de demonstração prática clique a clique (Admin e Aluno)
- Explicação de conceitos com referências de livros (Stallings, Tanenbaum, Kurose, Lockhart, GoF, Ross Anderson)
- Modelagem de dados e segurança (BCrypt, tokens, timestamps)
- Explicação do funcionamento do Framework (`Route::middleware('auth')->group(...)` e `FlashMessage`)
- Diretrizes de Pull Request e Conventional Commits

Consulte o documento completo:  
👉 **[docs/GUIA_APRESENTACAO_RUBRICA.md](file:///home/lucas/Documents/UTFPR/backend/project/utfprarena/docs/GUIA_APRESENTACAO_RUBRICA.md)**
