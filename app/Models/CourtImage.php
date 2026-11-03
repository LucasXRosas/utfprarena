<?php

namespace App\Models;

use App\Services\FileStorage;
use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

/**
 * Foto da galeria de uma quadra (lado "N" da relação Court 1xN CourtImage).
 *
 * @property int $id
 * @property int $court_id
 * @property string $file_name
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property ?string $caption
 * @property ?string $created_at
 * @property Court $court
 */
class CourtImage extends Model
{
    protected static string $table = 'court_images';

    /** @var array<int, string> */
    protected static array $columns = [
        'court_id',
        'file_name',
        'original_name',
        'mime_type',
        'size',
        'caption',
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
        Validations::notEmpty('court_id', $this);
        Validations::notEmpty('file_name', $this);
        Validations::notEmpty('original_name', $this);
        Validations::notEmpty('mime_type', $this);

        // Integridade referencial também na aplicação: a quadra (galeria) precisa existir
        Validations::foreignKeyExists('court_id', $this, Court::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class, 'court_id');
    }

    /**
     * Caminho relativo à pasta de uploads: courts/{court_id}/{file_name}
     */
    public function relativePath(): string
    {
        return 'courts/' . $this->court_id . '/' . $this->file_name;
    }

    public function url(): string
    {
        return (new FileStorage())->url($this->relativePath());
    }

    public function sizeLabel(): string
    {
        $size = (int) $this->size;
        if ($size >= 1024 * 1024) {
            return number_format($size / 1024 / 1024, 2, ',', '.') . ' MB';
        }

        return number_format($size / 1024, 1, ',', '.') . ' KB';
    }
}
