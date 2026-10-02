<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

/**
 * Testes Unitarios da entidade User.
 *
 * Valida a logica de papeis, status e transicoes de estado do usuario.
 */
class UserTest extends TestCase
{
    private function makeUser(string $status = User::STATUS_ACTIVE, string $role = User::ROLE_STUDENT): User
    {
        return new User(
            id: 1,
            fullName: 'Joao da Silva',
            cpf: '12345678901',
            email: 'joao@email.com',
            password: password_hash('secret', PASSWORD_BCRYPT),
            phone: '41999990000',
            role: $role,
            status: $status,
        );
    }

    public function testUsuarioAtivoEhAtivo(): void
    {
        $user = $this->makeUser(User::STATUS_ACTIVE);

        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isBlocked());
    }

    public function testUsuarioBloqueadoEhBloqueado(): void
    {
        $user = $this->makeUser(User::STATUS_BLOCKED);

        $this->assertTrue($user->isBlocked());
        $this->assertFalse($user->isActive());
    }

    public function testBloquearUsuarioAlteraStatus(): void
    {
        $user = $this->makeUser(User::STATUS_ACTIVE);
        $this->assertFalse($user->isBlocked());

        $user->block();

        $this->assertTrue($user->isBlocked());
        $this->assertSame(User::STATUS_BLOCKED, $user->getStatus());
    }

    public function testAtivarUsuarioBloqueadoAlteraStatus(): void
    {
        $user = $this->makeUser(User::STATUS_BLOCKED);
        $this->assertTrue($user->isBlocked());

        $user->activate();

        $this->assertTrue($user->isActive());
        $this->assertSame(User::STATUS_ACTIVE, $user->getStatus());
    }

    public function testToArrayRetornaCamposEsperados(): void
    {
        $user = $this->makeUser();
        $array = $user->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('full_name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('role', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayNotHasKey('password', $array, 'Senha NAO deve ser exposta no toArray().');
    }

    public function testPadraoDeRoleEhStudent(): void
    {
        $user = $this->makeUser(User::STATUS_ACTIVE, User::ROLE_STUDENT);
        $this->assertSame(User::ROLE_STUDENT, $user->getRole());
    }

    public function testUsuarioManager(): void
    {
        $user = $this->makeUser(User::STATUS_ACTIVE, User::ROLE_MANAGER);
        $this->assertSame(User::ROLE_MANAGER, $user->getRole());
    }

    public function testVerifyPasswordCorreta(): void
    {
        $user = $this->makeUser();
        $this->assertTrue($user->verifyPassword('secret'));
        $this->assertFalse($user->verifyPassword('wrong-password'));
        $this->assertNotEmpty($user->getPassword());
    }
}
