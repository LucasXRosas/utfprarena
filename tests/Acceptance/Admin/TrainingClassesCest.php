<?php

namespace Tests\Acceptance\Admin;

use PHPUnit\Framework\Assert;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

/**
 * Testes de aceitação da relação NxN Turma <-> Aluno (tabela pivô class_enrollments).
 * Dados do Seeder: turma 1 (Iniciante) com Aluno Beach Tennis (#2), Beatriz (#4) e Carlos (#5);
 * turma 2 (Intermediário) com Aluno Beach Tennis e Eduardo (#7); turma 3 (Avançado) com Carlos;
 * turma 4 (Kids) sem alunos.
 */
class TrainingClassesCest extends BaseAcceptanceCest
{
    private function loginAsAdmin(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->fillField('user[email]', 'admin@arena.com');
        $page->fillField('user[password]', 'admin123');
        $page->click('#login_submit');
        $page->seeInCurrentUrl('/admin');
    }

    private function loginAsStudent(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->fillField('user[email]', 'aluno@arena.com');
        $page->fillField('user[password]', 'aluno123');
        $page->click('#login_submit');
        $page->seeInCurrentUrl('/dashboard');
    }

    /**
     * Marca a página atual com uma variável JavaScript. Se a página recarregar,
     * a variável some — é assim que provamos que a ação foi feita via Ajax.
     */
    private function markPageAsNotReloaded(AcceptanceTester $page): void
    {
        $page->executeJS('window.__semRecarregar = true;');
    }

    private function assertPageWasNotReloaded(AcceptanceTester $page): void
    {
        Assert::assertTrue($page->executeJS('return window.__semRecarregar === true;'));
    }

    // 1.1 Registro da relação (via Ajax)
    public function registroDaRelacao(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/classes/4');
        $page->see('Turma: Kids - Sábado 10h');
        $page->see('Nenhum aluno matriculado nesta turma.');
        $page->see('0', '#students_count');

        $page->selectOption('#enroll_user_id', '4'); // Beatriz Lima
        $page->click('#btn_enroll');

        $page->waitForText('Aluno Beatriz Lima matriculado com sucesso na turma Kids - Sábado 10h!', 5, '#ajax_feedback');
        $page->seeElement('#enrolled_student_4');
        $page->see('1', '#students_count');
        $page->dontSeeElement('#enroll_user_id option[value="4"]');

        // A relação foi persistida: continua lá depois de recarregar a página
        $page->reloadPage();
        $page->seeElement('#enrolled_student_4');
        $page->see('Beatriz Lima', '#enrolled_students');
    }

    // 1.2 Visualização da relação (pelos dois lados: turma -> alunos e aluno -> turmas)
    public function visualizacaoDaRelacao(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/classes');
        $page->see('Iniciante - Terça e Quinta 19h', '#table_classes');
        $page->see('3/8', '#class_students_count_1');

        $page->click('#btn_show_class_1');
        $page->seeInCurrentUrl('/admin/classes/1');
        $page->see('Aluno Beach Tennis', '#enrolled_students');
        $page->see('Beatriz Lima', '#enrolled_students');
        $page->see('Carlos Eduardo', '#enrolled_students');
        $page->dontSee('Eduardo Mendes', '#enrolled_students');

        // Lado do aluno, na ficha do atleta (admin)
        $page->amOnPage('/admin/users/2');
        $page->see('Iniciante - Terça e Quinta 19h', '#athlete_classes');
        $page->see('Intermediário - Segunda e Quarta 20h', '#athlete_classes');
        $page->click('#logout_btn');

        // Lado do aluno, na Área do Aluno
        $this->loginAsStudent($page);
        $page->see('Minhas Turmas');
        $page->see('2 turma(s)', '#my_classes_count');
        $page->see('Iniciante - Terça e Quinta 19h', '#my_classes');
        $page->see('Intermediário - Segunda e Quarta 20h', '#my_classes');
        $page->dontSee('Avançado - Sábado 9h', '#my_classes');
    }

