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
     * Valida e grava uma nova foto de perfil, substituindo (e apagando do disco) a anterior.
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
        $this->storage->store((string) $file['tmp_name'], $this->directory(), $fileName);

        // Remove o arquivo antigo do filesystem antes de apontar para o novo
        $this->removeFile();
        $this->user->update(['avatar_name' => $fileName]);

        return true;
    }

    /**
     * Remove a foto de perfil (filesystem + referência no banco).
     */
    public function remove(): bool
    {
        if ($this->relativePath() === null) {
            $this->errors = ['Nenhuma foto de perfil cadastrada.'];
            return false;
        }

        $this->removeFile();
        $this->user->update(['avatar_name' => null]);

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

    private function removeFile(): void
    {
        $relativePath = $this->relativePath();
        if ($relativePath !== null) {
            $this->storage->delete($relativePath);
        }
    }
}
