<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

/**
 * Modelo OO de Reclamacao / Problem Tracker.
 */
class Complaint
{
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';

    public function __construct(
        private ?int $id,
        private string $title,
        private string $description,
        private string $authorEmail,
        private string $status = self::STATUS_OPEN,
        private ?DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt ?? new DateTimeImmutable();
    }

    public function resolve(): void
    {
        $this->status = self::STATUS_RESOLVED;
    }

    public function startAnalysis(): void
    {
        $this->status = self::STATUS_IN_PROGRESS;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'author_email' => $this->authorEmail,
            'status' => $this->status,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }
}