    // 1.3 Remoção da relação (via Ajax)
    public function remocaoDaRelacao(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/classes/1');
        $page->seeElement('#enrolled_student_2');

        $page->click('#btn_unenroll_2');
        $page->acceptPopup();

        $page->waitForText('Aluno Aluno Beach Tennis removido da turma Iniciante - Terça e Quinta 19h.', 5, '#ajax_feedback');
        $page->dontSeeElement('#enrolled_student_2');
        $page->see('2', '#students_count');
        $page->seeElement('#enroll_user_id option[value="2"]'); // volta a ser opção do select

        // Só a relação foi removida: o aluno continua existindo e na outra turma
        $page->reloadPage();
        $page->dontSeeElement('#enrolled_student_2');
        $page->amOnPage('/admin/classes/2');
        $page->seeElement('#enrolled_student_2');
    }

    // 1.4 Funcionalidade em Ajax: matricular e remover sem recarregar a página
    public function funcionalidadeEmAjax(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/classes/3');
        $this->markPageAsNotReloaded($page);

        // Registro
        $page->selectOption('#enroll_user_id', '7'); // Eduardo Mendes
        $page->click('#btn_enroll');
        $page->waitForElement('#enrolled_student_7', 5);
        $page->see('2', '#students_count');
        $this->assertPageWasNotReloaded($page);
        $page->seeInCurrentUrl('/admin/classes/3');

        // Remoção de uma linha criada pelo próprio Ajax (delegação de eventos)
        $page->click('#btn_unenroll_7');
        $page->acceptPopup();
        $page->waitForElementNotVisible('#enrolled_student_7', 5);
        $page->see('removido da turma', '#ajax_feedback');
        $page->see('1', '#students_count');
        $this->assertPageWasNotReloaded($page);

        // Erro devolvido pelo servidor em JSON (o select voltou para "Selecione um aluno...") também sem recarregar
        $page->click('#btn_enroll');
        $page->waitForText('Selecione um aluno válido para matricular.', 5, '#ajax_feedback');
        $this->assertPageWasNotReloaded($page);
    }

    // Lotação: a turma Avançado tem 4 vagas
    public function turmaLotadaNaoAceitaMatricula(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/classes/3');

        foreach (['2', '4', '7'] as $userId) {
            $page->selectOption('#enroll_user_id', $userId);
            $page->click('#btn_enroll');
            $page->waitForElement('#enrolled_student_' . $userId, 5);
        }
        $page->see('4', '#students_count');
        $page->seeElement('#class_full_badge');

        $page->selectOption('#enroll_user_id', '3');
        $page->click('#btn_enroll');
        $page->waitForText('a turma está lotada!', 5, '#ajax_feedback');
        $page->dontSeeElement('#enrolled_student_3');
    }

    // Tentativa de remoção de registro com dependências
    public function exclusaoDeTurmaComAlunosEhBloqueada(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/classes');

        $page->click('#btn_delete_class_1');
        $page->acceptPopup();
        $page->see('Não é possível excluir a turma Iniciante - Terça e Quinta 19h: ela possui 3 aluno(s) matriculado(s).');
        $page->see('Iniciante - Terça e Quinta 19h', '#table_classes');

        // Turma sem dependências pode ser excluída
        $page->click('#btn_delete_class_4');
        $page->acceptPopup();
        $page->see('Turma Kids - Sábado 10h excluída com sucesso!');
        $page->dontSee('Kids - Sábado 10h', '#table_classes');
    }

    // Aluno não acessa a gestão de turmas
    public function alunoNaoAcessaGestaoDeTurmas(AcceptanceTester $page): void
    {
        $this->loginAsStudent($page);
        $page->amOnPage('/admin/classes/1');

        $page->seeInCurrentUrl('/dashboard');
        $page->see('Acesso não autorizado');
    }
}
