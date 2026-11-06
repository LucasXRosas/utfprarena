<?php

namespace App\Models;

use App\Services\ProfileAvatar;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

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

    /** Opções dos formulários (valor => rótulo): fonte única para views e validação */
    public const ROLE_OPTIONS = [
        self::ROLE_STUDENT => 'Aluno / Atleta (Beach Tennis)',
        self::ROLE_MANAGER => 'Administrador / Gestor',
    ];

    /** Opções dos formulários (valor => rótulo) */
    public const STATUS_OPTIONS = [
        self::STATUS_ACTIVE => 'Ativo (Acesso Liberado)',
        self::STATUS_BLOCKED => 'Bloqueado (Inadimplência / Pendência)',
    ];

    public const PASSWORD_MIN_LENGTH = 6;
    public const CPF_REGEX = '/^\d{3}\.\d{3}\.\d{3}-\d{2}$/';

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

    /** Indica que uma nova senha foi informada para um usuário já existente (edição) */
    private bool $passwordChanged = false;

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
        Validations::maxLength('name', $this, 255);

        Validations::notEmpty('email', $this);
        Validations::email('email', $this);
        Validations::maxLength('email', $this, 191);
        Validations::uniqueness('email', $this);

        Validations::maxLength('phone', $this, 20);

        if ($this->cpf !== null && $this->cpf !== '') {
            Validations::format('cpf', $this, self::CPF_REGEX, 'deve estar no formato 000.000.000-00!');
            Validations::uniqueness('cpf', $this);
        }

        Validations::inclusion('role', $this, array_keys(self::ROLE_OPTIONS));
        Validations::inclusion('status', $this, [self::STATUS_ACTIVE, self::STATUS_BLOCKED, self::STATUS_INACTIVE]);

        // Senha: obrigatória no cadastro; na edição só é validada quando uma nova for informada
        if ($this->newRecord() || $this->passwordChanged) {
            Validations::notEmpty('password', $this);
            Validations::minLength('password', $this, self::PASSWORD_MIN_LENGTH);
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

    public function changePassword(string $newPassword): void
    {
        $this->encrypted_password = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    /**
     * Define uma nova senha para um usuário existente, aplicando as mesmas regras do cadastro
     * (obrigatória, tamanho mínimo e confirmação). Só é persistida se o save() for válido.
     */
    public function updatePassword(string $password, string $confirmation): void
    {
        $this->password = $password;
        $this->password_confirmation = $confirmation;
        $this->passwordChanged = true;
        $this->changePassword($password);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'user_id');
    }

    /**
     * @return array<Invoice>
     */
    public function getInvoices(): array
    {
        if ($this->id === null) {
            return [];
        }

        return Invoice::allByUser($this->id);
    }

    /**
     * Foto de perfil do usuário (arquivo em uploads/users/{id}, nome em users.avatar_name).
     */
    public function avatar(): ProfileAvatar
    {
        return new ProfileAvatar($this);
    }

    /**
     * Ao excluir o usuário, apaga também sua pasta de uploads (avatar) do filesystem.
     * As faturas são removidas pelo ON DELETE CASCADE de invoices.user_id.
     */
    public function destroy(): bool
    {
        $avatar = $this->avatar();
        $deleted = parent::destroy();

        if ($deleted) {
            $avatar->removeAll();
        }

        return $deleted;
    }

    public static function countTotal(): int
    {
        return self::count();
    }

    public static function countActive(): int
    {
        return self::count(['status' => self::STATUS_ACTIVE]);
    }

    public static function countBlocked(): int
    {
        return self::count(['status' => self::STATUS_BLOCKED]);
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
