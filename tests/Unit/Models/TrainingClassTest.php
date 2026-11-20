<?php

namespace Tests\Unit\Models;

use App\Models\Enrollment;
use App\Models\TrainingClass;
use App\Models\User;
use Core\Database\ActiveRecord\BelongsToMany;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\Database;
use Tests\TestCase;

class TrainingClassTest extends TestCase
{
    private TrainingClass $trainingClass;
    private User $ana;
    private User $bruno;

    public function setUp(): void
    {
        parent::setUp();

        $this->trainingClass = new TrainingClass([
            'name' => 'Iniciante - Terça 19h',
            'level' => TrainingClass::LEVEL_BEGINNER,
            'schedule' => 'Terça 19h',
            'capacity' => 2,
        ]);
        $this->trainingClass->save();

        $this->ana = $this->createStudent('Ana', 'ana@arena.com');
        $this->bruno = $this->createStudent('Bruno', 'bruno@arena.com');
    }

    private function createStudent(string $name, string $email, string $role = User::ROLE_STUDENT): User
    {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => $role,
        ]);
        $user->save();

        return $user;
    }

    /* ---------------- Validações ---------------- */

    public function test_should_create_class_with_defaults(): void
    {
        $trainingClass = new TrainingClass(['name' => 'Kids', 'schedule' => 'Sábado 10h']);

        $this->assertTrue($trainingClass->save());
        $this->assertEquals(TrainingClass::LEVEL_BEGINNER, $trainingClass->level);
        $this->assertEquals(TrainingClass::DEFAULT_CAPACITY, $trainingClass->capacity);
        $this->assertEquals('Iniciante', $trainingClass->levelLabel());
    }

    public function test_validates_required_fields(): void
    {
        $trainingClass = new TrainingClass(['name' => '', 'schedule' => '']);

        $this->assertFalse($trainingClass->save());
        $this->assertEquals('não pode ser vazio!', $trainingClass->errors('name'));
        $this->assertEquals('não pode ser vazio!', $trainingClass->errors('schedule'));
    }

    public function test_validates_level_capacity_and_unique_name(): void
    {
        $trainingClass = new TrainingClass([
            'name' => 'Iniciante - Terça 19h',
            'schedule' => 'Quarta 18h',
            'level' => 'profissional',
            'capacity' => 0,
        ]);

        $this->assertFalse($trainingClass->save());
        $this->assertEquals('já existe um registro com esse dado', $trainingClass->errors('name'));
        $this->assertEquals('possui um valor inválido!', $trainingClass->errors('level'));
        $this->assertEquals('deve ser um número entre 1 e 30!', $trainingClass->errors('capacity'));
    }

    /* ---------------- Relação NxN ---------------- */

    public function test_students_returns_belongs_to_many_relation(): void
    {
        $this->assertInstanceOf(BelongsToMany::class, $this->trainingClass->students());
        $this->assertInstanceOf(HasMany::class, $this->trainingClass->enrollments());
        $this->assertInstanceOf(BelongsToMany::class, $this->ana->trainingClasses());
    }

    public function test_enroll_registers_the_relation_in_both_directions(): void
    {
        $enrollment = $this->trainingClass->enroll($this->ana);

        $this->assertFalse($enrollment->hasErrors());
        $this->assertGreaterThan(0, $enrollment->id);

        // Lado da turma: N alunos
        $students = $this->trainingClass->students;
        $this->assertCount(1, $students);
        $this->assertContainsOnlyInstancesOf(User::class, $students);
        $this->assertEquals('Ana', $students[0]->name);

        // Lado do aluno: N turmas
        $classes = $this->ana->trainingClasses;
        $this->assertCount(1, $classes);
        $this->assertEquals($this->trainingClass->id, $classes[0]->id);

        $this->assertTrue($this->trainingClass->hasStudent($this->ana));
        $this->assertFalse($this->trainingClass->hasStudent($this->bruno));
        $this->assertEquals(1, $this->trainingClass->studentsCount());
    }

    public function test_student_can_be_in_many_classes(): void
    {
        $other = new TrainingClass(['name' => 'Avançado', 'schedule' => 'Sábado 9h']);
        $other->save();

        $this->trainingClass->enroll($this->ana);
        $other->enroll($this->ana);

        $this->assertCount(2, $this->ana->trainingClasses);
        $this->assertEquals(2, $this->ana->trainingClasses()->count());
    }

    public function test_enroll_same_student_twice_is_rejected(): void
    {
        $this->trainingClass->enroll($this->ana);
        $duplicate = $this->trainingClass->enroll($this->ana);

        $this->assertTrue($duplicate->hasErrors());
        $this->assertEquals('o aluno já está matriculado nesta turma!', $duplicate->errors('user_id'));
        $this->assertEquals(1, Enrollment::count());
    }

    public function test_enroll_is_rejected_when_class_is_full(): void
    {
        $this->trainingClass->enroll($this->ana);
        $this->trainingClass->enroll($this->bruno);
        $this->assertTrue($this->trainingClass->isFull());

        $carla = $this->createStudent('Carla', 'carla@arena.com');
        $enrollment = $this->trainingClass->enroll($carla);

        $this->assertEquals('a turma está lotada!', $enrollment->errors('training_class_id'));
        $this->assertEquals(2, $this->trainingClass->studentsCount());
    }

    public function test_only_students_can_be_enrolled(): void
    {
        $manager = $this->createStudent('Gestor', 'gestor@arena.com', User::ROLE_MANAGER);

        $enrollment = $this->trainingClass->enroll($manager);

        $this->assertEquals('apenas alunos podem ser matriculados em turmas!', $enrollment->errors('user_id'));
    }

    public function test_unenroll_removes_only_the_relation(): void
    {
        $this->trainingClass->enroll($this->ana);

        $this->assertTrue($this->trainingClass->unenroll($this->ana));

        $this->assertCount(0, $this->trainingClass->students);
        $this->assertNotNull(User::findById($this->ana->id));
        $this->assertNotNull(TrainingClass::findById($this->trainingClass->id));
        $this->assertFalse($this->trainingClass->unenroll($this->ana));
    }

    public function test_available_students_excludes_enrolled_and_managers(): void
    {
        $this->createStudent('Gestor', 'gestor@arena.com', User::ROLE_MANAGER);
        $this->trainingClass->enroll($this->ana);

        $available = $this->trainingClass->availableStudents();

        $this->assertCount(1, $available);
        $this->assertEquals('Bruno', $available[0]->name);
    }

    public function test_all_ordered_sorts_by_name(): void
    {
        $other = new TrainingClass(['name' => 'Avançado', 'schedule' => 'Sábado 9h']);
        $other->save();

        $names = array_map(fn($c) => $c->name, TrainingClass::allOrdered());

        $this->assertEquals(['Avançado', 'Iniciante - Terça 19h'], $names);
    }

    /* ---------------- Remoção com dependências ---------------- */

    public function test_destroy_class_with_students_is_blocked(): void
    {
        $this->trainingClass->enroll($this->ana);

        $this->assertFalse($this->trainingClass->destroy());
        $this->assertStringContainsString('possui 1 aluno(s) matriculado(s)', (string) $this->trainingClass->firstError());
        $this->assertNotNull(TrainingClass::findById($this->trainingClass->id));
    }

    public function test_destroy_class_without_students(): void
    {
        $this->assertTrue($this->trainingClass->destroy());
        $this->assertNull(TrainingClass::findById($this->trainingClass->id));
    }

    /* ---------------- Integridade no banco ---------------- */

    public function test_database_unique_index_rejects_duplicate_enrollment(): void
    {
        $this->trainingClass->enroll($this->ana);

        // Ignorando a validação do model, o UNIQUE KEY (training_class_id, user_id) barra o INSERT
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessageMatches('/Duplicate entry/');

        Database::getDatabaseConn()->exec(
            'INSERT INTO class_enrollments (training_class_id, user_id) VALUES ('
            . (int) $this->trainingClass->id . ', ' . (int) $this->ana->id . ')'
        );
    }

    public function test_database_restrict_blocks_deleting_class_with_students(): void
    {
        $this->trainingClass->enroll($this->ana);

        // Ignorando o model, o ON DELETE RESTRICT da FK barra o DELETE da turma
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessageMatches('/foreign key constraint fails/');

        Database::getDatabaseConn()->exec('DELETE FROM training_classes WHERE id = ' . (int) $this->trainingClass->id);
    }

    public function test_database_cascade_removes_enrollments_when_user_is_deleted(): void
    {
        $this->trainingClass->enroll($this->ana);

        $this->ana->destroy();

        $this->assertEquals(0, Enrollment::count());
        $this->assertCount(0, $this->trainingClass->students);
    }
}
