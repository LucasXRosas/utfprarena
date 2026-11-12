# Bônus: Apelido do Atleta

> Branch: `feature/bônus`. Ela serve só para testar e validar o conteúdo e **não deve ser mergeada**.

## Objetivo

Adicionar o campo **apelido** (`nickname`) ao atleta:

1. Coluna `nickname` na tabela `users` (`NOT NULL`), com validação de nulos e vazios no **Model**.
2. Campo nos formulários de **cadastro** e **edição**.
3. Persistência via **ActiveRecord** e exibição nos **detalhes do atleta**.
4. Testes de persistência válida e de rejeição de valores nulos e vazios.

## Fluxo do dado

```
Formulário (_form.phtml)  →  UsersController::userParams()  →  User::save()
   user[nickname]              trim + '' vira null              validates() → INSERT/UPDATE
                                                                     ↓
                                    show.phtml  ←  User::findById()  ←  users.nickname
```

---

## 1. Banco de dados

**Arquivo:** [database/schema.sql](../database/schema.sql) (linha 11)

```sql
nickname VARCHAR(50) NOT NULL,
```

- A coluna fica logo depois de `name`.
- Sem `DEFAULT`: o banco também recusa o `INSERT` sem apelido. É a segunda camada de proteção, já que a primeira é o Model.
- Para aplicar a mudança, rode `./run db:populate` (o `populate.php` chama `Database::migrate()`, que recria as tabelas).

## 2. Model: validação de nulos e vazios

**Arquivo:** [app/Models/User.php](../app/Models/User.php)

| Linha | Alteração |
|-------|-----------|
| 13 | `@property string $nickname` (tipagem para o PHPStan/IDE) |
| 48 | Constante `NICKNAME_MAX_LENGTH = 50`, a mesma do `VARCHAR(50)` e usada também na view |
| 55 | `'nickname'` em `$columns`: o ActiveRecord passa a ler e gravar a coluna |
| 98-99 | Regras no `validates()` |

```php
// Apelido: obrigatório (rejeita null, '' e só espaços), espelhando o NOT NULL da coluna
Validations::notEmpty('nickname', $this);
Validations::maxLength('nickname', $this, self::NICKNAME_MAX_LENGTH);
```

`Validations::notEmpty` usa `isBlank()`, que trata `null`, `''` e strings só com espaços como vazias. A mensagem de erro é `não pode ser vazio!`, a mesma dos demais campos obrigatórios.

Como a validação fica no Model, ela vale para qualquer origem: formulário, `populate.php` ou testes. O `save()` retorna `false` e nada chega ao banco.

## 3. Controller

**Arquivo:** [app/Controllers/Admin/UsersController.php](../app/Controllers/Admin/UsersController.php) (linha 275)

```php
'nickname' => $text('nickname'),
```

O campo entrou na lista explícita de parâmetros aceitos (`userParams()`), que protege contra atribuição em massa. O helper `$text` faz `trim()` e converte `''` em `null`. Os métodos `create` e `update` não mudaram: os dois já usam `userParams()`, e em caso de erro renderizam o formulário de novo com status 422.

## 4. Views

### Formulário de cadastro e edição

**Arquivo:** [app/views/admin/users/_form.phtml](../app/views/admin/users/_form.phtml) (linhas 40-46)

- Campo `user[nickname]` (`id="user_nickname"`), obrigatório (`*`), com `maxlength` vindo de `User::NICKNAME_MAX_LENGTH`.
- O partial é compartilhado, então o campo aparece em `new.phtml` e em `edit.phtml`.
- Com erro, o campo recebe `is-invalid` e a mensagem aparece embaixo (`#error_user_nickname`), seguindo o padrão já existente.
- Na edição, o valor salvo vem preenchido. Depois de um erro, o valor digitado é mantido.

### Detalhes do atleta

**Arquivo:** [app/views/admin/users/show.phtml](../app/views/admin/users/show.phtml) (linha 48)

Nova linha "Apelido:" no card "Dados Cadastrais" (`id="athlete_nickname"`), escapada com `htmlspecialchars`.

## 5. Seed

