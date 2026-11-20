<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

/**
 * Matrícula: registro da tabela pivô class_enrollments, que liga uma turma a um aluno
 * (relação NxN TrainingClass <-> User).
 *
 * @property int $id
 * @property int $training_class_id
 * @property int $user_id
 * @property ?string $created_at
 * @property TrainingClass $trainingClass
 * @property User $user
 */
class Enrollment extends Model
{
    protected static string $table = 'class_enrollments';

    /** @var array<int, string> */
    protected static array $columns = [
        'training_class_id',
        'user_id',
        'created_at'
    ];

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);

        if ($this->created_at === null) {
            $this->created_at = date('Y-m-d H:i:s');
        }
    }

    public function validates(): void
    {
        Validations::notEmpty('training_class_id', $this);
        Validations::notEmpty('user_id', $this);

        // Integridade referencial na aplicação: os dois lados da relação precisam existir
        $classExists = Validations::foreignKeyExists('training_class_id', $this, TrainingClass::class);
        $userExists = Validations::foreignKeyExists('user_id', $this, User::class);

        if ($this->hasErrors() || !$classExists || !$userExists) {
            return;
        }

        // Mesma regra do UNIQUE KEY (training_class_id, user_id) do banco, com mensagem amigável
        if (!Validations::uniqueness(['training_class_id', 'user_id'], $this)) {
            $this->errors = ['user_id' => 'o aluno já está matriculado nesta turma!'];
            return;
        }

        $user = User::findById((int) $this->user_id);
        if ($user !== null && !$user->isStudent()) {
            $this->addError('user_id', 'apenas alunos podem ser matriculados em turmas!');
        }

        $trainingClass = TrainingClass::findById((int) $this->training_class_id);
        if ($this->newRecord() && $trainingClass !== null && $trainingClass->isFull()) {
            $this->addError('training_class_id', 'a turma está lotada!');
        }
    }

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'training_class_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
