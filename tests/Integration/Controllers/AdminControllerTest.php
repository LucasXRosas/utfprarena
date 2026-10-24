<?php

namespace Tests\Integration\Controllers;

use App\Models\Court;
use App\Models\User;
use Lib\Authentication\Auth;

class AdminControllerTest extends ControllerTestCase
{
    private User $admin;
    private User $student;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin = new User([
            'name' => 'Admin Test',
            'email' => 'admin.panel@arena.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => User::ROLE_MANAGER,
            'status' => User::STATUS_ACTIVE
        ]);
        $this->admin->save();

        $this->student = new User([
            'name' => 'Student Test',
            'email' => 'student.panel@arena.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => User::ROLE_STUDENT,
            'status' => User::STATUS_ACTIVE
        ]);
        $this->student->save();

        Auth::login($this->admin);
    }

    public function test_index_renders_manager_panel_with_metrics(): void
    {
        $court = new Court(['name' => 'Quadra Admin Test']);
        $court->save();

        $response = $this->get(
            action: 'index',
            controllerName: 'App\Controllers\Admin\UsersController'
        );

        $this->assertMatchesRegularExpression('/Painel Gerencial/', $response);
        $this->assertMatchesRegularExpression('/Gestão de Atletas/', $response);
        $this->assertMatchesRegularExpression('/Student Test/', $response);
    }

    public function test_new_renders_creation_form(): void
    {
        $response = $this->get(
            action: 'new',
            controllerName: 'App\Controllers\Admin\UsersController'
        );

        $this->assertMatchesRegularExpression('/Cadastrar Novo Atleta/', $response);
        $this->assertMatchesRegularExpression('/Formulário de Registro de Usuário/', $response);
    }

    public function test_create_fails_with_missing_fields(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\Admin\UsersController',
            params: ['user' => ['name' => '']]
        );

        $this->assertMatchesRegularExpression('/Location: \/admin\/users\/new/', $response);
        $this->assertMatchesRegularExpression(
            '/Todos os campos obrigatórios/',
            $_SESSION['flash']['danger'] ?? ''
        );
    }

    public function test_create_fails_with_mismatched_password(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\Admin\UsersController',
            params: [
                'user' => [
                    'name' => 'Novo Atleta',
                    'email' => 'novo@arena.com',
                    'password' => '123456',
                    'password_confirmation' => '654321'
                ]
            ]
        );

        $this->assertMatchesRegularExpression('/Location: \/admin\/users\/new/', $response);
        $this->assertEquals(
            'As senhas devem ser idênticas!',
            $_SESSION['flash']['danger'] ?? null
        );
    }

    public function test_create_succeeds(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\Admin\UsersController',
            params: [
                'user' => [
                    'name' => 'Atleta Cadastrado',
                    'email' => 'atleta.cadastrado@arena.com',
                    'password' => '123456',
                    'password_confirmation' => '123456',
                    'phone' => '(41) 99999-1111',
                    'role' => 'student',
                    'status' => 'active'
                ]
            ]
        );

        $this->assertMatchesRegularExpression('/Location: \/admin\/users/', $response);
        $this->assertMatchesRegularExpression(
            '/cadastrado com sucesso!/',
            $_SESSION['flash']['success'] ?? ''
        );
        $this->assertNotNull(User::findByEmail('atleta.cadastrado@arena.com'));
    }

    public function test_show_renders_user_details(): void
    {
        $response = $this->get(
            action: 'show',
            controllerName: 'App\Controllers\Admin\UsersController',
            params: ['id' => $this->student->id]
        );

        $this->assertMatchesRegularExpression('/Credencial do Atleta/', $response);
        $this->assertMatchesRegularExpression('/Student Test/', $response);
    }

    public function test_edit_renders_edit_form(): void
    {
        $response = $this->get(
            action: 'edit',
            controllerName: 'App\Controllers\Admin\UsersController',
            params: ['id' => $this->student->id]
        );

        $this->assertMatchesRegularExpression('/Editar Atleta/', $response);
        $this->assertMatchesRegularExpression('/Student Test/', $response);
    }

    public function test_update_fails_with_empty_name(): void
    {
        $response = $this->put(
            action: 'update',
            controllerName: 'App\Controllers\Admin\UsersController',
            params: [
                'id' => $this->student->id,
                'user' => [
                    'name' => '',
                    'email' => $this->student->email
                ]
            ]
        );

        $this->assertMatchesRegularExpression('/Location: \/admin\/users\/' . $this->student->id . '\/edit/', $response);
        $this->assertEquals(
            'Nome e e-mail são obrigatórios.',
            $_SESSION['flash']['danger'] ?? null
        );
    }

    public function test_update_succeeds(): void
    {
        $response = $this->put(
            action: 'update',
            controllerName: 'App\Controllers\Admin\UsersController',
            params: [
                'id' => $this->student->id,
                'user' => [
                    'name' => 'Student Atualizado',
                    'email' => $this->student->email,
                    'status' => User::STATUS_BLOCKED
                ]
            ]
        );

        $this->assertMatchesRegularExpression('/Location: \/admin\/users/', $response);
        $this->assertMatchesRegularExpression(
            '/atualizado com sucesso!/',
            $_SESSION['flash']['success'] ?? ''
        );

        $updated = User::findById((int) $this->student->id);
        $this->assertNotNull($updated);
        $this->assertEquals('Student Atualizado', $updated->name);
        $this->assertTrue($updated->isBlocked());
    }

    public function test_destroy_prevents_deleting_self(): void
    {
        $response = $this->post(
            action: 'destroy',
            controllerName: 'App\Controllers\Admin\UsersController',
            params: ['id' => $this->admin->id]
        );

        $this->assertMatchesRegularExpression('/Location: \/admin\/users/', $response);
        $this->assertEquals(
            'Não é permitido remover o próprio administrador conectado.',
            $_SESSION['flash']['danger'] ?? null
        );
    }

    public function test_destroy_successfully_removes_user(): void
    {
        $response = $this->post(
            action: 'destroy',
            controllerName: 'App\Controllers\Admin\UsersController',
            params: ['id' => $this->student->id]
        );

        $this->assertMatchesRegularExpression('/Location: \/admin\/users/', $response);
        $this->assertMatchesRegularExpression(
            '/removido com sucesso!/',
            $_SESSION['flash']['success'] ?? ''
        );

        $this->assertNull(User::findById((int) $this->student->id));
    }

    public function test_block_user_successfully_blocks_user(): void
    {
        $this->assertTrue($this->student->isActive());

        $response = $this->post(
            action: 'block',
            controllerName: 'App\Controllers\Admin\UsersController',
            params: ['id' => $this->student->id]
        );

        $this->assertMatchesRegularExpression('/Location: \/admin\/users/', $response);
        $updatedUser = User::findById((int) $this->student->id);
        $this->assertNotNull($updatedUser);
        $this->assertTrue($updatedUser->isBlocked());
    }

    public function test_activate_user_successfully_activates_user(): void
    {
        $this->student->block();
        $this->student->save();
        $this->assertTrue($this->student->isBlocked());

        $response = $this->post(
            action: 'activate',
            controllerName: 'App\Controllers\Admin\UsersController',
            params: ['id' => $this->student->id]
        );

        $this->assertMatchesRegularExpression('/Location: \/admin\/users/', $response);
        $updatedUser = User::findById((int) $this->student->id);
        $this->assertNotNull($updatedUser);
        $this->assertTrue($updatedUser->isActive());
    }
}
