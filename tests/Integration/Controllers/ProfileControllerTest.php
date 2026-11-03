<?php

namespace Tests\Integration\Controllers;

use App\Models\User;
use App\Services\ProfileAvatar;
use Lib\Authentication\Auth;
use Tests\Support\Upload\TestFileStorage;
use Tests\Support\Upload\UploadFixtures;

class ProfileControllerTest extends ControllerTestCase
{
    private User $student;

    public function setUp(): void
    {
        parent::setUp();
        unset($_SESSION['flash']);

        $this->student = new User([
            'name' => 'Aluno Perfil',
            'email' => 'aluno.perfil@arena.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);
        $this->student->save();
        Auth::login($this->student);
    }

    public function tearDown(): void
    {
        unset($_SESSION['flash']);
        UploadFixtures::cleanup();
        parent::tearDown();
    }

    public function test_show_renders_default_avatar_and_upload_form(): void
    {
        $response = $this->get('show', 'App\Controllers\ProfileController');

        $this->assertStringContainsString('Meu Perfil', $response);
        $this->assertStringContainsString(ProfileAvatar::DEFAULT_AVATAR, $response);
        $this->assertStringContainsString('enctype="multipart/form-data"', $response);
        $this->assertStringContainsString('name="avatar"', $response);
    }

    public function test_show_renders_uploaded_avatar(): void
    {
        $avatar = new ProfileAvatar($this->student, null, new TestFileStorage());
        $avatar->update(UploadFixtures::validPng());

        $response = $this->get('show', 'App\Controllers\ProfileController');

        $this->assertStringContainsString($avatar->path(), $response);
        $this->assertStringContainsString('Remover Foto', $response);
    }

    public function test_update_avatar_with_malicious_file_is_rejected(): void
    {
        $tmp = UploadFixtures::phpScript('avatar.php');
        $response = $this->post('updateAvatar', 'App\Controllers\ProfileController', [], ['avatar' => $tmp]);

        $this->assertStringContainsString('Location: /profile', $response);
        $this->assertStringContainsString('Arquivo malicioso', $_SESSION['flash']['danger'] ?? '');
        $this->assertNull(User::findById($this->student->id)?->avatar_name);
    }

    public function test_destroy_avatar_removes_file(): void
    {
        $avatar = new ProfileAvatar($this->student, null, new TestFileStorage());
        $avatar->update(UploadFixtures::validPng());
        $path = (string) $avatar->absolutePath();

        $response = $this->post('destroyAvatar', 'App\Controllers\ProfileController');

        $this->assertStringContainsString('Location: /profile', $response);
        $this->assertFileDoesNotExist($path);
        $this->assertNull(User::findById($this->student->id)?->avatar_name);
        $this->assertStringContainsString('removida com sucesso', $_SESSION['flash']['success'] ?? '');
    }

    public function test_destroy_avatar_without_photo_shows_error(): void
    {
        $this->post('destroyAvatar', 'App\Controllers\ProfileController');

        $this->assertEquals('Nenhuma foto de perfil cadastrada.', $_SESSION['flash']['danger'] ?? '');
    }
}
