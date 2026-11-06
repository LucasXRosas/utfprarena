<?php

namespace App\Models;

use App\Services\CourtGallery;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
use Lib\Validations;
use PDO;

/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $status
 * @property ?string $created_at
 * @property array<CourtImage> $images
 */
class Court extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_MAINTENANCE = 'maintenance';

    public const TYPE_BEACH_TENNIS = 'beach_tennis';

    protected static string $table = 'courts';

    /** @var array<int, string> */
    protected static array $columns = [
        'name',
        'type',
        'status',
        'created_at'
    ];

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);

        if ($this->type === null) {
            $this->type = self::TYPE_BEACH_TENNIS;
        }

        if ($this->status === null) {
            $this->status = self::STATUS_AVAILABLE;
        }
    }

    public function validates(): void
    {
        Validations::notEmpty('name', $this);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isMaintenance(): bool
    {
        return $this->status === self::STATUS_MAINTENANCE;
    }

    /**
     * Retorna todas as quadras ordenadas por ID.
     *
     * @param string $direction
     * @return array<Court>
     */
    public static function allOrdered(string $direction = 'ASC'): array
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $table = static::$table;
        $attributes = implode(', ', static::$columns);

        $sql = "SELECT id, {$attributes} FROM {$table} ORDER BY id {$direction};";

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $courts = [];
        foreach ($rows as $row) {
            $courts[] = new static($row);
        }

        return $courts;
    }

    /**
     * Sobrescreve all() para garantir ordenação por id ASC.
     *
     * @return array<Court>
     */
    public static function all(): array
    {
        return self::allOrdered('ASC');
    }

    /**
     * Retorna apenas as quadras disponíveis.
     *
     * @return array<Court>
     */
    public static function available(): array
    {
        return self::where(['status' => self::STATUS_AVAILABLE]);
    }

    /* ------------------- GALERIA DE FOTOS (RELAÇÃO 1xN) ------------------- */

    /**
     * Uma quadra possui N imagens (court_images.court_id -> courts.id).
     */
    public function images(): HasMany
    {
        return $this->hasMany(CourtImage::class, 'court_id');
    }

    public function gallery(): CourtGallery
    {
        return new CourtGallery($this);
    }

    /**
     * Primeira foto da galeria, usada como capa nos cards.
     */
    public function coverImage(): ?CourtImage
    {
        $images = $this->gallery()->images();
        return $images[0] ?? null;
    }

    public function imagesCount(): int
    {
        return count($this->gallery()->images());
    }

    /**
     * Exclui a quadra e toda a sua galeria.
     *  - Banco: as linhas de court_images são removidas pelo ON DELETE CASCADE da FK;
     *  - Filesystem: a pasta uploads/courts/{id} é apagada pela aplicação,
     *    pois o banco não conhece os arquivos físicos.
     */
    public function destroy(): bool
    {
        $gallery = $this->gallery();
        $deleted = parent::destroy();

        if ($deleted) {
            $gallery->destroyFiles();
        }

        return $deleted;
    }
}
