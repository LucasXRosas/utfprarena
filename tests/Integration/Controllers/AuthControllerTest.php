<?php

namespace Tests\Integration\Controllers;

use App\Models\User;
use Lib\Authentication\Auth;

class AuthControllerTest extends ControllerTestCase
{
    private User $admin;
    private User $student;
    private User $blocked;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin = new User([
            'name' => 'Admin Controller Test',
            'email' => 'admin.ctrl@arena.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => User::ROLE_MANAGER,
            'status' => User::STATUS_ACTIVE
        ]);
        $this->admin->save();

        $this->student = new User([
            'name' => 'Student Controller Test',
            'email' => 'student.ctrl@arena.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => User::ROLE_STUDENT,
            'status' => User::STATUS_ACTIVE
        ]);
        $this->student->save();

        $this->blocked = new User([
            'name' => 'Blocked Controller Test',
            'email' => 'blocked.ctrl@arena.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => User::ROLE_STUDENT,
            'status' => User::STATUS_BLOCKED
        ]);
        $this->blocked->save();
    }

    public function test_new_renders_login_page_when_not_authenticated(): void
    {
        $response = $this->get(
            action: 'new',
            controllerName: 'App\Controllers\AuthController'
        );

        $this->assertMatchesRegularExpression('/Autenticação de Acesso/', $response);
    }

    public function test_create_fails_when_parameters_are_absent(): void
    {
        // Envio de requisição sem parâmetros obrigatórios (email e senha ausentes)
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\AuthController',
            params: []
        );

        // Não redireciona: renderiza o login com a mensagem embaixo de cada campo
        $this->assertStringNotContainsString('Location:', $response);
        $this->assertStringContainsString('id="error_user_email">Informe o seu e-mail.', $response);
        $this->assertStringContainsString('id="error_user_password">Informe a sua senha.', $response);
        $this->assertFalse(Auth::check());
    }

    public function test_login_form_disables_browser_validation(): void
    {
        $response = $this->get(
            action: 'new',
            controllerName: 'App\Controllers\AuthController'
        );

        $this->assertStringContainsString('id="login_form" novalidate', $response);
        $this->assertStringNotContainsString('required', $response);
    }

    public function test_create_fails_with_invalid_email_format_and_keeps_typed_value(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\AuthController',
            params: ['user' => ['email' => 'aluno-sem-arroba', 'password' => 'secret123']]
        );

        $this->assertStringContainsString('id="error_user_email">Informe um e-mail válido', $response);
        $this->assertStringContainsString('value="aluno-sem-arroba"', $response);
        $this->assertStringNotContainsString('id="error_user_password"', $response);
    }

    public function test_create_fails_when_parameters_are_empty(): void
    {
        // Envio de requisição com parâmetros presentes, porém com valores vazios
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\AuthController',
            params: [
                'user' => [
                    'email' => '   ',
                    'password' => ''
                ]
            ]
        );

        // Espaços em branco no e-mail contam como vazio
        $this->assertStringNotContainsString('Location:', $response);
        $this->assertStringContainsString('id="error_user_email">Informe o seu e-mail.', $response);
        $this->assertStringContainsString('id="error_user_password">Informe a sua senha.', $response);
    }

    public function test_create_fails_with_invalid_credentials(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\AuthController',
            params: [
                'user' => [
                    'email' => 'admin.ctrl@arena.com',
                    'password' => 'wrongpassword'
                ]
            ]
        );

        // Mensagem genérica (não revela se o e-mail existe) e os dois campos destacados
        $this->assertStringNotContainsString('Location:', $response);
        $this->assertStringContainsString('E-mail ou senha inválidos.', $response);
        $this->assertStringContainsString('value="admin.ctrl@arena.com"', $response);
        $this->assertStringContainsString('class="form-control is-invalid"', $response);
        $this->assertFalse(Auth::check());
    }

    public function test_create_fails_when_user_is_blocked(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\AuthController',
            params: [
                'user' => [
                    'email' => 'blocked.ctrl@arena.com',
                    'password' => 'secret123'
                ]
            ]
        );

        $this->assertStringNotContainsString('Location:', $response);
        $this->assertStringContainsString('Acesso bloqueado: usuário possui pendências financeiras', $response);
        $this->assertFalse(Auth::check());
    }

    public function test_create_succeeds_and_redirects_admin_to_admin_index(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\AuthController',
            params: [
                'user' => [
                    'email' => 'admin.ctrl@arena.com',
                    'password' => 'secret123'
                ]
            ]
        );

        $this->assertMatchesRegularExpression('/Location: \/admin/', $response);
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->admin->id, Auth::user()->id);
    }

    public function test_create_succeeds_and_redirects_student_to_dashboard(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\AuthController',
            params: [
                'user' => [
                    'email' => 'student.ctrl@arena.com',
                    'password' => 'secret123'
                ]
            ]
        );

        $this->assertMatchesRegularExpression('/Location: \/dashboard/', $response);
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->student->id, Auth::user()->id);
    }

    public function test_destroy_logs_out_user(): void
    {
        Auth::login($this->student);
        $this->assertTrue(Auth::check());

        $response = $this->post(
            action: 'destroy',
            controllerName: 'App\Controllers\AuthController'
        );

        $this->assertMatchesRegularExpression('/Location: \/login/', $response);
        $this->assertFalse(Auth::check());
    }
}
