# Guia Prático para a Validação de Imagens/Arquivos (1xN) — UTFPR Arena

> Branch: `feature/bônus_2`. Ela serve só para consulta durante a validação e **não deve ser mergeada**.

Roteiro de consulta rápida para a validação presencial da entrega **Imagens/Arquivos (Relação 1xN)**.

**Nível esperado.** Nas validações anteriores o professor pediu algo do **porte da entrega**, sempre atravessando todas as camadas e com testes:
- **Autenticação:** novo tipo de usuário + nova página de login + código aleatório.
- **CRUD:** campo `nickname` obrigatório, do banco à view, com validação no Model e testes (ver `docs/BONUS_APELIDO.md` na branch `feature/bônus`).

Para imagens/arquivos, espere pedidos como:

| # | Pedido provável | Porte | Seção |
|---|---|---|---|
| 1 | "Crie **outra relação 1xN com arquivos**: documentos (PDF) do atleta / anexos / posts com imagem" | **grande**: é a entrega de novo | [§2](#2-cenário-1--nova-relação-1xn-com-arquivos-documentos-pdf-do-atleta) |
| 2 | "Limite a galeria a N fotos" / "legenda obrigatória" / "dimensão mínima" / "aceite outro formato ou outro tamanho" | médio | [§3](#3-cenário-2--novas-regras-de-upload-na-galeria) |
| 3 | "Em vez de apagar em cascata, **impeça** a exclusão da quadra com fotos (RESTRICT)" / "mostre o SET NULL" | médio, cai nos conceitos da rubrica | [§4](#4-cenário-3--trocar-a-ação-referencial-restrict--set-null) |
| 4 | "Permita **editar a legenda**" / "**definir a foto de capa**" | médio | [§5](#5-cenário-4--editar-legenda-e-definir-foto-de-capa) |
| 5 | "Envie **várias fotos de uma vez**" | médio, com pegadinha do `$_FILES` | [§6](#6-cenário-5--upload-múltiplo) |
| 6 | "Agora escreva os testes" | sempre | [§7](#7-testes-para-qualquer-alteração) |

> ⚠️ **Antes de começar:** `git checkout -b validacao-arquivos` a partir da `main`. Depois da validação: `git checkout main && git branch -D validacao-arquivos`.
>
> ⚠️ **Os trechos deste guia não estão aplicados no projeto.** Foram escritos sobre o código atual da `main` (após o PR #12) para serem colados durante a validação. Sempre rode `php -l` no arquivo e teste no navegador depois de colar.

---

## 1. Mapa da funcionalidade atual (decore isto)

### 1.1 Fluxo de um upload

```
<form method="POST" enctype="multipart/form-data" novalidate>   (admin/courts/show.phtml, linha 32)
        │  POST /admin/courts/{court_id}/images
        ▼
config/routes.php (linhas 52–61) ── middleware auth + admin
        ▼
Admin\CourtImagesController::create()
        │  Court::findById()                      → não existe? "Galeria inexistente"
        │  $request->getFile('image')             → entrada de $_FILES (Request.php)
        ▼
Services\CourtGallery::add()                       (linha 55)
        ├─ ImageValidator::validate()              → 8 regras (tamanho, formato, MIME real, malicioso…)
        ├─ FileStorage::randomName() + store()     → move_uploaded_file p/ uploads/courts/{id}/
        └─ $court->images()->new([...])->save()    → INSERT em court_images (falhou? apaga o arquivo)
        ▼
sucesso: flash + redirect (PRG)      erro: renderiza a galeria de novo (422) com o erro embaixo do campo
```

### 1.2 Onde está cada coisa

| Peça | Arquivo | Papel |
|---|---|---|
| Tabela 1xN | `database/schema.sql` (linhas 49–64) | `court_images`, FK `court_id` com `ON DELETE CASCADE` |
| Model "N" | `app/Models/CourtImage.php` | `$columns`, `validates()` (linha 50), `court(): BelongsTo`, `relativePath()`, `url()` |
| Model "1" | `app/Models/Court.php` | `images(): HasMany`, `gallery()`, `coverImage()`, `destroy()` (apaga a pasta) |
| Validação do arquivo | `app/Services/ImageValidator.php` | construtor (linha 48) aceita `maxSize` e `allowedTypes` |
| Disco | `app/Services/FileStorage.php` | `store`, `delete`, `deleteDirectory`, `exists`, `absolutePath`, `url`, `randomName` |
| Regra de negócio | `app/Services/CourtGallery.php` | `add()` (linha 55), `remove()` (linha 109): banco primeiro, arquivo depois |
| 1x1 (avatar) | `app/Services/ProfileAvatar.php` | `update()`, `remove()`, `removeAll()` |
| `$_FILES` | `core/Http/Request.php` | `getFile('campo')`, `hasFile()`, `addFiles()` (testes) |
| Pasta pública | `Core\Constants\Constants::uploadsPath()/uploadsUrl()` | `public/assets/uploads` (testes: `.../uploads/test`) |
| Seed | `database/Populate/Seeder.php` | usado por `./run db:populate` **e** pelos testes de aceitação |

### 1.3 Regras que o professor costuma perguntar
- **O banco não guarda o binário**, só os metadados e o `file_name`. O arquivo fica no disco e o Nginx o serve como estático.
- **O nome é aleatório** (`court_<32 hex>.jpg`): evita colisão, sobrescrita, *path traversal* e adivinhação de URL. A extensão vem do **MIME real**, nunca do nome enviado.
- **Ordem das operações** (PR #12):
  - **upload:** grava o arquivo → INSERT → se o INSERT falhar, apaga o arquivo;
  - **remoção:** DELETE → só depois apaga o arquivo;
  - **troca de avatar:** grava o novo → UPDATE → só depois apaga o antigo.
- **CASCADE apaga linhas, não arquivos.** Por isso `Court::destroy()` chama `CourtGallery::destroyFiles()`.
- **`novalidate`** em todos os formulários: quem valida é o backend, e o erro aparece embaixo do campo.

---

## 2. Cenário 1 — Nova relação 1xN com arquivos: documentos (PDF) do atleta

**Pedido:** "Cada atleta pode ter **vários documentos** (atestado médico, termo de responsabilidade…). O admin envia, lista, baixa e remove. Só PDF, até 5MB. O título é obrigatório."

É a entrega inteira de novo, com outra entidade e outro tipo de arquivo. **User (1) ──< UserDocument (N)**.

> Se o pedido for **posts/notícias com imagem** ou **galeria de outra entidade**, a receita é a mesma: troque o PDF pelo `ImageValidator` padrão e use a galeria das quadras como molde.

### Passo 1 — Banco (`database/schema.sql`)

No topo, junto com os outros `DROP`:
```sql
DROP TABLE IF EXISTS user_documents;
```

No fim, antes do `SET foreign_key_checks = 1;`:
```sql
-- Documentos do atleta (relação 1xN: um usuário possui N documentos)
CREATE TABLE user_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    file_name VARCHAR(100) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(50) NOT NULL,
    size INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_documents_file_name (file_name),
    KEY idx_user_documents_user_id (user_id),
    CONSTRAINT fk_user_documents_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
Aplique com `./run db:reset && ./run db:populate`.

### Passo 2 — Validador aceitando PDF (`app/Services/ImageValidator.php`)

O validador já recebe `maxSize` e `allowedTypes` no construtor, mas o **passo 7** (`getimagesize`, linha 127) recusa tudo que não é imagem. Adicione um terceiro parâmetro opcional:

```php
public function __construct(
    private int $maxSize = self::MAX_SIZE,
    private array $allowedTypes = self::ALLOWED_TYPES,
    private bool $mustBeImage = true    // NOVO: false para documentos (PDF)
) {
}
```

E envolva o passo 7 com ele:
```php
// 7. Estrutura da imagem precisa ser legível (só para imagens)
if ($this->mustBeImage) {
    $imageInfo = @getimagesize($tmpPath);
    if ($imageInfo === false || $imageInfo[0] < 1 || $imageInfo[1] < 1) {
        return $this->fail('O arquivo está corrompido ou não é uma imagem válida.');
    }
}
```

Todas as outras regras continuam valendo para o PDF: tamanho, extensões executáveis em qualquer parte do nome, extensão permitida, **MIME real** (`application/pdf`, detectado pelos bytes `%PDF-`), coerência entre extensão e conteúdo, e código embutido. Uso:
```php
new ImageValidator(5 * 1024 * 1024, ['application/pdf' => ['pdf']], false);
```
> Avatar e galeria continuam iguais, porque o padrão do novo parâmetro é `true`. Se o professor perguntar, renomear a classe para `FileValidator` seria o ideal, mas exige mexer em todos os `use`. Em sala, o parâmetro é mais seguro.

### Passo 3 — Model "N" (`app/Models/UserDocument.php`, novo)

```php
<?php

namespace App\Models;

use App\Services\FileStorage;
use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

/**
 * Documento (PDF) de um atleta — lado "N" da relação User 1xN UserDocument.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $file_name
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property ?string $created_at
 */
class UserDocument extends Model
{
    public const TITLE_MAX_LENGTH = 100;

    protected static string $table = 'user_documents';

    /** @var array<int, string> */
    protected static array $columns = [
        'user_id',
        'title',
        'file_name',
        'original_name',
        'mime_type',
        'size',
        'created_at'
    ];

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);

        if ($this->created_at === null) {
            $this->created_at = date('Y-m-d H:i:s');
        }
    }

    public function validates(): void
    {
        Validations::notEmpty('user_id', $this);
        Validations::notEmpty('title', $this);
        Validations::maxLength('title', $this, self::TITLE_MAX_LENGTH);
        Validations::notEmpty('file_name', $this);
        Validations::notEmpty('original_name', $this);
        Validations::notEmpty('mime_type', $this);

        // Integridade referencial também na aplicação: o atleta precisa existir
        Validations::foreignKeyExists('user_id', $this, User::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * users/{user_id}/documents/{file_name} — dentro da pasta do usuário,
     * então User::destroy() (que apaga users/{id}) já remove os documentos do disco.
     */
    public function relativePath(): string
    {
        return 'users/' . $this->user_id . '/documents/' . $this->file_name;
    }

    public function sizeLabel(): string
    {
        return number_format(((int) $this->size) / 1024, 1, ',', '.') . ' KB';
    }
}
```

### Passo 4 — Model "1" (`app/Models/User.php`)

```php
use Core\Database\ActiveRecord\HasMany;   // já existe

public function documents(): HasMany
{
    return $this->hasMany(UserDocument::class, 'user_id');
}
```
> **Exclusão do atleta:** o banco apaga as linhas (`ON DELETE CASCADE`), e o `User::destroy()` (linha 207) já chama `$avatar->removeAll()`, que apaga **a pasta inteira** `users/{id}`, incluindo `documents/`. Nada a fazer, mas vale explicar.

### Passo 5 — Serviço (`app/Services/UserDocuments.php`, novo), molde do `CourtGallery`

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDocument;

class UserDocuments
{
    public const MAX_SIZE = 5 * 1024 * 1024; // 5MB

    private ImageValidator $validator;
    private FileStorage $storage;

    /** @var array<string, string> campo => mensagem */
    private array $errors = [];

    public function __construct(private User $user, ?ImageValidator $validator = null, ?FileStorage $storage = null)
    {
        $this->validator = $validator ?? new ImageValidator(self::MAX_SIZE, ['application/pdf' => ['pdf']], false);
        $this->storage = $storage ?? new FileStorage();
    }

    public function directory(): string
    {
        return 'users/' . $this->user->id . '/documents';
    }

    /**
     * @return array<UserDocument>
     */
    public function all(): array
    {
        /** @var array<UserDocument> $documents */
        $documents = $this->user->documents()->get();
        return $documents;
    }

    /**
     * @param array<string, mixed>|null $file entrada de $_FILES['document']
     */
    public function add(?array $file, string $title): ?UserDocument
    {
        $this->errors = [];
        $title = trim($title);

        // Erros por campo: cada um aparece embaixo do seu input
        if ($title === '') {
            $this->errors['title'] = 'Informe o título do documento.';
        }
        if (!$this->validator->validate($file)) {
            $this->errors['document'] = (string) $this->validator->firstError();
        }
        if (!empty($this->errors)) {
            return null;
        }

        $fileName = FileStorage::randomName('doc', (string) $this->validator->safeExtension());
        $relativePath = $this->storage->store((string) $file['tmp_name'], $this->directory(), $fileName);

        /** @var UserDocument $document */
        $document = $this->user->documents()->new([
            'title' => mb_substr($title, 0, UserDocument::TITLE_MAX_LENGTH),
            'file_name' => $fileName,
            'original_name' => mb_substr(basename((string) $file['name']), 0, 255),
            'mime_type' => $this->validator->mimeType(),
            'size' => (int) filesize($this->storage->absolutePath($relativePath)),
        ]);

        try {
            $saved = $document->save();
        } catch (\PDOException $e) {
            $saved = false;
        }

        if (!$saved) {
            $this->storage->delete($relativePath); // sem registro, sem arquivo
            $this->errors['document'] = 'Não foi possível registrar o documento: ' . ($document->firstError() ?? 'erro no banco');
            return null;
        }

        return $document;
    }

    public function remove(UserDocument $document): bool
    {
        $this->errors = [];

        if ((int) $document->user_id !== (int) $this->user->id) {
            $this->errors['document'] = 'O documento não pertence a este atleta.';
            return false;
        }

        $relativePath = $document->relativePath();

        try {
            $deleted = $document->destroy();     // banco primeiro…
        } catch (\PDOException $e) {
            $deleted = false;
        }

        if (!$deleted) {
            $this->errors['document'] = 'Não foi possível remover o documento.';
            return false;
        }

        $this->storage->delete($relativePath);   // …arquivo depois
        return true;
    }

    public function absolutePath(UserDocument $document): string
    {
        return $this->storage->absolutePath($document->relativePath());
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function validator(): ImageValidator
    {
        return $this->validator;
    }
}
```

### Passo 6 — Rotas (`config/routes.php`, grupo `admin`, depois das rotas de quadras)

```php
use App\Controllers\Admin\UserDocumentsController as AdminUserDocumentsController;   // no topo

// Documentos do atleta (relação 1xN)
Route::post('/admin/users/{user_id}/documents', [AdminUserDocumentsController::class, 'create'])
    ->name('admin.users.documents.create');
Route::get('/admin/users/{user_id}/documents/{id}/download', [AdminUserDocumentsController::class, 'download'])
    ->name('admin.users.documents.download');
Route::delete('/admin/users/{user_id}/documents/{id}', [AdminUserDocumentsController::class, 'destroy'])
    ->name('admin.users.documents.destroy');
```
> **Por que download por rota, e não por link direto?** O `nginx.conf` só serve como estático `css|js|jpeg|png|jpg|webp|gif|ico`. Uma URL `.pdf` cai no `index.php`, e o roteador responde 404. Isso é **bom**: documento pessoal não deve ficar público. A rota de download passa pelos middlewares `auth` + `admin`.

### Passo 7 — Controller (`app/Controllers/Admin/UserDocumentsController.php`, novo)

```php
<?php

namespace App\Controllers\Admin;

use App\Models\Invoice;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\UserDocuments;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class UserDocumentsController extends Controller
{
    /**
     * POST /admin/users/{user_id}/documents  (multipart/form-data: "document" + "title")
     */
    public function create(Request $request): void
    {
        $targetUser = $this->findUser($request);
        if ($targetUser === null) {
            return;
        }

        $documents = new UserDocuments($targetUser);
        $title = (string) $request->getParam('title', '');
        $document = $documents->add($request->getFile('document'), $title);

        if ($document === null) {
            // Mesma tela de detalhes, com os erros embaixo dos campos e o título preservado
            http_response_code(422);
            $this->renderShow($targetUser, $documents, $documents->errors(), $title);
            return;
        }

        FlashMessage::success('Documento ' . $document->title . ' enviado com sucesso!');
        $this->redirectTo(route('admin.users.show', ['id' => $targetUser->id]));
    }

    /**
     * GET /admin/users/{user_id}/documents/{id}/download
     */
    public function download(Request $request): void
    {
        $targetUser = $this->findUser($request);
        $document = $targetUser ? $this->findDocument($request, $targetUser) : null;
        if ($targetUser === null || $document === null) {
            return;
        }

        $path = (new UserDocuments($targetUser))->absolutePath($document);
        if (!is_file($path)) {
            FlashMessage::danger('Arquivo não encontrado no servidor.');
            $this->redirectTo(route('admin.users.show', ['id' => $targetUser->id]));
            return;
        }

        header('Content-Type: ' . $document->mime_type);
        header('Content-Length: ' . filesize($path));
        // filename* com rawurlencode: nomes com acento/espaço chegam corretos ao navegador
        header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($document->original_name));
        readfile($path);
    }

    /**
     * DELETE /admin/users/{user_id}/documents/{id}
     */
    public function destroy(Request $request): void
    {
        $targetUser = $this->findUser($request);
        $document = $targetUser ? $this->findDocument($request, $targetUser) : null;
        if ($targetUser === null || $document === null) {
            return;
        }

        $documents = new UserDocuments($targetUser);
        if (!$documents->remove($document)) {
            FlashMessage::danger($documents->errors()['document'] ?? 'Erro ao remover.');
        } else {
            FlashMessage::success('Documento ' . $document->title . ' removido do banco e do filesystem.');
        }

        $this->redirectTo(route('admin.users.show', ['id' => $targetUser->id]));
    }

    /* ---------------- helpers ---------------- */

    private function findUser(Request $request): ?User
    {
        $id = $request->getParam('user_id');
        $user = is_numeric($id) ? User::findById((int) $id) : null;

        if ($user === null) {
            FlashMessage::danger('Atleta inexistente.');
            $this->redirectTo(route('admin.users.index'));
            return null;
        }

        return $user;
    }

    /**
     * HasMany::findById busca DENTRO da relação (user_id + id): não dá para baixar ou apagar
     * o documento de outro atleta trocando o id na URL.
     */
    private function findDocument(Request $request, User $user): ?UserDocument
    {
        $id = $request->getParam('id');
        /** @var UserDocument|null $document */
        $document = is_numeric($id) ? $user->documents()->findById((int) $id) : null;

        if ($document === null) {
            FlashMessage::danger('Documento não encontrado para este atleta.');
            $this->redirectTo(route('admin.users.show', ['id' => $user->id]));
            return null;
        }

        return $document;
    }

    /**
     * @param array<string, string> $documentErrors
     */
    private function renderShow(User $targetUser, UserDocuments $documents, array $documentErrors, string $documentTitle): void
    {
        $title = 'Credencial do Atleta: ' . $targetUser->name;
        $invoices = Invoice::allByUser($targetUser->id);
        $documentList = $documents->all();
        $documentValidator = $documents->validator();

        $this->render(
            'admin/users/show',
            compact('title', 'targetUser', 'invoices', 'documentList', 'documentValidator', 'documentErrors', 'documentTitle')
        );
    }
}
```

**E em `UsersController::show()`** (linha 84), passe a lista e o validador para a view:
```php
$documents = new \App\Services\UserDocuments($targetUser);
$documentList = $documents->all();
$documentValidator = $documents->validator();

$this->render('admin/users/show', compact('title', 'targetUser', 'invoices', 'documentList', 'documentValidator'));
```

### Passo 8 — View (`app/views/admin/users/show.phtml`, novo card no fim do arquivo)

```php
<?php
$documentErrors = $documentErrors ?? [];
$documentTitle = $documentTitle ?? '';
?>
<div class="card border-0 shadow-sm mt-4" id="documents_card">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-file-earmark-pdf"></i> Documentos do Atleta (<?= count($documentList) ?>)
    </div>
    <div class="card-body">
        <!-- multipart obrigatório para enviar o binário; novalidate: validação no backend -->
        <form action="/admin/users/<?= $targetUser->id ?>/documents" method="POST" enctype="multipart/form-data"
              id="document_form" class="row g-3 align-items-start mb-4" novalidate>
            <div class="col-md-5">
                <label for="document_title" class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                <input type="text" class="form-control<?= isset($documentErrors['title']) ? ' is-invalid' : '' ?>"
                       id="document_title" name="title" maxlength="100" value="<?= htmlspecialchars($documentTitle) ?>"
                       placeholder="Ex: Atestado médico">
                <?php if (isset($documentErrors['title'])) : ?>
                    <div class="invalid-feedback" id="error_document_title"><?= htmlspecialchars($documentErrors['title']) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-5">
                <label for="document_file" class="form-label fw-semibold">Arquivo (PDF, até <?= $documentValidator->maxSizeLabel() ?>)</label>
                <input type="file" class="form-control<?= isset($documentErrors['document']) ? ' is-invalid' : '' ?>"
                       id="document_file" name="document" accept="<?= htmlspecialchars($documentValidator->acceptAttribute()) ?>">
                <?php if (isset($documentErrors['document'])) : ?>
                    <div class="invalid-feedback" id="error_document"><?= htmlspecialchars($documentErrors['document']) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-2 d-grid">
                <label class="form-label d-none d-md-block">&nbsp;</label>
                <button type="submit" class="btn btn-warning fw-semibold" id="btn_upload_document">Enviar</button>
            </div>
        </form>

        <?php if (empty($documentList)) : ?>
            <p class="text-muted mb-0" id="empty_documents">Nenhum documento enviado.</p>
        <?php else : ?>
            <ul class="list-group" id="documents_list">
                <?php foreach ($documentList as $doc) : ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" id="document_<?= $doc->id ?>">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($doc->title) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($doc->original_name) ?> &bull; <?= $doc->sizeLabel() ?></small>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="/admin/users/<?= $targetUser->id ?>/documents/<?= $doc->id ?>/download"
                               class="btn btn-outline-primary btn-sm" id="btn_download_document_<?= $doc->id ?>">Baixar</a>
                            <form action="/admin/users/<?= $targetUser->id ?>/documents/<?= $doc->id ?>" method="POST" class="m-0"
                                  onsubmit="return confirm('Remover este documento?');">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn btn-outline-danger btn-sm" id="btn_delete_document_<?= $doc->id ?>">Remover</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
```

### Passo 9 — Testes do cenário 1

**Fixture de PDF** (cole em `tests/Support/Upload/UploadFixtures.php`). O `finfo` reconhece `application/pdf` pelos bytes iniciais `%PDF-`:
```php
/**
 * @return array<string, mixed>
 */
public static function validPdf(string $name = 'atestado.pdf'): array
{
    $pdf = "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n";
    return self::fileEntry(self::createFile($pdf), $name);
}
```

**Unitário do serviço** (`tests/Unit/Services/UserDocumentsTest.php`), no padrão do `CourtGalleryTest`:
```php
public function setUp(): void
{
    parent::setUp();
    $this->user = new User([
        'name' => 'Atleta Docs', 'email' => 'docs@arena.com',
        'password' => '123456', 'password_confirmation' => '123456',
    ]);
    $this->user->save();
    $this->documents = new UserDocuments($this->user, null, new TestFileStorage());
}

public function tearDown(): void
{
    UploadFixtures::cleanup();
    parent::tearDown();
}

public function test_add_saves_pdf_in_database_and_filesystem(): void
{
    $doc = $this->documents->add(UploadFixtures::validPdf(), 'Atestado médico');

    $this->assertNotNull($doc);
    $this->assertEquals('application/pdf', $doc->mime_type);
    $this->assertMatchesRegularExpression('/^doc_[a-f0-9]{32}\.pdf$/', $doc->file_name);
    $this->assertFileExists($this->documents->absolutePath($doc));
    $this->assertCount(1, $this->user->documents()->get());
}

public function test_title_is_required(): void
{
    $this->assertNull($this->documents->add(UploadFixtures::validPdf(), '   '));
    $this->assertEquals('Informe o título do documento.', $this->documents->errors()['title']);
}

public function test_rejects_image_and_malicious_files(): void
{
    $this->assertNull($this->documents->add(UploadFixtures::validPng(), 'Foto'));
    $this->assertNull($this->documents->add(UploadFixtures::phpScript('contrato.php.pdf'), 'Contrato'));
    $this->assertCount(0, $this->user->documents()->get());
}

public function test_remove_deletes_record_and_file(): void
{
    $doc = $this->documents->add(UploadFixtures::validPdf(), 'Termo');
    $path = $this->documents->absolutePath($doc);

    $this->assertTrue($this->documents->remove($doc));
    $this->assertNull(UserDocument::findById($doc->id));
    $this->assertFileDoesNotExist($path);
}

public function test_destroying_user_cascades_documents(): void
{
    $doc = $this->documents->add(UploadFixtures::validPdf(), 'Termo');
    $path = $this->documents->absolutePath($doc);

    $this->user->destroy();

    $this->assertNull(UserDocument::findById($doc->id));   // ON DELETE CASCADE
    $this->assertFileDoesNotExist($path);                  // User::destroy() apagou users/{id}
}
```

**Model** (`tests/Unit/Models/UserDocumentTest.php`): `notEmpty` do título, `foreignKeyExists` com `user_id = 9999`, `belongsTo` e o INSERT puro no banco com `user_id` inexistente lançando `PDOException` "foreign key constraint fails" (copie do `CourtImageTest`).

**Acesso** (`tests/Integration/Access/AuthenticatedRoutesAccessTest.php`):
```php
public function test_should_block_unauthenticated_document_routes(): void
{
    $this->assertRedirectsToLogin('POST', '/admin/users/1/documents', [
        'multipart' => [['name' => 'document', 'contents' => '%PDF-1.4', 'filename' => 'a.pdf']],
    ]);
    $this->assertRedirectsToLogin('GET', '/admin/users/1/documents/1/download');
    $this->assertRedirectsToLogin('POST', '/admin/users/1/documents/1', ['form_params' => ['_method' => 'DELETE']]);
}
```

**Aceitação**: veja o [§7.3](#73-aceitação-codeception--selenium).

### ✅ Checklist do cenário 1
- [ ] `schema.sql` (DROP + CREATE com FK) e depois `db:reset` + `db:populate`
- [ ] `ImageValidator` com `$mustBeImage`
- [ ] `UserDocument` (model "N") e `User::documents()` (model "1")
- [ ] `UserDocuments` (serviço) e `UserDocumentsController`
- [ ] 3 rotas no grupo `admin`
- [ ] `UsersController::show()` passando `documentList` e `documentValidator`
- [ ] Card na `admin/users/show.phtml`
- [ ] Testes: fixture `validPdf`, serviço, model, acesso e aceitação

---

## 3. Cenário 2 — Novas regras de upload na galeria

### 3.1 Limite de N fotos por quadra

`app/Services/CourtGallery.php`:
```php
public const MAX_IMAGES = 5;
```
No início do `add()` (linha 55), **depois** da checagem de "Galeria inexistente" e **antes** do `validate()`:
```php
if (CourtImage::count(['court_id' => $this->court->id]) >= self::MAX_IMAGES) {
    $this->errors[] = 'A galeria já possui o limite de ' . self::MAX_IMAGES . ' fotos. Remova uma antes de enviar outra.';
    return null;
}
```
- `Model::count()` faz `SELECT COUNT(*)` direto no banco.
- O controller já mostra `firstError()` embaixo do campo de arquivo, então não precisa mexer.
- **Teste:** um loop de 5 `add(UploadFixtures::validPng())`, e o 6º retorna `null` com a mensagem, deixando `CourtImage::count([...])` em 5.

### 3.2 Legenda obrigatória

1. **Model** (`CourtImage::validates()`, linha 50): segunda camada, vale para qualquer origem.
   ```php
   Validations::notEmpty('caption', $this);
   Validations::maxLength('caption', $this, 255);
   ```
2. **Erro embaixo do campo da legenda.** No `CourtImagesController::create()`, antes do `$gallery->add(...)`:
   ```php
   if (trim($caption) === '') {
       http_response_code(422);
       $title = 'Galeria da Quadra: ' . $court->name;
       $images = $gallery->images();
       $validator = $gallery->validator();
       $captionError = 'Informe a legenda da foto.';
       $this->render('admin/courts/show', compact('title', 'court', 'gallery', 'images', 'validator', 'caption', 'captionError'));
       return;
   }
   ```
3. **View** (`admin/courts/show.phtml`): o mesmo padrão do `$uploadError`, aplicado ao input `#court_image_caption`.
   ```php
   <?php $captionError = $captionError ?? null; ?>
   <input type="text" class="form-control<?= $captionError !== null ? ' is-invalid' : '' ?>" id="court_image_caption" ...>
   <?php if ($captionError !== null) : ?>
       <div class="invalid-feedback" id="error_caption"><?= htmlspecialchars($captionError) ?></div>
   <?php endif; ?>
   ```
4. **Testes que quebram:** os `add(UploadFixtures::validPng())` **sem legenda** no `CourtGalleryTest`, o `uploadImage($page, 'quadra_foto.jpg')` sem legenda no `CourtGalleryCest` e o `createImageWithFile()` do controller test. Passe uma legenda em todos.

### 3.3 Dimensão mínima (ex.: 300×200 px)

`ImageValidator`: dois parâmetros opcionais no construtor (padrão 0 = sem limite, e o avatar não muda):
```php
private int $minWidth = 0,
private int $minHeight = 0
```
No passo 7, logo depois do `getimagesize`:
```php
if ($imageInfo[0] < $this->minWidth || $imageInfo[1] < $this->minHeight) {
    return $this->fail(
        "A imagem deve ter no mínimo {$this->minWidth}x{$this->minHeight} px (enviada: {$imageInfo[0]}x{$imageInfo[1]})."
    );
}
```
Só na galeria (`CourtGallery::__construct`):
```php
$this->validator = $validator ?? new ImageValidator(minWidth: 300, minHeight: 200);
```
- **Teste:** `UploadFixtures::validPng()` gera 4×4 px e deve ser recusado; `UploadFixtures::fileEntry(UploadFixtures::createFile(UploadFixtures::pngContent(400, 300)), 'ok.png')` passa.
- A fixture de aceitação `quadra_foto.jpg` tem 2048×1152, então continua passando.

### 3.4 Outro formato / outro tamanho

| Pedido | Onde mexer |
|---|---|
| "Aceite GIF" | `ImageValidator::ALLOWED_TYPES`: `'image/gif' => ['gif']`. O teste `test_rejects_format_not_allowed` (usa GIF) precisa mudar para outro formato. |
| "Limite de 5MB" | `ImageValidator::MAX_SIZE`. O `php/uploads.ini` (40M) e o `client_max_body_size 40M` do Nginx já comportam. Acima de 40MB, mexa nos dois e reinicie os containers. |
| "Mostre o limite na tela" | Já aparece: as views usam `maxSizeLabel()` e `allowedLabel()` do próprio validador. |

---

## 4. Cenário 3 — Trocar a ação referencial (RESTRICT / SET NULL)

**Pedido:** "Não deixe excluir uma quadra que tem fotos." É o conceito "Cascade Delete, SET NULL e RESTRICT" da rubrica, na prática.

### 4.1 RESTRICT

1. **Banco** (`schema.sql`, linha 62): troque `ON DELETE CASCADE` por `ON DELETE RESTRICT`. Depois rode `./run db:reset && ./run db:populate`.
2. **Model** (`Court::destroy()`): o MySQL agora lança `PDOException` com código **1451**.
   ```php
   public function destroy(): bool
   {
       try {
           $deleted = parent::destroy();
       } catch (\PDOException $e) {
           if (($e->errorInfo[1] ?? null) === 1451) { // RESTRICT: existem linhas filhas
               $this->addError('id', 'a quadra possui fotos; remova-as antes de excluir.');
               return false;
           }
           throw $e;
       }

       // Com RESTRICT só chega aqui se a galeria estava vazia; a pasta pode ser apagada
       if ($deleted) {
           $this->gallery()->destroyFiles();
       }

       return $deleted;
   }
   ```
3. **Controller** (`Admin\CourtsController::destroy()`, linha 86): verifique o retorno.
   ```php
   $totalImages = $court->imagesCount();
   if (!$court->destroy()) {
       FlashMessage::danger(
           'Não é possível excluir a quadra ' . $court->name . ': ' . $totalImages
           . ' foto(s) vinculada(s) (ON DELETE RESTRICT). Remova as fotos primeiro.'
       );
       $this->redirectTo(route('admin.courts.index'));
       return;
   }
   ```
4. **Testes que mudam de expectativa:**
   - `CourtImageTest::test_database_cascade_deletes_images_with_court`: agora espera `PDOException` / 1451.
   - `CourtGalleryTest::test_destroying_court_removes_images_from_database_and_filesystem`: `destroy()` retorna `false`, imagens e pasta continuam.
   - `CourtGalleryControllersTest::test_admin_destroy_court_removes_images_from_db_and_filesystem`: mensagem de erro, nada apagado.
   - `CourtGalleryCest::exclusaoDaQuadraRemoveGaleria`: espera a mensagem de RESTRICT. Acrescente outro cenário que remove as fotos e depois exclui a quadra.

**Demonstração direta no banco** (`./run db:console`):
```sql
DELETE FROM courts WHERE id = 1;   -- com fotos
-- ERROR 1451 (23000): Cannot delete or update a parent row: a foreign key constraint fails
```

### 4.2 SET NULL (se pedirem para "manter as fotos sem quadra")
- **Coluna:** `court_id INT NULL`. O `SET NULL` exige coluna anulável, senão o `CREATE TABLE` falha.
- **FK:** `ON DELETE SET NULL`.
- **Model:** tire o `notEmpty('court_id')` e mantenha o `foreignKeyExists`, que já ignora valor vazio.
- **Arquivos:** o `Court::destroy()` **não** pode apagar a pasta, porque as fotos continuam existindo como órfãs. Elas precisariam ser movidas para uma pasta "acervo", senão o `relativePath()` aponta para `courts//arquivo`.
- **Recomendação:** explique o SET NULL e demonstre só no banco. Implementar exige decidir o que fazer com os arquivos.

---

## 5. Cenário 4 — Editar legenda e definir foto de capa

### 5.1 Editar legenda (PUT)

**Rota:**
```php
Route::put('/admin/courts/{court_id}/images/{id}', [AdminCourtImagesController::class, 'update'])
    ->name('admin.courts.images.update');
```

**Controller** (`CourtImagesController`):
```php
public function update(Request $request): void
{
    $courtId = $request->getParam('court_id');
    $court = is_numeric($courtId) ? Court::findById((int) $courtId) : null;
    $imageId = $request->getParam('id');
    /** @var CourtImage|null $image */
    $image = ($court !== null && is_numeric($imageId)) ? $court->images()->findById((int) $imageId) : null;

    if ($court === null || $image === null) {
        FlashMessage::danger('Imagem não encontrada nesta galeria.');
        $this->redirectTo(route('admin.courts.index'));
        return;
    }

    $caption = trim((string) $request->getParam('caption', ''));
    // update() grava só a coluna; o arquivo no disco não muda
    $image->update(['caption' => $caption === '' ? null : mb_substr($caption, 0, 255)]);

    FlashMessage::success('Legenda atualizada.');
    $this->redirectTo(route('admin.courts.show', ['id' => $court->id]));
}
```

**View** (dentro do card de cada imagem em `admin/courts/show.phtml`):
```php
<form action="/admin/courts/<?= $court->id ?>/images/<?= $image->id ?>" method="POST" class="input-group input-group-sm mb-2">
    <input type="hidden" name="_method" value="PUT">
    <input type="text" name="caption" class="form-control" maxlength="255"
           value="<?= htmlspecialchars($image->caption ?? '') ?>" id="caption_image_<?= $image->id ?>">
    <button class="btn btn-outline-secondary" id="btn_update_caption_<?= $image->id ?>">Salvar</button>
</form>
```

### 5.2 Definir foto de capa

1. **Banco:** `is_cover TINYINT(1) NOT NULL DEFAULT 0` em `court_images`, e `'is_cover'` no `$columns` do `CourtImage`.
2. **Rota:** `Route::post('/admin/courts/{court_id}/images/{id}/cover', [AdminCourtImagesController::class, 'cover'])`.
3. **Controller:** encontre a imagem como no `update()` e então:
   ```php
   // Só uma capa por quadra: zera as outras e marca esta
   foreach ($court->images()->get() as $other) {
       $other->update(['is_cover' => (int) ($other->id === $image->id)]);
   }
   ```
4. **`Court::coverImage()`:** prefira a marcada.
   ```php
   public function coverImage(): ?CourtImage
   {
       $images = $this->gallery()->images();
       foreach ($images as $image) {
           if ((int) $image->is_cover === 1) {
               return $image;
           }
       }
       return $images[0] ?? null;
   }
   ```
5. **View:** botão "Definir como capa" em cada card e um selo "CAPA" na imagem marcada.

---

## 6. Cenário 5 — Upload múltiplo

**Pegadinha:** com `<input type="file" name="images[]" multiple>`, o `$_FILES` **não** vem como lista de arquivos. Vem "por atributo":
```
$_FILES['images'] = [
  'name'     => ['a.jpg', 'b.png'],
  'tmp_name' => ['/tmp/php1', '/tmp/php2'],
  'error'    => [0, 0], ...
]
```

**1. `core/Http/Request.php`: normalizar para lista**
```php
/**
 * <input name="campo[]" multiple> → lista de entradas no formato de um arquivo único.
 *
 * @return array<int, array<string, mixed>>
 */
public function getFiles(string $key): array
{
    $files = $this->files[$key] ?? null;
    if (!is_array($files) || !is_array($files['name'] ?? null)) {
        return [];
    }

    $list = [];
    foreach (array_keys($files['name']) as $i) {
        if ((int) $files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $list[] = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];
    }

    return $list;
}
```

**2. Controller (`CourtImagesController::create()`)**
```php
$files = $request->getFiles('images');
if ($files === []) {
    // renderize com $uploadError = 'Selecione ao menos uma imagem.' (mesmo bloco do erro atual)
}

$sent = 0;
$errors = [];
foreach ($files as $file) {
    if ($gallery->add($file, $caption) !== null) {
        $sent++;
    } else {
        $errors[] = $file['name'] . ': ' . $gallery->firstError();   // um arquivo inválido não cancela os outros
    }
}
```
Depois, mostre `"$sent imagem(ns) enviada(s)"` e, se houver `$errors`, renderize com `$uploadError = implode(' | ', $errors)`.

**3. View:** `name="images[]"` e o atributo `multiple` no input.

**4. Teste de controller:** passe o formato "por atributo" em `$files`:
```php
$a = UploadFixtures::validPng('a.png');
$b = UploadFixtures::validJpeg('b.jpg');
$files = ['images' => [
    'name' => [$a['name'], $b['name']], 'type' => [$a['type'], $b['type']],
    'tmp_name' => [$a['tmp_name'], $b['tmp_name']], 'error' => [0, 0], 'size' => [$a['size'], $b['size']],
]];
```
> ⚠️ No teste de controller o `move_uploaded_file` **falha**, porque não é um upload HTTP real. O controller usa o `FileStorage` padrão, então teste o **caminho feliz** pelo serviço (com `TestFileStorage`) ou pela aceitação, e no controller teste só os erros.

---

## 7. Testes para qualquer alteração

### 7.1 Unitário (serviço / model)
- **Herdar de `Tests\TestCase`:** o banco de teste é recriado a cada teste.
- **Arquivos:** `UploadFixtures::validPng()`, `validJpeg()`, `oversized()`, `phpScript('x.php.jpg')`, `polyglotPng()`, `gif()`, `fileEntry($tmp, $nome, $erro)`.
- **`TestFileStorage`:** passe no construtor do serviço. Ele troca o `move_uploaded_file` por `copy`; o resto roda igual à produção.
- **Limpeza:** `UploadFixtures::cleanup()` no `tearDown()`, antes do `parent::tearDown()`.
- **Falha real do banco:** `DatabaseFailure::on('tabela', 'DELETE')` cria um trigger que faz o MySQL recusar a operação. Bom para provar a ordem "banco primeiro, arquivo depois".

### 7.2 Controller (`tests/Integration/Controllers/...`)
Herde de `ControllerTestCase`. O `post()` aceita `$files` como quarto argumento:
```php
$response = $this->post('create', 'App\Controllers\Admin\UserDocumentsController',
    ['user_id' => $this->student->id, 'title' => ''],
    ['document' => UploadFixtures::validPdf()]
);
$this->assertStringContainsString('id="error_document_title">Informe o título do documento.', $response);
```
Erro de validação → **não** há `Location:` na resposta (renderiza com 422). Sucesso → `Location: ...` + `$_SESSION['flash']['success']`.

### 7.3 Aceitação (Codeception + Selenium)
- **Herdar de `BaseAcceptanceCest`:** a cada cenário, o banco `arena_test` é recriado e populado pelo `Seeder`, e os uploads de teste são limpos. Pode rodar quantas vezes quiser, sem `db:reset`.
- **Arquivos enviados** ficam em `tests/Support/Data/`. Para PDF, gere no `_before` (como o `imagem_grande.jpg` do `CourtGalleryCest`):
  ```php
  public function _before(AcceptanceTester $page): void
  {
      parent::_before($page);
      file_put_contents(codecept_data_dir() . 'atestado.pdf', "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n");
  }
  ```
- **Fluxo:**
  ```php
  $page->amOnPage('/admin/users/2');
  $page->fillField('title', 'Atestado médico');
  $page->attachFile('#document_file', 'atestado.pdf');
  $page->click('#btn_upload_document');
  $page->see('enviado com sucesso');
  $page->see('Atestado médico', '#documents_list');
  ```
- **Conferir o disco:** `codecept_root_dir() . 'public/assets/uploads/test/...'` com `clearstatcache(true, $path)` antes de `assertFileDoesNotExist`, porque quem apaga é o PHP-FPM, outro processo.
- **Botão no rodapé da página não clica** ("element click intercepted"): siga o `href` com `grabAttributeFrom` + `amOnPage`, como no `visualizacaoDeImagem`.

### 7.4 Comandos
```bash
./run test tests/Unit/Services/UserDocumentsTest.php        # um arquivo
docker compose run --rm php_test ./vendor/bin/phpunit --filter Document tests
./run test:browser tests/Acceptance/Admin/CourtGalleryCest.php
./run test && ./run test:browser && ./run phpcs && ./run phpstan
```

---

## 8. Roteiro de execução em sala

1. **Repita o pedido em camadas**, em voz alta: "tabela com FK → model N com `belongsTo` → `hasMany` no model 1 → serviço com validador e storage → controller + rotas → view com `multipart` → testes".
2. `git checkout -b validacao-arquivos`
3. **Banco:** `schema.sql` → `./run db:reset && ./run db:populate`.
4. **Models:** `$columns` (pegadinha nº 1), `validates()` com `notEmpty` + `foreignKeyExists`, e as relações.
5. **Serviço:** copie o `CourtGallery` como molde: validar → gravar arquivo → salvar no banco → se falhar, apagar o arquivo.
6. **Rotas** no grupo certo (`admin`), com `{user_id}`/`{court_id}`, e **controller**: erro → `render` com 422; sucesso → flash + `redirectTo` + `return`.
7. **View:** `enctype="multipart/form-data"` (sem isso o `$_FILES` chega vazio!), `novalidate`, `is-invalid` + `invalid-feedback`.
8. **Testar no navegador:** caminho feliz, arquivo inválido, arquivo malicioso, remoção, e conferir o disco com `ls public/assets/uploads/...`.
9. **Testes** (§7), `php -l`, e `./run phpcs` se der tempo.
10. **Explicar com o vocabulário da rubrica:** FK, integridade referencial, CASCADE/RESTRICT/SET NULL, `multipart/form-data`, MIME type real × declarado, BelongsTo/HasMany.

---

## 9. Pegadinhas e erros comuns

| Sintoma | Causa | Solução |
|---|---|---|
| `$request->getFile('x')` é `null` | o form não tem `enctype="multipart/form-data"`, ou o `name` do input difere | conferir o `enctype` e o `name` ↔ `getFile('name')` |
| "Nenhum arquivo foi enviado" com arquivo grande | passou do `post_max_size` do PHP (40M): o PHP descarta **todo** o corpo | ajustar `php/uploads.ini` + `client_max_body_size` e reiniciar os containers |
| `Property X not found in App\Models\...` | coluna fora do `$columns` | adicionar no `$columns` |
| `Unknown column` / `Table doesn't exist` | mudou o `schema.sql` sem recriar o banco | `./run db:reset && ./run db:populate` |
| `mkdir(): Permission denied` no Linux | o PHP-FPM (`www-data`) não pode gravar na pasta | `sudo chown -R www-data:www-data public/assets/uploads` |
| Link `.pdf` dá 404 | o Nginx só serve `css/js/imagens` como estático | usar rota de download (§2) ou incluir `pdf` no regex do `nginx.conf` |
| PDF recusado como "não é uma imagem válida" | o passo 7 (`getimagesize`) do validador | parâmetro `$mustBeImage = false` (§2, passo 2) |
| `{userId}` não vira parâmetro | o regex de rota só aceita `[a-z_]` | usar `{user_id}` |
| Imagem de outra quadra apagada pela URL | buscou com `CourtImage::findById($id)` | usar `$court->images()->findById($id)` (filtra pelo pai) |
| Arquivo órfão no disco / registro sem arquivo | ordem errada das operações | upload: arquivo → banco (falhou? apaga o arquivo). Remoção: banco → arquivo |
| Acentos/espaços quebrando o download | `filename="..."` sem codificação | `filename*=UTF-8''` + `rawurlencode()` |
| Teste de controller falha no caminho feliz | `move_uploaded_file` só funciona com upload HTTP real | caminho feliz no teste do serviço (`TestFileStorage`) ou na aceitação |
| `assertFileDoesNotExist` falha mas o arquivo sumiu | *stat cache* do PHP | `clearstatcache(true, $path)` antes |
| Mensagem flash mostrando `&amp;` | escapou duas vezes | o `_flash_message.phtml` já escapa: não use `htmlspecialchars` na mensagem |

## 10. Comandos rápidos

| Comando | Para quê |
|---|---|
| `./run up -d` / `./run ps` | subir / ver containers |
| `./run db:reset && ./run db:populate` | recriar banco + seed (e limpar uploads) |
| `./run db:console` | MySQL (`SHOW CREATE TABLE court_images\G`, `SELECT * FROM court_images;`) |
| `ls -la public/assets/uploads/courts/1` | mostrar o arquivo no filesystem |
| `./run test` · `./run test caminho/Arquivo.php` | PHPUnit |
| `./run test:browser` | aceitação (ambiente isolado, `arena_test`) |
| `./run phpcs` / `./run phpstan` | qualidade |

**Credenciais do seed:** `admin@arena.com / admin123` · `aluno@arena.com / aluno123` · `bloqueado@arena.com / aluno123`
**IDs do seed:** usuários 1 (admin), 2 (aluno), 3 (bloqueado), 4–7 · quadras 1 (Central), 2, 3 (manutenção).
