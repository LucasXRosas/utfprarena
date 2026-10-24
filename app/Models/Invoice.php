<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
use Lib\Validations;
use PDO;

/**
 * @property int $id
 * @property int $user_id
 * @property float|string $amount
 * @property string $due_date
 * @property string $status
 * @property ?string $created_at
 * @property ?User $user
 */
class Invoice extends Model
{
    public const STATUS_PAID = 'paid';
    public const STATUS_PENDING = 'pending';
    public const STATUS_OVERDUE = 'overdue';

    protected static string $table = 'invoices';

    /** @var array<int, string> */
    protected static array $columns = [
        'user_id',
        'amount',
        'due_date',
        'status',
        'created_at'
    ];

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);

        if ($this->status === null) {
            $this->status = self::STATUS_PENDING;
        }
    }

    public function validates(): void
    {
        Validations::notEmpty('user_id', $this);
        Validations::notEmpty('amount', $this);
        Validations::notEmpty('due_date', $this);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE;
    }

    /**
     * Retorna todas as faturas de um usuário ordenadas pela data de vencimento decrescente.
     *
     * @param int $userId
     * @return array<Invoice>
     */
    public static function allByUser(int $userId): array
    {
        $pdo = Database::getDatabaseConn();
        $table = static::$table;
        $attributes = implode(', ', static::$columns);

        $sql = "SELECT id, {$attributes} FROM {$table} WHERE user_id = :uid ORDER BY due_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $invoices = [];
        foreach ($rows as $row) {
            $invoices[] = new static($row);
        }

        return $invoices;
    }
}