**Arquivo:** [database/Populate/populate.php](../database/Populate/populate.php)

Como a coluna agora é `NOT NULL` e validada, todos os usuários do seed receberam um apelido (o primeiro nome). Sem isso, o `save()` falharia.

---

## 6. Testes

### Novos testes

**Unitários (Model):** [tests/Unit/Models/Users/UserTest.php](../tests/Unit/Models/Users/UserTest.php)

| Teste | O que verifica |
|-------|----------------|
| `test_valid_nickname_is_persisted_with_active_record` | Salva e recarrega do banco com `findById`: o apelido vem igual |
| `test_nickname_can_be_updated_and_persisted` | A edição do apelido é gravada |
| `test_null_nickname_is_rejected` | `null`: `save()` retorna `false`, o erro aparece e nada é gravado |
| `test_empty_nickname_is_rejected` | `''`: idem |
| `test_blank_nickname_with_only_spaces_is_rejected` | `'   '`: idem |
| `test_existing_user_cannot_have_nickname_cleared` | Um usuário existente não pode ter o apelido apagado, e o valor no banco continua o original |
| `test_nickname_respects_max_length` | 51 caracteres são rejeitados |

`test_errors_should_return_errors` também passou a verificar o erro de `nickname`.

**Integração (Controller):** [tests/Integration/Controllers/AdminControllerTest.php](../tests/Integration/Controllers/AdminControllerTest.php)

| Teste | O que verifica |
|-------|----------------|
| `test_create_rejects_empty_nickname` | O POST com apelido só de espaços não redireciona, mostra o erro embaixo do campo e não cria o usuário |
| `test_update_persists_new_nickname` | O PUT grava o novo apelido |
| `test_update_rejects_empty_nickname` | O PUT com apelido vazio mostra o erro e mantém o valor antigo |
| `test_create_succeeds` (ajustado) | Confere o apelido gravado |
| `test_create_fails_with_missing_fields` (ajustado) | Confere `#error_user_nickname` |
| `test_show_renders_user_details` (ajustado) | O apelido aparece em `#athlete_nickname` |

**Aceitação (navegador):** [tests/Acceptance/Admin/UsersCrudCest.php](../tests/Acceptance/Admin/UsersCrudCest.php)

O cadastro preenche `user[nickname]`, e o envio vazio confere o erro em `#error_user_nickname`.

### Fixtures ajustadas

Todos os `new User([...])` dos testes ganharam `'nickname'`, porque sem ele o `save()` falha:

- `tests/Acceptance/Auth/AuthCest.php`
- `tests/Integration/Controllers/AdminControllerTest.php`
- `tests/Integration/Controllers/AuthControllerTest.php`
- `tests/Integration/Controllers/CourtGalleryControllersTest.php`
- `tests/Integration/Controllers/DashboardControllerTest.php`
- `tests/Integration/Controllers/ProfileControllerTest.php`
- `tests/Unit/Lib/Authentication/AuthTest.php`
- `tests/Unit/Models/InvoiceTest.php`
- `tests/Unit/Services/ProfileAvatarTest.php`

### Resultado

| Comando | Resultado |
|---------|-----------|
| `./run test` (PHPUnit completo) | **OK: 216 testes, 587 asserções** |
| `./run test:browser tests/Acceptance/Admin/UsersCrudCest.php` | **OK: 8 testes, 44 asserções** |
| `./run phpcs` | sem erros |
| `./run phpstan` | sem erros |

Para rodar só os testes do apelido:

```bash
docker compose run --rm php_test ./vendor/bin/phpunit --testdox --filter 'nickname|Nickname' tests
```

## Como testar manualmente

1. `./run up -d` e depois `./run db:populate`
2. Faça login como `admin@arena.com` / `admin123`.
3. Em **/admin/users/new**, envie o formulário com o apelido vazio. O erro "Não pode ser vazio!" aparece embaixo do campo.
4. Preencha o apelido e salve. Abra o atleta (**/admin/users/{id}**) e confira a linha "Apelido".
5. Em **Editar**, apague o apelido e salve. O erro aparece e o valor antigo é mantido no banco.
