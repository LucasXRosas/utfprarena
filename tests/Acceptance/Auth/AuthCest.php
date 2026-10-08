<?php

namespace Tests\Acceptance\Auth;

use App\Models\User;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class AuthCest extends BaseAcceptanceCest
{
    public function _before(AcceptanceTester $page): void
    {
        parent::_before($page);

        // Prepara usuários para os testes de aceitação
        $admin = new User([
            'name' => 'Administrador Arena',
            'email' => 'admin@arena.com',
            'password' => 'admin123',
            'password_confirmation' => 'admin123',
            'role' => User::ROLE_MANAGER,
            'status' => User::STATUS_ACTIVE
        ]);
        $admin->save();

        $aluno = new User([
            'name' => 'Aluno Beach Tennis',
            'email' => 'aluno@arena.com',
            'password' => 'aluno123',
            'password_confirmation' => 'aluno123',
            'role' => User::ROLE_STUDENT,
            'status' => User::STATUS_ACTIVE
        ]);
        $aluno->save();
    }

    // 1.1 Tentativa de acesso à área restrita sem autenticação
    public function tentativaDeAcessoAreaRestritaSemAutenticacao(AcceptanceTester $page): void
    {
        $page->amOnPage('/dashboard');
        $page->seeInCurrentUrl('/login');
        $page->see('Você deve estar logado para acessar essa página');
    }

    // 1.2 Tentativa de autenticação com dados incorretos
    public function tentativaDeAutenticacaoComDadosIncorretos(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->fillField('user[email]', 'inexistente@arena.com');
        $page->fillField('user[password]', 'senhaerrada');
        $page->click('#login_submit');
        $page->seeInCurrentUrl('/login');
        $page->see('Email ou senha inválidos');
    }

    // 1.3 Autenticação bem-sucedida (Administrador)
    public function autenticacaoBemSucedidaAdmin(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->fillField('user[email]', 'admin@arena.com');
        $page->fillField('user[password]', 'admin123');
        $page->click('#login_submit');
        $page->seeInCurrentUrl('/admin');
        $page->see('Painel Gerencial');
    }

    // 1.3 Autenticação bem-sucedida (Usuário Aluno)
    public function autenticacaoBemSucedidaAluno(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->fillField('user[email]', 'aluno@arena.com');
        $page->fillField('user[password]', 'aluno123');
        $page->click('#login_submit');
        $page->seeInCurrentUrl('/dashboard');
        $page->see('Área do Aluno');
    }

    // 1.4 Logout
    public function logout(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->fillField('user[email]', 'aluno@arena.com');
        $page->fillField('user[password]', 'aluno123');
        $page->click('#login_submit');
        $page->seeInCurrentUrl('/dashboard');

        $page->click('Sair');
        $page->seeInCurrentUrl('/login');
        $page->see('Logout realizado com sucesso');
    }
}
