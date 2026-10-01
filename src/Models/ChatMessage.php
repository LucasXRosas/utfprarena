<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

/**
 * Modelo OO de Mensagem de Chat.
 */
class ChatMessage
{
    public function __construct(
        private ?int $id,
        private string $sender,
        private string $message,
        private ?DateTimeImmutable $sentAt = null
    ) {
        $this->sentAt = $sentAt ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSentAt(): DateTimeImmutable
    {
        return $this->sentAt ?? new DateTimeImmutable();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sender' => $this->sender,
            'message' => $this->message,
            'sent_at' => $this->sentAt?->format('Y-m-d H:i:s'),
        ];
    }
}
