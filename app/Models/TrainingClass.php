<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsToMany;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
use Lib\Validations;
use PDO;

/**
 * Turma de treino de Beach Tennis.
 *
 * Relação NxN com User (alunos) através da tabela pivô class_enrollments:
 *  - uma turma possui N alunos  -> TrainingClass::students()
 *  - um aluno participa de N turmas -> User::trainingClasses()
 *
 * @property int $id
 * @property string $name
 * @property string $level
 * @property string $schedule
 * @property int $capacity
 * @property ?string $created_at
 * @property array<User> $students
 * @property array<Enrollment> $enrollments
 */
class TrainingClass extends Model
{
    public const LEVEL_BEGINNER = 'beginner';
    public const LEVEL_INTERMEDIATE = 'intermediate';
    public const LEVEL_ADVANCED = 'advanced';

    /** Opções do formulário (valor => rótulo) */
    public const LEVEL_OPTIONS = [
        self::LEVEL_BEGINNER => 'Iniciante',
        self::LEVEL_INTERMEDIATE => 'Intermediário',
        self::LEVEL_ADVANCED => 'Avançado',
    ];

    public const DEFAULT_CAPACITY = 8;
    public const MAX_CAPACITY = 30;

    protected static string $table = 'training_classes';

    /** @var array<int, string> */
    protected static array $columns = [
        'name',
        'level',
        'schedule',
        'capacity',
        'created_at'
    ];

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);

        if ($this->level === null) {
            $this->level = self::LEVEL_BEGINNER;
        }

        if ($this->capacity === null) {
            $this->capacity = self::DEFAULT_CAPACITY;
        }
    }

    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::maxLength('name', $this, 100);
        Validations::uniqueness('name', $this);

        Validations::notEmpty('schedule', $this);
        Validations::maxLength('schedule', $this, 100);

        Validations::inclusion('level', $this, array_keys(self::LEVEL_OPTIONS));

        // Vem do formulário como texto: precisa ser um inteiro dentro do limite
        $capacity = filter_var((string) $this->capacity, FILTER_VALIDATE_INT);
        if ($capacity === false || $capacity < 1 || $capacity > self::MAX_CAPACITY) {
            $this->addError('capacity', 'deve ser um número entre 1 e ' . self::MAX_CAPACITY . '!');
        }
    }

    /* ------------------- RELAÇÃO NxN ------------------- */

    /**
     * Alunos matriculados na turma (users JOIN class_enrollments).
     */
    public function students(): BelongsToMany
    {
        return $this->BelongsToMany(User::class, 'class_enrollments', 'training_class_id', 'user_id');
    }

    /**
     * Registros da tabela pivô desta turma (útil para contar dependências).
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'training_class_id');
    }

    public function studentsCount(): int
    {
        return $this->newRecord() ? 0 : (int) $this->students()->count();
    }

    public function isFull(): bool
    {
        return $this->studentsCount() >= (int) $this->capacity;
    }

    public function hasStudent(User $user): bool
    {
        return Enrollment::exists(['training_class_id' => $this->id, 'user_id' => $user->id]);
    }

    /**
     * Registra a relação (INSERT na tabela pivô).
     * Retorna a matrícula; se ela tiver erros (hasErrors()), nada foi gravado.
     */
    public function enroll(User $user): Enrollment
    {
        $enrollment = new Enrollment([
            'training_class_id' => $this->id,
            'user_id' => $user->id,
        ]);
        $enrollment->save();

        return $enrollment;
    }

    /**
     * Remove a relação (DELETE na tabela pivô). Turma e aluno continuam existindo.
     */
    public function unenroll(User $user): bool
    {
        $enrollment = Enrollment::findBy(['training_class_id' => $this->id, 'user_id' => $user->id]);

        return $enrollment !== null && $enrollment->destroy();
    }

    /**
     * Alunos que ainda NÃO estão na turma (opções do select de matrícula), em ordem alfabética.
     *
     * @return array<User>
     */
    public function availableStudents(): array
    {
        $attributes = implode(', ', array_map(fn($column) => 'u.' . $column, User::columns()));

        $sql = <<<SQL
            SELECT u.id, {$attributes}
            FROM users u
            WHERE u.role = :role
              AND NOT EXISTS (
                  SELECT 1 FROM class_enrollments e
                  WHERE e.user_id = u.id AND e.training_class_id = :class_id
              )
            ORDER BY u.name ASC
        SQL;

        $stmt = Database::getDatabaseConn()->prepare($sql);
        $stmt->bindValue(':role', User::ROLE_STUDENT);
        $stmt->bindValue(':class_id', $this->id, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn($row) => new User($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function levelLabel(): string
    {
        return self::LEVEL_OPTIONS[$this->level] ?? (string) $this->level;
    }

    /**
     * Turmas ordenadas por nome.
     *
     * @return array<TrainingClass>
     */
    public static function allOrdered(): array
    {
        $attributes = implode(', ', static::$columns);
        $table = static::$table;

        $stmt = Database::getDatabaseConn()->query("SELECT id, {$attributes} FROM {$table} ORDER BY name ASC");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return array_map(fn($row) => new static($row), $rows);
    }

    /**
     * Tentativa de remoção de registro com dependências:
     * a turma só é excluída se não houver alunos matriculados. A aplicação verifica antes
     * e mostra uma mensagem amigável; o banco garante a mesma regra com ON DELETE RESTRICT.
     */
    public function destroy(): bool
    {
        $this->errors = [];

        $total = $this->studentsCount();
        if ($total > 0) {
            $this->addError(
                'base',
                'Não é possível excluir a turma ' . $this->name . ': ela possui ' . $total
                . ' aluno(s) matriculado(s). Remova as matrículas antes de excluir.'
            );
            return false;
        }

        return parent::destroy();
    }
}
