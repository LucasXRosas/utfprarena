<?php

namespace Tests\Acceptance\Auth;

use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class AuthCest extends BaseAcceptanceCest
{
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
        $page->see('E-mail ou senha inválidos.');
        $page->seeInField('user[email]', 'inexistente@arena.com');
    }

    // 1.2 Campos vazios: mensagens personalizadas embaixo dos campos (sem balões do navegador)
    public function tentativaDeAutenticacaoComCamposVazios(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->click('#login_submit'); // novalidate: o navegador não bloqueia o envio

        $page->seeInCurrentUrl('/login');
        $page->see('Informe o seu e-mail.', '#error_user_email');
        $page->see('Informe a sua senha.', '#error_user_password');
        $page->seeElement('#user_email.is-invalid');
    }

    // 1.2 E-mail em formato inválido
    public function tentativaDeAutenticacaoComEmailInvalido(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->fillField('user[email]', 'aluno-sem-arroba');
        $page->fillField('user[password]', 'aluno123');
        $page->click('#login_submit');

        $page->see('Informe um e-mail válido', '#error_user_email');
        $page->seeInField('user[email]', 'aluno-sem-arroba');
        $page->dontSeeElement('#error_user_password');
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
