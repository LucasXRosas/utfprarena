<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

/**
 * Entidade Invoice com calculo da regra de inadimplencia da arena.
 */
class Invoice
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELED = 'canceled';

    public function __construct(
        private ?int $id,
        private int $userId,
        private float $amount,
        private DateTimeImmutable $dueDate,
        private ?DateTimeImmutable $paidAt = null,
        private string $status = self::STATUS_PENDING
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isPaid(): bool
    {
        return $this->paidAt !== null || $this->status === self::STATUS_PAID;
    }

    public function isOverdue(?DateTimeImmutable $currentDate = null): bool
    {
        if ($this->isPaid() || $this->status === self::STATUS_CANCELED) {
            return false;
        }

        $now = $currentDate ?? new DateTimeImmutable('today');
        return $this->dueDate < $now;
    }

    public function markAsPaid(?DateTimeImmutable $paidAt = null): void
    {
        $this->paidAt = $paidAt ?? new DateTimeImmutable();
        $this->status = self::STATUS_PAID;
    }

    /**
     * Motor de regras de acesso da Arena:
     * - Se houver 2 ou mais faturas vencidas -> BLOQUEADO
     * - Se houver menos de 2 faturas vencidas (0 ou 1) -> ATIVO
     *
     * @param Invoice[] $invoices
     * @param DateTimeImmutable|null $referenceDate
     * @return string Status resultante (User::STATUS_ACTIVE ou User::STATUS_BLOCKED)
     */
    public static function evaluateAccessStatus(array $invoices, ?DateTimeImmutable $referenceDate = null): string
    {
        $overdueCount = 0;
        foreach ($invoices as $invoice) {
            if ($invoice->isOverdue($referenceDate)) {
                $overdueCount++;
            }
        }

        return $overdueCount >= 2 ? User::STATUS_BLOCKED : User::STATUS_ACTIVE;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'amount' => $this->amount,
            'due_date' => $this->dueDate->format('Y-m-d'),
            'paid_at' => $this->paidAt?->format('Y-m-d H:i:s'),
            'status' => $this->status,
        ];
    }
}
