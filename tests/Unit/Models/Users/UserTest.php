<?php

namespace Tests\Unit\Models\Users;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    private User $user;
    private User $user2;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = new User([
            'name' => 'User 1',
            'email' => 'fulano@example.com',
            'password' => '123456',
            'password_confirmation' => '123456',
            'role' => User::ROLE_MANAGER,
            'status' => User::STATUS_ACTIVE
        ]);
        $this->user->save();

        $this->user2 = new User([
            'name' => 'User 2',
            'email' => 'fulano1@example.com',
            'password' => '123456',
            'password_confirmation' => '123456',
            'role' => User::ROLE_STUDENT,
            'status' => User::STATUS_ACTIVE
        ]);
        $this->user2->save();
    }

    public function test_should_create_new_user(): void
    {
        $this->assertCount(2, User::all());
    }

    public function test_all_should_return_all_users(): void
    {
        $this->user2->save();

        $users[] = $this->user->id;
        $users[] = $this->user2->id;

        $all = array_map(fn($user) => $user->id, User::all());

        $this->assertCount(2, $all);
        $this->assertEquals($users, $all);
    }

    public function test_destroy_should_remove_the_user(): void
    {
        $this->user->destroy();
        $this->assertCount(1, User::all());
    }

    public function test_set_id(): void
    {
        $this->user->id = 10;
        $this->assertEquals(10, $this->user->id);
    }

    public function test_set_name(): void
    {
        $this->user->name = 'User name';
        $this->assertEquals('User name', $this->user->name);
    }

    public function test_set_email(): void
    {
        $this->user->email = 'outro@example.com';
        $this->assertEquals('outro@example.com', $this->user->email);
    }

    public function test_errors_should_return_errors(): void
    {
        $user = new User();

        $this->assertFalse($user->isValid());
        $this->assertFalse($user->save());
        $this->assertTrue($user->hasErrors());

        $this->assertEquals('não pode ser vazio!', $user->errors('name'));
        $this->assertEquals('não pode ser vazio!', $user->errors('email'));
    }

    public function test_errors_should_return_password_confirmation_error(): void
    {
        $user = new User([
            'name' => 'User 3',
            'email' => 'fulano3@example.com',
            'password' => '123456',
            'password_confirmation' => '1234567'
        ]);

        $this->assertFalse($user->isValid());
        $this->assertFalse($user->save());

        $this->assertEquals('as senhas devem ser idênticas!', $user->errors('password_confirmation'));
    }

    public function test_find_by_id_should_return_the_user(): void
    {
        $this->assertEquals($this->user->id, User::findById((int) $this->user->id)->id);
    }

    public function test_find_by_id_should_return_null(): void
    {
        $this->assertNull(User::findById(999));
    }

    public function test_find_by_email_should_return_the_user(): void
    {
        $this->assertEquals($this->user->id, User::findByEmail($this->user->email)->id);
    }

    public function test_find_by_email_should_return_null(): void
    {
        $this->assertNull(User::findByEmail('not.exits@example.com'));
    }

    public function test_authenticate_should_return_the_true(): void
    {
        $this->assertTrue($this->user->authenticate('123456'));
        $this->assertFalse($this->user->authenticate('wrong'));
    }

    public function test_authenticate_should_return_false(): void
    {
        $this->assertFalse($this->user->authenticate(''));
    }

    public function test_update_should_not_change_the_password(): void
    {
        $this->user->password = '654321';
        $this->user->save();

        $this->assertTrue($this->user->authenticate('123456'));
        $this->assertFalse($this->user->authenticate('654321'));
    }

    public function test_is_admin_and_is_student(): void
    {
        $this->assertTrue($this->user->isAdmin());
        $this->assertFalse($this->user->isStudent());

        $this->assertFalse($this->user2->isAdmin());
        $this->assertTrue($this->user2->isStudent());
    }

    public function test_is_active_and_block_activation(): void
    {
        $this->assertTrue($this->user->isActive());
        $this->assertFalse($this->user->isBlocked());

        $this->user->block();
        $this->assertTrue($this->user->isBlocked());
        $this->assertFalse($this->user->isActive());

        $this->user->activate();
        $this->assertTrue($this->user->isActive());
        $this->assertFalse($this->user->isBlocked());
    }

    public function test_metrics_count(): void
    {
        $this->assertEquals(2, User::countTotal());
        $this->assertEquals(2, User::countActive());
        $this->assertEquals(0, User::countBlocked());

        $this->user2->block();
        $this->user2->save();

        $this->assertEquals(2, User::countTotal());
        $this->assertEquals(1, User::countActive());
        $this->assertEquals(1, User::countBlocked());
    }

    public function test_user_avatar_returns_profile_avatar_with_default_image(): void
    {
        $avatar = $this->user->avatar();

        $this->assertInstanceOf(\App\Services\ProfileAvatar::class, $avatar);
        $this->assertFalse($avatar->hasAvatar());
        $this->assertEquals(\App\Services\ProfileAvatar::DEFAULT_AVATAR, $avatar->path());
        $this->assertEquals('users/' . $this->user->id, $avatar->directory());
    }

    public function test_new_user_requires_password_with_min_length(): void
    {
        $user = new User(['name' => 'Sem Senha', 'email' => 'semsenha@example.com']);
        $this->assertFalse($user->isValid());
        $this->assertEquals('não pode ser vazio!', $user->errors('password'));

        $user = new User([
            'name' => 'Senha Curta',
            'email' => 'curta@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);
        $this->assertFalse($user->isValid());
        $this->assertEquals('deve ter no mínimo 6 caracteres!', $user->errors('password'));
    }

    public function test_email_must_be_valid(): void
    {
        $this->user->email = 'sem-arroba';
        $this->assertFalse($this->user->isValid());
        $this->assertEquals('deve ser um e-mail válido!', $this->user->errors('email'));
    }

    public function test_cpf_must_have_format_and_be_unique(): void
    {
        $this->user->cpf = '12345678900';
        $this->assertFalse($this->user->isValid());
        $this->assertEquals('deve estar no formato 000.000.000-00!', $this->user->errors('cpf'));

        $this->user->cpf = '123.456.789-00';
        $this->assertTrue($this->user->save());

        $this->user2->cpf = '123.456.789-00';
        $this->assertFalse($this->user2->isValid());
        $this->assertEquals('já existe um registro com esse dado', $this->user2->errors('cpf'));
    }

    public function test_role_and_status_must_be_known_values(): void
    {
        $this->user->role = 'superadmin';
        $this->user->status = 'qualquer';
        $this->assertFalse($this->user->isValid());
        $this->assertEquals('possui um valor inválido!', $this->user->errors('role'));
        $this->assertEquals('possui um valor inválido!', $this->user->errors('status'));
    }

    public function test_update_keeps_own_email_valid_even_with_different_case(): void
    {
        // A collation do MySQL ignora maiúsculas: o próprio registro não pode contar como duplicado
        $this->user->email = 'FULANO@example.com';
        $this->assertTrue($this->user->isValid());

        $this->user->email = 'FULANO1@example.com'; // e-mail do user2
        $this->assertFalse($this->user->isValid());
    }

    public function test_update_password_is_validated_and_applied(): void
    {
        $this->user->updatePassword('abc', 'abc');
        $this->assertFalse($this->user->save());
        $this->assertEquals('deve ter no mínimo 6 caracteres!', $this->user->errors('password'));

        $this->user->updatePassword('novaSenha1', 'outra');
        $this->assertFalse($this->user->save());
        $this->assertEquals('as senhas devem ser idênticas!', $this->user->errors('password_confirmation'));

        $this->user->updatePassword('novaSenha1', 'novaSenha1');
        $this->assertTrue($this->user->save());
        $this->assertTrue(User::findById($this->user->id)?->authenticate('novaSenha1'));
    }

    public function test_user_invoices_relationship(): void
    {
        $invoices = $this->user->invoices();
        $this->assertInstanceOf(\Core\Database\ActiveRecord\HasMany::class, $invoices);
        $this->assertEmpty($this->user->getInvoices());
    }
}
