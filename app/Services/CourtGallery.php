<?php

namespace App\Services;

use App\Models\Court;
use App\Models\CourtImage;

/**
 * Galeria de fotos de uma quadra (relação 1xN: Court hasMany CourtImage).
 *
 * - Arquivos físicos: public/assets/uploads/courts/{court_id}/court_<hash>.<ext>
 * - Banco de dados: uma linha em court_images por foto (metadados + file_name)
 */
class CourtGallery
{
    private ImageValidator $validator;
    private FileStorage $storage;

    /** @var array<int, string> */
    private array $errors = [];

    public function __construct(
        private Court $court,
        ?ImageValidator $validator = null,
        ?FileStorage $storage = null
    ) {
        $this->validator = $validator ?? new ImageValidator();
        $this->storage = $storage ?? new FileStorage();
    }

    public function directory(): string
    {
        return 'courts/' . $this->court->id;
    }

    /**
     * @return array<CourtImage>
     */
    public function images(): array
    {
        if ($this->court->id === null) {
            return [];
        }

        /** @var array<CourtImage> $images */
        $images = $this->court->images()->get();
        return $images;
    }

    /**
     * Valida o arquivo, grava no filesystem e registra a imagem no banco.
     *
     * @param array<string, mixed>|null $file entrada de $_FILES['image']
     */
    public function add(?array $file, ?string $caption = null): ?CourtImage
    {
        $this->errors = [];

        // Integridade referencial: não existe galeria sem a entidade principal
        if ($this->court->id === null || !Court::exists(['id' => $this->court->id])) {
            $this->errors[] = 'Galeria inexistente: a quadra informada não existe.';
            return null;
        }

        if (!$this->validator->validate($file)) {
            $this->errors = $this->validator->errors();
            return null;
        }

        $fileName = FileStorage::randomName('court', (string) $this->validator->safeExtension());
        $relativePath = $this->storage->store((string) $file['tmp_name'], $this->directory(), $fileName);

        $caption = $caption !== null ? trim($caption) : null;

        /** @var CourtImage $image */
        $image = $this->court->images()->new([
            'file_name' => $fileName,
            'original_name' => mb_substr(basename((string) $file['name']), 0, 255),
            'mime_type' => $this->validator->mimeType(),
            'size' => (int) filesize($this->storage->absolutePath($relativePath)),
            'caption' => ($caption === null || $caption === '') ? null : mb_substr($caption, 0, 255),
        ]);

        try {
            $saved = $image->save();
        } catch (\PDOException $e) {
            // Ex: FK violada (quadra removida entre a checagem e o INSERT)
            $saved = false;
            $image->addError('court_id', 'violação de integridade referencial (' . $e->getCode() . ')');
        }

        if (!$saved) {
            // Mantém filesystem e banco consistentes: sem registro, sem arquivo
            $this->storage->delete($relativePath);
            $this->errors[] = 'Não foi possível registrar a imagem: ' . ($image->firstError() ?? 'dados inválidos');
            return null;
        }

        return $image;
    }

    /**
     * Remove uma imagem da galeria (arquivo do filesystem + registro do banco).
     */
    public function remove(CourtImage $image): bool
    {
        $this->errors = [];

        if ((int) $image->court_id !== (int) $this->court->id) {
            $this->errors[] = 'A imagem não pertence a esta galeria.';
            return false;
        }

        $this->storage->delete($image->relativePath());
        return $image->destroy();
    }

    /**
     * Apaga a pasta inteira da galeria no filesystem.
     * Chamado por Court::destroy() — as linhas do banco são removidas pelo ON DELETE CASCADE.
     */
    public function destroyFiles(): void
    {
        if ($this->court->id !== null) {
            $this->storage->deleteDirectory($this->directory());
        }
    }

    public function url(CourtImage $image): string
    {
        return $this->storage->url($image->relativePath());
    }

    public function fileExists(CourtImage $image): bool
    {
        return $this->storage->exists($image->relativePath());
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
}
