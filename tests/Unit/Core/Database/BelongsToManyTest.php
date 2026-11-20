<?php

namespace Tests\Unit\Core\Database;

use App\Models\TrainingClass;
use App\Models\User;
use Core\Database\ActiveRecord\BelongsToMany;
use Tests\TestCase;

/**
 * Testa a classe do framework core/Database/ActiveRecord/BelongsToMany.php
 * usando a relação real Turma <-> Aluno (pivô class_enrollments).
 */
class BelongsToManyTest extends TestCase
{
    private TrainingClass $first;
    private TrainingClass $second;
    private User $student;
    private User $other;

    public function setUp(): void
    {
        parent::setUp();

        $this->first = new TrainingClass(['name' => 'Turma A', 'schedule' => 'Segunda 19h']);
        $this->first->save();
        $this->second = new TrainingClass(['name' => 'Turma B', 'schedule' => 'Terça 19h']);
        $this->second->save();

        $this->student = $this->createStudent('Aluno 1', 'aluno1@arena.com');
        $this->other = $this->createStudent('Aluno 2', 'aluno2@arena.com');
    }

    private function createStudent(string $name, string $email): User
    {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);
        $user->save();

        return $user;
    }

    public function test_get_returns_empty_array_without_relations(): void
    {
        $relation = new BelongsToMany($this->first, User::class, 'class_enrollments', 'training_class_id', 'user_id');

        $this->assertSame([], $relation->get());
        $this->assertEquals(0, $relation->count());
    }

    public function test_get_returns_only_related_models_through_pivot(): void
    {
        $this->first->enroll($this->student);
        $this->second->enroll($this->other);

        $relation = new BelongsToMany($this->first, User::class, 'class_enrollments', 'training_class_id', 'user_id');
        $users = $relation->get();

        $this->assertCount(1, $users);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertEquals($this->student->id, $users[0]->id);
        $this->assertEquals('aluno1@arena.com', $users[0]->email);
        $this->assertEquals(1, $relation->count());
    }

    public function test_inverse_direction_uses_swapped_keys(): void
    {
        $this->first->enroll($this->student);
        $this->second->enroll($this->student);

        $relation = new BelongsToMany($this->student, TrainingClass::class, 'class_enrollments', 'user_id', 'training_class_id');
        $classes = $relation->get();

        $this->assertCount(2, $classes);
        $this->assertContainsOnlyInstancesOf(TrainingClass::class, $classes);
        $this->assertEqualsCanonicalizing(['Turma A', 'Turma B'], [$classes[0]['name'], $classes[1]['name']]);
        $this->assertEquals(2, $relation->count());
    }

    public function test_magic_property_calls_get(): void
    {
        $this->first->enroll($this->student);
        $this->first->enroll($this->other);

        // $model->students chama students()->get() automaticamente (Model::__get)
        $this->assertCount(2, $this->first->students);
        $this->assertCount(1, $this->other->trainingClasses);
    }
}
