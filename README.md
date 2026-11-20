# UTFPR Arena Beach Tennis — Backend Framework

> **Disciplina:** TSI34D — Frameworks Web / Backend  
> **Arquitetura Base:** Template Oficial UTFPR `tsi34d-framework-template`  
> **Linguagem & Ambiente:** PHP 8.3 | Nginx | MySQL 8.4 | Docker & Docker Compose | Selenium WebDriver  
> **Documentação Completa (Wiki):** [https://github.com/LucasXRosas/utfprarena/wiki](https://github.com/LucasXRosas/utfprarena/wiki)

---

## 1. Apresentação da Funcionalidade

### 1.1 Propósito e Importância no Sistema
O **UTFPR Arena** é um sistema de gerenciamento de arenas esportivas de *Beach Tennis*. O sistema automatiza o controle de acesso, o ciclo de faturamento recorrente de mensalidades e a mitigação ativa de inadimplência:
- **Lógica de Acesso:** O sistema bloqueia automaticamente o acesso de alunos com pendências financeiras prolongadas (mais de 1 mês de atraso).
- **Reativação Instantânea:** Assim que as faturas em aberto são quitadas, o motor de regras restabelece o status do atleta para `ATIVO` em tempo real.
- **Segurança e Isolamento:** Implementação robusta de autenticação baseada em sessões com hashes de senha seguros via BCrypt, proteção contra *Session Fixation* e controle de acesso baseado em papéis (RBAC).

### 1.2 Diferenças de Ações entre os Tipos de Usuários
| Tipo de Usuário | Papel (`role`) | Área Exclusiva | Permissões e Ações Permitidas |
|---|---|---|---|
| **Visitante** | Não autenticado | `/` e `/login` | Visualizar página inicial pública e efetuar login. Bloqueado de qualquer área restrita. |
| **Aluno (Atleta)** | `student` | `/dashboard`, `/profile`, `/courts/{id}` | Visualizar credencial de acesso ("Apto para Jogar"), histórico de mensalidades, as turmas em que está matriculado, quadras disponíveis e suas galerias de fotos; enviar/remover a própria foto de perfil. |
| **Administrador (Gestor)** | `manager` | `/admin`, `/admin/courts`, `/admin/classes` | Acesso ao Painel Gerencial, métricas de faturamento e atletas, controle manual de status (bloquear / ativar atleta), gestão das quadras com upload/remoção de fotos da galeria e gestão das turmas com matrícula/remoção de alunos via Ajax. |

### 1.3 Imagens e Arquivos (Relação 1xN)
| Funcionalidade | Rotas | Armazenamento |
|---|---|---|
| **Galeria de fotos das quadras** (uma quadra possui N imagens) | `GET /admin/courts/{id}`, `POST /admin/courts/{court_id}/images`, `DELETE /admin/courts/{court_id}/images/{id}`, `GET /courts/{id}` | Arquivo em `public/assets/uploads/courts/{court_id}/`; metadados na tabela `court_images` (FK `court_id` com `ON DELETE CASCADE`) |
| **Foto de perfil (avatar)** | `GET /profile`, `POST /profile/avatar`, `DELETE /profile/avatar` | Arquivo em `public/assets/uploads/users/{user_id}/`; nome na coluna `users.avatar_name` |

Validações de upload (`app/Services/ImageValidator.php`): apenas **JPG/JPEG, PNG e WEBP**, até **2MB**, MIME type real verificado pelo conteúdo, bloqueio de extensões executáveis (ex: `foto.php.jpg`) e de imagens com código embutido.

### 1.4 Turmas e Matrículas (Relação NxN + Ajax)
Uma **turma** (`training_classes`) possui N **alunos** e um aluno participa de N turmas. A relação é guardada na tabela pivô `class_enrollments` (`training_class_id`, `user_id`), com `UNIQUE (training_class_id, user_id)`.

| Ação | Rota | Observação |
|---|---|---|
| Listar / cadastrar / excluir turmas | `GET /admin/classes`, `GET /admin/classes/new`, `POST /admin/classes`, `DELETE /admin/classes/{id}` | Turma com alunos matriculados **não pode ser excluída** (validação no model + `ON DELETE RESTRICT`) |
| Ver alunos da turma | `GET /admin/classes/{id}` | Lado turma → alunos (`TrainingClass::students()`, `BelongsToMany`) |
| Matricular aluno (Ajax) | `POST /admin/classes/{class_id}/enrollments` | Responde JSON (`201`/`422`) quando a requisição tem `Accept: application/json` |
| Remover matrícula (Ajax) | `DELETE /admin/classes/{class_id}/enrollments/{user_id}` | Remove só a linha da pivô; turma e aluno continuam existindo |
| Ver turmas do aluno | `GET /dashboard` (Minhas Turmas) e `GET /admin/users/{id}` | Lado aluno → turmas (`User::trainingClasses()`, `BelongsToMany`) |

O JavaScript fica em `public/assets/js/enrollments.js` (XMLHttpRequest + Promise + async/await). Sem JavaScript, os mesmos formulários funcionam com redirect + flash message.

---

## 2. Passo a Passo para Executar o Projeto

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
Execute o reset do schema MySQL para criar as tabelas `users`, `courts`, `invoices`, `court_images`, `training_classes` e `class_enrollments`:
```bash
./run db:reset
```

### Passo 6: Popular o Banco de Dados com Dados Iniciais (Seeds)
Execute o script de população para semear os perfis da demonstração (Admin, Aluno Ativo, Aluno Inadimplente, quadras e faturas):
```bash
./run db:populate
```

### Passo 7: Configurar Permissões de Uploads (necessário no Linux para o upload de imagens)
```bash
sudo chown -R www-data:www-data public/assets/uploads
```

### Passo 8: Acessar a Aplicação
Abra seu navegador em:
**[http://localhost:8000](http://localhost:8000)** *(porta principal — backend PHP normal)*  
**[http://localhost:8080](http://localhost:8080)** *(porta de testes — ambiente isolado)*

> **Nota:** Caso a porta `80` esteja livre na sua máquina, você pode reverter o `docker-compose.yml` para `80:80` e acessar via `http://localhost`.

---

## 3. Dados de Acesso Pré-configurados (Seeds)

| Perfil | E-mail | Senha | Área Redirecionada | Cenário de Demonstração |
|---|---|---|---|---|
| **Administrador** | `admin@arena.com` | `admin123` | `/admin` | Acesso completo ao painel gerencial e bloqueio de usuários. |
| **Aluno Ativo** | `aluno@arena.com` | `aluno123` | `/dashboard` | Acesso regular liberado às quadras e faturas em dia. |
| **Aluno Inadimplente** | `bloqueado@arena.com` | `aluno123` | N/A (Bloqueado) | Demonstração de bloqueio imediato na tela de login por pendência financeira. |

---

## 4. Execução dos Testes Automatizados

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
Executa a bateria de testes de navegador (autenticação, CRUD, galeria de imagens e avatar):
```bash
./run test:browser
```
*(ou equivalentemente: `./run codecept run acceptance`)*

> **Ambiente isolado:** o navegador acessa a porta de testes `http://web:8080` (container `php_test`, banco `arena_test`, uploads em `public/assets/uploads/test`). Antes de **cada** cenário o banco de teste é recriado e populado pelo `Database\Populate\Seeder` (o mesmo usado pelo `./run db:populate`). Por isso:
> - os testes podem ser executados várias vezes seguidas, em qualquer ordem, sem `db:reset`;
> - o banco de desenvolvimento (`arena_development`) e os uploads usados no navegador **não são alterados**.

---

## 5. Linters e Análise Estática de Código

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

## 6. Testes de API (cURL / HTTP REST)

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
