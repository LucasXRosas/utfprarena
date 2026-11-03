<?php

namespace Tests\Acceptance\Admin;

use PHPUnit\Framework\Assert;
use Tests\Support\AcceptanceTester;

/**
 * Testes de aceitação da galeria de fotos das quadras (relação 1xN Court -> CourtImage).
 * Usa os dados do populate (admin@arena.com, aluno@arena.com e as quadras #1, #2 e #3).
 */
class CourtGalleryCest
{
    private const BIG_FILE = 'imagem_grande.jpg';

    public function _before(AcceptanceTester $page): void
    {
        // Gera um arquivo de 3MB (acima do limite de 2MB) apenas durante o teste
        $bigFile = codecept_data_dir() . self::BIG_FILE;
        if (!is_file($bigFile)) {
            file_put_contents($bigFile, "\xFF\xD8\xFF\xE0" . str_repeat("\0", 3 * 1024 * 1024));
        }
    }

    public function _after(AcceptanceTester $page): void
    {
        $bigFile = codecept_data_dir() . self::BIG_FILE;
        if (is_file($bigFile)) {
            unlink($bigFile);
        }
    }

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

    private function createCourt(AcceptanceTester $page, string $name): void
    {
        $page->amOnPage('/admin/courts/new');
        $page->fillField('court[name]', $name);
        $page->click('#btn_submit_court');
        $page->see('cadastrada com sucesso');
        $page->see('Galeria: ' . $name);
    }

    private function uploadImage(AcceptanceTester $page, string $file, string $caption = ''): void
    {
        $page->attachFile('#court_image_file', $file);
        if ($caption !== '') {
            $page->fillField('#court_image_caption', $caption);
        }
        $page->click('#btn_upload_image');
    }

    /**
     * Converte a URL da imagem (src) no caminho físico dentro do container.
     */
    private function filesystemPath(string $src): string
    {
        return codecept_root_dir() . 'public' . (string) parse_url($src, PHP_URL_PATH);
    }

    // 1.1 Upload de imagem
    public function uploadDeImagem(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $this->createCourt($page, 'Quadra Upload Teste');

        $page->see('Nenhuma foto cadastrada nesta galeria ainda.');
        $this->uploadImage($page, 'quadra_foto.jpg', 'Foto enviada pelo teste');

        $page->see('enviada com sucesso! Arquivo salvo em: /assets/uploads/courts/');
        $page->seeNumberOfElements('#court_gallery img', 1);
        $page->see('Foto enviada pelo teste');
        $page->see('arquivo presente');

        // O arquivo realmente existe no filesystem
        $src = $page->grabAttributeFrom('#court_gallery img', 'src');
        Assert::assertFileExists($this->filesystemPath($src));
    }

    // 1.2 Visualização de imagem (aluno visualiza a galeria)
    public function visualizacaoDeImagem(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/courts/1');
        $this->uploadImage($page, 'quadra_foto.jpg', 'Foto visivel para o aluno');
        $page->see('enviada com sucesso');
        $page->click('#logout_btn');

        $this->loginAsStudent($page);
        $page->seeElement('#btn_view_gallery_1');
        $page->click('#btn_view_gallery_1');

        $page->seeInCurrentUrl('/courts/1');
        $page->see('Galeria: Quadra Central');
        $page->see('Foto visivel para o aluno');
        $page->seeElement('#court_gallery img');
        $page->dontSee('Remover Imagem');
    }

    // 1.3 Remoção de imagem
    public function remocaoDeImagem(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $this->createCourt($page, 'Quadra Remocao Teste');
        $this->uploadImage($page, 'quadra_foto.jpg');
        $page->see('enviada com sucesso');

        $src = $page->grabAttributeFrom('#court_gallery img', 'src');
        $path = $this->filesystemPath($src);
        Assert::assertFileExists($path);

        $page->click('#court_gallery form button');
        $page->acceptPopup();

        $page->see('removida com sucesso! Arquivo apagado do filesystem');
        $page->see('Nenhuma foto cadastrada nesta galeria ainda.');
        Assert::assertFileDoesNotExist($path);
    }

    // Validação: tamanho máximo de 2MB
    public function validacaoTamanhoMaximo(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/courts/2');
        $this->uploadImage($page, self::BIG_FILE);

        $page->see('Erro ao enviar imagem: O arquivo excede o tamanho máximo permitido de 2MB.');
    }

    // Validação: upload malicioso (dupla extensão e script disfarçado)
    public function validacaoUploadMalicioso(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $page->amOnPage('/admin/courts/2');

        $this->uploadImage($page, 'malicioso.php.jpg');
        $page->see('Arquivo malicioso: extensões executáveis (.php) não são permitidas.');

        $this->uploadImage($page, 'script_disfarcado.jpg');
        $page->see('Arquivo malicioso ou inválido: o conteúdo real (text/x-php) não é uma imagem permitida.');
    }

    // Integridade: excluir a quadra remove as imagens do banco e do filesystem
    public function exclusaoDaQuadraRemoveGaleria(AcceptanceTester $page): void
    {
        $this->loginAsAdmin($page);
        $this->createCourt($page, 'Quadra Cascata Teste');
        $this->uploadImage($page, 'quadra_foto.jpg');
        $page->see('enviada com sucesso');
        $this->uploadImage($page, 'quadra_foto.jpg');
        $page->seeNumberOfElements('#court_gallery img', 2);

        $src = $page->grabAttributeFrom('#court_gallery img', 'src');
        $directory = dirname($this->filesystemPath($src));
        Assert::assertDirectoryExists($directory);

        $page->amOnPage('/admin/courts');
        $page->see('Quadra Cascata Teste');
        $page->see('2 foto(s)');
        $courtId = basename($directory);
        $page->click('#btn_delete_court_' . $courtId);
        $page->acceptPopup();

        $page->see('removida com sucesso! 2 imagem(ns) apagada(s) do banco (ON DELETE CASCADE) e do filesystem.');
        $page->dontSee('Quadra Cascata Teste', '#table_courts');
        Assert::assertDirectoryDoesNotExist($directory);
    }
}
