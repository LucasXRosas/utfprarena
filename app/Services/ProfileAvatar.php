<?php

namespace App\Services;

use App\Models\User;

/**
 * Gerencia a foto de perfil (avatar) de um usuário.
 *
 * - Arquivo físico: public/assets/uploads/users/{user_id}/avatar_<hash>.<ext>
 * - Banco de dados: apenas o nome do arquivo na coluna users.avatar_name
 */
class ProfileAvatar
{
    public const DEFAULT_AVATAR = '/assets/images/defaults/avatar.png';

    private ImageValidator $validator;
    private FileStorage $storage;

    /** @var array<int, string> */
    private array $errors = [];

    public function __construct(
        private User $user,
        ?ImageValidator $validator = null,
        ?FileStorage $storage = null
    ) {
        $this->validator = $validator ?? new ImageValidator();
        $this->storage = $storage ?? new FileStorage();
    }

    /**
     * URL pública do avatar (ou a imagem padrão quando o usuário não possui foto).
     */
    public function path(): string
    {
        $relativePath = $this->relativePath();
        if ($relativePath === null || !$this->storage->exists($relativePath)) {
            return self::DEFAULT_AVATAR;
        }

        return $this->storage->url($relativePath);
    }

    public function hasAvatar(): bool
    {
        $relativePath = $this->relativePath();
        return $relativePath !== null && $this->storage->exists($relativePath);
    }

    public function relativePath(): ?string
    {
        if ($this->user->avatar_name === null || $this->user->avatar_name === '') {
            return null;
        }

        return $this->directory() . '/' . $this->user->avatar_name;
    }

    public function absolutePath(): ?string
    {
        $relativePath = $this->relativePath();
        return $relativePath === null ? null : $this->storage->absolutePath($relativePath);
    }

    public function directory(): string
    {
        return 'users/' . $this->user->id;
    }

    /**
     * Valida e grava uma nova foto de perfil, substituindo a anterior.
     *
     * Ordem pensada para manter banco e filesystem consistentes:
     *  1. grava o arquivo novo;
     *  2. atualiza users.avatar_name;
     *  3. só então apaga o arquivo anterior.
     * Se o passo 2 falhar, o arquivo novo é descartado e o anterior continua valendo.
     *
     * @param array<string, mixed>|null $file entrada de $_FILES['avatar']
     */
    public function update(?array $file): bool
    {
        $this->errors = [];

        if ($this->user->id === null) {
            $this->errors[] = 'O usuário precisa estar salvo antes de receber uma foto.';
            return false;
        }

        if (!$this->validator->validate($file)) {
            $this->errors = $this->validator->errors();
            return false;
        }

        $fileName = FileStorage::randomName('avatar', (string) $this->validator->safeExtension());

        try {
            $newRelativePath = $this->storage->store((string) $file['tmp_name'], $this->directory(), $fileName);
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        $previousName = $this->user->avatar_name;
        $previousRelativePath = $this->relativePath();

        if (!$this->persistAvatarName($fileName)) {
            // Banco não foi atualizado: descarta o arquivo novo e mantém o anterior intacto
            $this->storage->delete($newRelativePath);
            $this->user->avatar_name = $previousName;
            $this->errors[] = 'Não foi possível salvar a foto de perfil. A foto anterior foi mantida.';
            return false;
        }

        // Banco já aponta para o arquivo novo: agora é seguro apagar o anterior
        if ($previousRelativePath !== null) {
            $this->storage->delete($previousRelativePath);
        }

        return true;
    }

    /**
     * Remove a foto de perfil (referência no banco + filesystem).
     * O arquivo só é apagado depois que o banco deixa de apontar para ele.
     */
    public function remove(): bool
    {
        $this->errors = [];

        $relativePath = $this->relativePath();
        if ($relativePath === null) {
            $this->errors = ['Nenhuma foto de perfil cadastrada.'];
            return false;
        }

        $previousName = $this->user->avatar_name;

        if (!$this->persistAvatarName(null)) {
            $this->user->avatar_name = $previousName;
            $this->errors[] = 'Não foi possível remover a foto de perfil. Tente novamente.';
            return false;
        }

        $this->storage->delete($relativePath);

        return true;
    }

    /**
     * Apaga toda a pasta de uploads do usuário (usado quando o usuário é excluído).
     */
    public function removeAll(): void
    {
        if ($this->user->id !== null) {
            $this->storage->deleteDirectory($this->directory());
        }
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    public function validator(): ImageValidator
    {
        return $this->validator;
    }

    /**
     * Grava users.avatar_name e informa se o banco realmente foi atualizado.
     * Model::update() retorna false quando nenhuma linha é afetada (ex: usuário removido)
     * e pode lançar PDOException (ex: banco indisponível); os dois casos contam como falha.
     */
    private function persistAvatarName(?string $fileName): bool
    {
        try {
            return $this->user->update(['avatar_name' => $fileName]);
        } catch (\PDOException $e) {
            return false;
        }
    }
}
