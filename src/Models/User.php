<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidade User do sistema UTFPR Arena.
 */
class User
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_INACTIVE = 'inactive';

    public const ROLE_STUDENT = 'student';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_MANAGER = 'manager';

    public function __construct(
        private ?int $id,
        private string $fullName,
        private string $cpf,
        private string $email,
        private string $password,
        private string $phone,
        private string $role = self::ROLE_STUDENT,
        private string $status = self::STATUS_ACTIVE
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getCpf(): string
    {
        return $this->cpf;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function block(): void
    {
        $this->status = self::STATUS_BLOCKED;
    }

    public function activate(): void
    {
        $this->status = self::STATUS_ACTIVE;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->fullName,
            'cpf' => $this->cpf,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
        ];
    }
}
