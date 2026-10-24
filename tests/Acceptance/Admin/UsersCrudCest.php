<?php

namespace Tests\Acceptance\Admin;

use Tests\Support\AcceptanceTester;

class UsersCrudCest
{
    private function loginAsAdmin(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->fillField('user[email]', 'admin@arena.com');
        $page->fillField('user[password]', 'admin123');
        $page->click('#login_submit');
        $page->seeInCurrentUrl('/admin');
    }

    // 1.5 Listagem de todos registros
    public function listagemDeTodosRegistros(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/users');
        $page->see('Painel Gerencial');
        $page->see('Gestão de Atletas e Controle de Catraca Lógica');
        $page->see('Administrador Arena');
        $page->see('Aluno Beach Tennis');
        $page->see('ATIVO');
        $page->see('BLOQUEADO');
    }

    // 1.1 Tentativa de cadastro com dados incorretos
    public function tentativaDeCadastroComDadosIncorretos(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/users/new');
        $page->see('Cadastrar Novo Atleta');

        // Submissão com senhas divergentes
        $page->fillField('user[name]', 'Novo Atleta Invalido');
        $page->fillField('user[email]', 'invalido@arena.com');
        $page->fillField('user[password]', 'senha123');
        $page->fillField('user[password_confirmation]', 'senhaDiferente');
        $page->click('#btn_submit_user');

        $page->seeInCurrentUrl('/admin/users/new');
        $page->see('As senhas devem ser idênticas!');
    }

    // 1.2 Tentativa de cadastro bem-sucedida
    public function tentativaDeCadastroBemSucedida(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/users/new');

        $page->fillField('user[name]', 'Carlos Magno Atleta');
        $page->fillField('user[email]', 'carlos.magno@arena.com');
        $page->fillField('user[phone]', '(41) 91111-2222');
        $page->fillField('user[cpf]', '123.456.789-00');
        $page->selectOption('user[role]', 'student');
        $page->selectOption('user[status]', 'active');
        $page->fillField('user[password]', 'segredo123');
        $page->fillField('user[password_confirmation]', 'segredo123');
        $page->click('#btn_submit_user');

        $page->seeInCurrentUrl('/admin/users');
        $page->see('cadastrado com sucesso!');
        $page->see('Carlos Magno Atleta');
    }

    // 1.3 Tentativa de atualização com dados incorretos
    public function tentativaDeAtualizacaoComDadosIncorretos(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/users/4/edit');
        $page->see('Editar Atleta');

        // Tentar atualizar com email já pertencente a outro usuário (admin@arena.com)
        $page->fillField('user[email]', 'admin@arena.com');
        $page->click('#btn_update_user');

        $page->seeInCurrentUrl('/admin/users/4/edit');
        $page->see('Erro ao atualizar: já existe um registro com esse dado');
    }

    // 1.4 Tentativa de atualização bem-sucedida
    public function tentativaDeAtualizacaoBemSucedida(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/users/4/edit');

        $page->fillField('user[name]', 'Beatriz Lima Atualizada');
        $page->fillField('user[phone]', '(41) 97777-8888');
        $page->click('#btn_update_user');

        $page->seeInCurrentUrl('/admin/users');
        $page->see('atualizado com sucesso!');
        $page->see('Beatriz Lima Atualizada');
    }

    // 1.6 Remoção do registro
    public function remocaoDoRegistro(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/users');
        $page->see('Carlos Eduardo');

        $page->click('#btn_delete_user_5');
        $page->acceptPopup();

        $page->seeInCurrentUrl('/admin/users');
        $page->see('removido com sucesso!');
        $page->dontSee('Carlos Eduardo', '#table_users');
    }

    // 1.7 Paginação dos registros
    public function paginacaoDosRegistros(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/users');

        // Página 1 contém registros e link para página 2
        $page->see('Gestão de Atletas');
        $page->see('Próxima');
        $page->see('2');

        // Clica na página 2
        $page->click('2');
        $page->seeInCurrentUrl('page=2');
        $page->see('Anterior');
        $page->see('Eduardo Mendes');
    }
}
