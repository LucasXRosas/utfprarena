<?php

namespace App\Models;

use Lib\Validations;
use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $encrypted_password
 * @property ?string $avatar_name
 * @property string $role
 * @property string $status
 * @property ?string $phone
 * @property ?string $cpf
 * @property ?string $remember_token
 * @property ?string $last_login_at
 * @property ?string $email_verified_at
 * @property ?string $password_reset_token
 * @property ?string $password_reset_expires_at
 */
class User extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_INACTIVE = 'inactive';

    public const ROLE_STUDENT = 'student';
    public const ROLE_MANAGER = 'manager';

    protected static string $table = 'users';
    protected static array $columns = [
        'name',
        'email',
        'encrypted_password',
        'avatar_name',
        'role',
        'status',
        'phone',
        'cpf',
        'remember_token',
        'last_login_at',
        'email_verified_at',
        'password_reset_token',
        'password_reset_expires_at'
    ];

    protected ?string $password = null;
    protected ?string $password_confirmation = null;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);

        if ($this->role === null) {
            $this->role = self::ROLE_STUDENT;
        }

        if ($this->status === null) {
            $this->status = self::STATUS_ACTIVE;
        }
    }

    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::notEmpty('email', $this);

        Validations::uniqueness('email', $this);

        if ($this->newRecord()) {
            Validations::passwordConfirmation($this);
        }
    }

    public function authenticate(string $password): bool
    {
        if ($this->encrypted_password === null || $this->encrypted_password === '') {
            return false;
        }

        return password_verify($password, $this->encrypted_password);
    }

    public static function findByEmail(string $email): User | null
    {
        return User::findBy(['email' => $email]);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    public function block(): void
    {
        $this->status = self::STATUS_BLOCKED;
    }

    public function activate(): void
    {
        $this->status = self::STATUS_ACTIVE;
    }

    public function __set(string $property, mixed $value): void
    {
        parent::__set($property, $value);

        if (
            $property === 'password' &&
            $this->newRecord() &&
            $value !== null && $value !== ''
        ) {
            $this->encrypted_password = password_hash((string) $value, PASSWORD_DEFAULT);
        }
    }
}
