<?php

namespace Tests\Integration\Controllers;

use App\Models\Enrollment;
use App\Models\TrainingClass;
use App\Models\User;
use Lib\Authentication\Auth;

/**
 * Testes de controller da relação NxN Turma <-> Aluno:
 * Admin\TrainingClassesController, Admin\EnrollmentsController (Ajax/JSON) e a visão do aluno no Dashboard.
 */
class TrainingClassesControllersTest extends ControllerTestCase
{
    private TrainingClass $trainingClass;
    private User $student;

    public function setUp(): void
    {
        parent::setUp();
        unset($_SESSION['flash']);

        $admin = new User([
            'name' => 'Admin Turmas',
            'email' => 'admin.turmas@arena.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => User::ROLE_MANAGER,
        ]);
        $admin->save();
        Auth::login($admin);

        $this->trainingClass = new TrainingClass([
            'name' => 'Turma Controller Test',
            'schedule' => 'Terça 19h',
            'capacity' => 5,
        ]);
        $this->trainingClass->save();

        $this->student = new User([
            'name' => 'Aluna Ajax',
            'email' => 'aluna.ajax@arena.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);
        $this->student->save();
    }

    public function tearDown(): void
    {
        unset($_SESSION['flash']);
        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $response): array
    {
        $data = json_decode($response, true);
        $this->assertIsArray($data, 'A resposta deveria ser JSON: ' . $response);

        return $data;
    }

    /* ---------------- CRUD de turmas ---------------- */

    public function test_index_lists_classes_with_students_count(): void
    {
        $this->trainingClass->enroll($this->student);

        $response = $this->get('index', 'App\Controllers\Admin\TrainingClassesController');

        $this->assertStringContainsString('Turmas de Treino', $response);
        $this->assertStringContainsString('Turma Controller Test', $response);
        $this->assertMatchesRegularExpression('/bi-people"><\/i> 1\/5/', $response);
    }

    public function test_create_class(): void
    {
        $response = $this->post('create', 'App\Controllers\Admin\TrainingClassesController', [
            'training_class' => [
                'name' => 'Turma Kids',
                'level' => TrainingClass::LEVEL_BEGINNER,
                'schedule' => 'Sábado 10h',
                'capacity' => '10',
            ],
        ]);

        $created = TrainingClass::findBy(['name' => 'Turma Kids']);
        $this->assertNotNull($created);
        $this->assertStringContainsString('Location: /admin/classes/' . $created->id, $response);
    }

    public function test_create_class_with_errors_shows_field_messages(): void
    {
        $response = $this->post('create', 'App\Controllers\Admin\TrainingClassesController', [
            'training_class' => ['name' => '', 'level' => 'beginner', 'schedule' => '', 'capacity' => '99'],
        ]);

        $this->assertStringNotContainsString('Location:', $response);
        $this->assertStringContainsString('id="new_class_form" novalidate', $response);
        $this->assertStringContainsString('id="error_class_name">O nome da turma não pode ser vazio!', $response);
        $this->assertStringContainsString(
            'id="error_class_capacity">O número de vagas deve ser um número entre 1 e 30!',
            $response
        );
    }

    public function test_show_lists_enrolled_and_available_students(): void
    {
        $other = new User([
            'name' => 'Aluno Disponivel',
            'email' => 'disponivel@arena.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);
        $other->save();
        $this->trainingClass->enroll($this->student);

        $response = $this->get('show', 'App\Controllers\Admin\TrainingClassesController', [
            'id' => $this->trainingClass->id,
        ]);

        $this->assertStringContainsString('Turma: Turma Controller Test', $response);
        $this->assertStringContainsString('id="enrolled_student_' . $this->student->id . '"', $response);
        $this->assertStringContainsString('<option value="' . $other->id . '">', $response);
        $this->assertStringNotContainsString('<option value="' . $this->student->id . '">', $response);
        $this->assertStringContainsString('/assets/js/enrollments.js', $response);
    }

    public function test_destroy_class_with_students_is_blocked(): void
    {
        $this->trainingClass->enroll($this->student);

        $response = $this->post('destroy', 'App\Controllers\Admin\TrainingClassesController', [
            'id' => $this->trainingClass->id,
        ]);

        $this->assertStringContainsString('Location: /admin/classes', $response);
        $this->assertNotNull(TrainingClass::findById($this->trainingClass->id));
        $this->assertStringContainsString('Não é possível excluir a turma', $_SESSION['flash']['danger'] ?? '');
    }

    public function test_destroy_class_without_students(): void
    {
        $this->post('destroy', 'App\Controllers\Admin\TrainingClassesController', ['id' => $this->trainingClass->id]);

        $this->assertNull(TrainingClass::findById($this->trainingClass->id));
        $this->assertStringContainsString('excluída com sucesso', $_SESSION['flash']['success'] ?? '');
    }

    /* ---------------- Matrículas via Ajax (JSON) ---------------- */

    public function test_enroll_via_ajax_returns_json(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $data = $this->decode($this->post('create', 'App\Controllers\Admin\EnrollmentsController', [
            'class_id' => $this->trainingClass->id,
            'user_id' => $this->student->id,
        ]));

        $this->assertTrue($data['success']);
        $this->assertStringContainsString('matriculado com sucesso', $data['message']);
        $this->assertEquals($this->student->id, $data['student']['id']);
        $this->assertEquals('Aluna Ajax', $data['student']['name']);
        $this->assertEquals(1, $data['training_class']['students_count']);
        $this->assertFalse($data['training_class']['is_full']);
        $this->assertSame([], $data['available_students']);
        $this->assertTrue($this->trainingClass->hasStudent($this->student));
    }

    public function test_enroll_twice_via_ajax_returns_error_json(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $this->trainingClass->enroll($this->student);

        $data = $this->decode($this->post('create', 'App\Controllers\Admin\EnrollmentsController', [
            'class_id' => $this->trainingClass->id,
            'user_id' => $this->student->id,
        ]));

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('já está matriculado', $data['message']);
        $this->assertArrayHasKey('user_id', $data['errors']);
        $this->assertEquals(1, Enrollment::count());
    }

    public function test_enroll_without_student_returns_error_json(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $data = $this->decode($this->post('create', 'App\Controllers\Admin\EnrollmentsController', [
            'class_id' => $this->trainingClass->id,
            'user_id' => '',
        ]));

        $this->assertFalse($data['success']);
        $this->assertEquals('Selecione um aluno válido para matricular.', $data['message']);
    }

    public function test_enroll_in_nonexistent_class_returns_error_json(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $data = $this->decode($this->post('create', 'App\Controllers\Admin\EnrollmentsController', [
            'class_id' => 9999,
            'user_id' => $this->student->id,
        ]));

        $this->assertFalse($data['success']);
        $this->assertEquals('Turma não encontrada.', $data['message']);
    }

    public function test_unenroll_via_ajax_returns_json(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $this->trainingClass->enroll($this->student);

        $data = $this->decode($this->post('destroy', 'App\Controllers\Admin\EnrollmentsController', [
            'class_id' => $this->trainingClass->id,
            'user_id' => $this->student->id,
        ]));

        $this->assertTrue($data['success']);
        $this->assertStringContainsString('removido da turma', $data['message']);
        $this->assertEquals(0, $data['training_class']['students_count']);
        $this->assertEquals($this->student->id, $data['available_students'][0]['id']);
        $this->assertFalse($this->trainingClass->hasStudent($this->student));
        $this->assertNotNull(User::findById($this->student->id));
    }

    public function test_unenroll_student_not_in_class_returns_error_json(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $data = $this->decode($this->post('destroy', 'App\Controllers\Admin\EnrollmentsController', [
            'class_id' => $this->trainingClass->id,
            'user_id' => $this->student->id,
        ]));

        $this->assertFalse($data['success']);
        $this->assertEquals('O aluno não está matriculado nesta turma.', $data['message']);
    }

    /* ---------------- Sem JavaScript: redirect + flash ---------------- */

    public function test_enroll_without_ajax_redirects_with_flash(): void
    {
        $response = $this->post('create', 'App\Controllers\Admin\EnrollmentsController', [
            'class_id' => $this->trainingClass->id,
            'user_id' => $this->student->id,
        ]);

        $this->assertStringContainsString('Location: /admin/classes/' . $this->trainingClass->id, $response);
        $this->assertStringContainsString('matriculado com sucesso', $_SESSION['flash']['success'] ?? '');
        $this->assertTrue($this->trainingClass->hasStudent($this->student));
    }

    /* ---------------- Visualização pelo lado do aluno ---------------- */

    public function test_dashboard_shows_student_classes(): void
    {
        $this->trainingClass->enroll($this->student);
        Auth::login($this->student);

        $response = $this->get('index', 'App\Controllers\DashboardController');

        $this->assertStringContainsString('Minhas Turmas', $response);
        $this->assertStringContainsString('id="my_class_' . $this->trainingClass->id . '"', $response);
        $this->assertStringContainsString('Turma Controller Test', $response);
    }

    public function test_admin_user_page_shows_athlete_classes(): void
    {
        $this->trainingClass->enroll($this->student);

        $response = $this->get('show', 'App\Controllers\Admin\UsersController', ['id' => $this->student->id]);

        $this->assertStringContainsString('Turmas do Atleta', $response);
        $this->assertStringContainsString('href="/admin/classes/' . $this->trainingClass->id . '"', $response);
    }
}
