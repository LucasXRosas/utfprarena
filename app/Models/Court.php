<?php

namespace App\Models;

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
}
