<?php

namespace Tests\Unit\Models;

use App\Models\Enrollment;
use App\Models\TrainingClass;
use App\Models\User;
use Core\Database\ActiveRecord\BelongsTo;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    private TrainingClass $trainingClass;
    private User $student;

    public function setUp(): void
    {
        parent::setUp();

        $this->trainingClass = new TrainingClass(['name' => 'Intermediário', 'schedule' => 'Quarta 20h']);
        $this->trainingClass->save();

        $this->student = new User([
            'name' => 'Aluno Pivô',
            'email' => 'pivo@arena.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);
        $this->student->save();
    }

    public function test_should_create_enrollment_with_created_at(): void
    {
        $enrollment = new Enrollment([
            'training_class_id' => $this->trainingClass->id,
            'user_id' => $this->student->id,
        ]);

        $this->assertTrue($enrollment->save());
        $this->assertNotNull($enrollment->created_at);
        $this->assertEquals(1, Enrollment::count());
    }

    public function test_validates_required_fields(): void
    {
        $enrollment = new Enrollment();

        $this->assertFalse($enrollment->save());
        $this->assertEquals('não pode ser vazio!', $enrollment->errors('training_class_id'));
        $this->assertEquals('não pode ser vazio!', $enrollment->errors('user_id'));
    }

    public function test_validates_that_both_sides_exist(): void
    {
        $enrollment = new Enrollment(['training_class_id' => 9999, 'user_id' => 8888]);

        $this->assertFalse($enrollment->save());
        $this->assertEquals('registro relacionado inexistente!', $enrollment->errors('training_class_id'));
        $this->assertEquals('registro relacionado inexistente!', $enrollment->errors('user_id'));
    }

    public function test_belongs_to_class_and_user(): void
    {
        $enrollment = $this->trainingClass->enroll($this->student);

        $this->assertInstanceOf(BelongsTo::class, $enrollment->trainingClass());
        $this->assertInstanceOf(BelongsTo::class, $enrollment->user());
        $this->assertEquals('Intermediário', $enrollment->trainingClass->name);
        $this->assertEquals('Aluno Pivô', $enrollment->user->name);
    }

    public function test_class_has_many_enrollments(): void
    {
        $enrollment = $this->trainingClass->enroll($this->student);

        $enrollments = $this->trainingClass->enrollments;
        $this->assertCount(1, $enrollments);
        $this->assertEquals($enrollment->id, $enrollments[0]->id);
    }
}
