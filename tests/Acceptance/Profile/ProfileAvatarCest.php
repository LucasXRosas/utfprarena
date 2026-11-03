<?php

namespace Tests\Acceptance\Profile;

use PHPUnit\Framework\Assert;
use Tests\Support\AcceptanceTester;

/**
 * Testes de aceitação da aba "Meu Perfil" (upload, visualização e remoção do avatar).
 */
class ProfileAvatarCest
{
    private function loginAsStudent(AcceptanceTester $page): void
    {
        $page->amOnPage('/login');
        $page->fillField('user[email]', 'aluno@arena.com');
        $page->fillField('user[password]', 'aluno123');
        $page->click('#login_submit');
        $page->seeInCurrentUrl('/dashboard');
    }

    private function filesystemPath(string $src): string
    {
        return codecept_root_dir() . 'public' . (string) parse_url($src, PHP_URL_PATH);
    }

    // Upload + visualização do avatar
    public function uploadEVisualizacaoDoAvatar(AcceptanceTester $page): void
    {
        $this->loginAsStudent($page);
        $page->click('#nav_profile');
        $page->seeInCurrentUrl('/profile');
        $page->see('Meu Perfil');

        $page->attachFile('#avatar_file', 'quadra_foto.jpg');
        $page->click('#btn_upload_avatar');

        $page->see('Foto de perfil atualizada com sucesso!');
        $src = $page->grabAttributeFrom('#profile_avatar', 'src');
        Assert::assertStringContainsString('/assets/uploads/users/', $src);
        Assert::assertFileExists($this->filesystemPath($src));

        // O avatar aparece também na navbar e na Área do Aluno
        Assert::assertEquals($src, $page->grabAttributeFrom('#nav_avatar', 'src'));
        $page->amOnPage('/dashboard');
        Assert::assertEquals($src, $page->grabAttributeFrom('#dashboard_avatar', 'src'));
    }

    // Remoção do avatar
    public function remocaoDoAvatar(AcceptanceTester $page): void
    {
        $this->loginAsStudent($page);
        $page->amOnPage('/profile');
        $page->attachFile('#avatar_file', 'quadra_foto.jpg');
        $page->click('#btn_upload_avatar');
        $page->see('Foto de perfil atualizada com sucesso!');

        $path = $this->filesystemPath($page->grabAttributeFrom('#profile_avatar', 'src'));
        Assert::assertFileExists($path);

        $page->click('#btn_remove_avatar');
        $page->acceptPopup();

        $page->see('Foto de perfil removida com sucesso!');
        $page->seeElement('#profile_default_avatar');
        Assert::assertStringContainsString(
            '/assets/images/defaults/avatar.png',
            $page->grabAttributeFrom('#profile_avatar', 'src')
        );
        clearstatcache(true, $path); // o arquivo é apagado por outro processo (PHP-FPM)
        Assert::assertFileDoesNotExist($path);
    }

    // Validação: avatar malicioso é rejeitado
    public function uploadDeAvatarMalicioso(AcceptanceTester $page): void
    {
        $this->loginAsStudent($page);
        $page->amOnPage('/profile');
        $page->attachFile('#avatar_file', 'malicioso.php.jpg');
        $page->click('#btn_upload_avatar');

        $page->see('Erro ao enviar foto: Arquivo malicioso');
        $page->seeElement('#profile_default_avatar');
    }
}
