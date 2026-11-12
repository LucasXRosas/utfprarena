<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\ImageValidator;
use App\Services\ProfileAvatar;
use Core\Database\Database;
use Tests\Support\DatabaseFailure;
use Tests\Support\Upload\TestFileStorage;
use Tests\Support\Upload\UploadFixtures;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    private User $user;
    private ProfileAvatar $avatar;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = new User([
            'name' => 'Atleta Avatar',
            'email' => 'avatar@arena.com',
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);
        $this->user->save();

        $this->avatar = new ProfileAvatar($this->user, new ImageValidator(), new TestFileStorage());
    }

    public function tearDown(): void
    {
        UploadFixtures::cleanup();
        parent::tearDown();
    }

    public function test_returns_default_avatar_when_user_has_no_photo(): void
    {
        $this->assertFalse($this->avatar->hasAvatar());
        $this->assertNull($this->avatar->relativePath());
        $this->assertEquals(ProfileAvatar::DEFAULT_AVATAR, $this->avatar->path());
    }

    public function test_update_saves_file_and_avatar_name(): void
    {
        $this->assertTrue($this->avatar->update(UploadFixtures::validJpeg('minha foto.jpg')));

        // Banco: users.avatar_name recebe um nome aleatório (nunca o nome original)
        $fresh = User::findById($this->user->id);
        $this->assertNotNull($fresh);
        $this->assertMatchesRegularExpression('/^avatar_[a-f0-9]{32}\.jpg$/', (string) $fresh->avatar_name);

        // Filesystem: arquivo gravado em uploads/users/{id}/
        $this->assertTrue($this->avatar->hasAvatar());
        $this->assertFileExists((string) $this->avatar->absolutePath());
        $this->assertStringContainsString('/users/' . $this->user->id . '/avatar_', $this->avatar->path());
    }

    public function test_update_replaces_and_deletes_previous_file(): void
    {
        $this->avatar->update(UploadFixtures::validJpeg());
        $oldPath = (string) $this->avatar->absolutePath();

        $this->avatar->update(UploadFixtures::validPng());
        $newPath = (string) $this->avatar->absolutePath();

        $this->assertNotEquals($oldPath, $newPath);
        $this->assertFileDoesNotExist($oldPath);
        $this->assertFileExists($newPath);
    }

    public function test_update_keeps_previous_avatar_when_database_update_fails(): void
    {
        $this->assertTrue($this->avatar->update(UploadFixtures::validJpeg()));
        $previousName = $this->user->avatar_name;
        $previousPath = (string) $this->avatar->absolutePath();
        $directory = dirname($previousPath);

        DatabaseFailure::on('users', 'UPDATE');

        $this->assertFalse($this->avatar->update(UploadFixtures::validPng()));
        $this->assertStringContainsString('foto anterior foi mantida', (string) $this->avatar->firstError());

        // Banco continua apontando para o arquivo anterior, que ainda existe
        $this->assertEquals($previousName, User::findById($this->user->id)?->avatar_name);
        $this->assertFileExists($previousPath);

        // Objeto em memória também volta ao valor anterior
        $this->assertEquals($previousName, $this->user->avatar_name);

        // O arquivo novo foi descartado: só o anterior permanece na pasta
        $files = array_values(array_diff(scandir($directory) ?: [], ['.', '..']));
        $this->assertEquals([basename($previousPath)], $files);
    }

    public function test_update_when_user_row_no_longer_exists_discards_new_file(): void
    {
        // Model::update() retorna false quando nenhuma linha é afetada
        Database::getDatabaseConn()->exec('DELETE FROM users WHERE id = ' . (int) $this->user->id);

        $this->assertFalse($this->avatar->update(UploadFixtures::validPng()));
        $this->assertNull($this->user->avatar_name);

        $directory = (new TestFileStorage())->absolutePath($this->avatar->directory());
        $this->assertEquals([], array_values(array_diff(scandir($directory) ?: [], ['.', '..'])));
    }

    public function test_remove_keeps_file_when_database_update_fails(): void
    {
        $this->avatar->update(UploadFixtures::validPng());
        $name = $this->user->avatar_name;
        $path = (string) $this->avatar->absolutePath();

        DatabaseFailure::on('users', 'UPDATE');

        $this->assertFalse($this->avatar->remove());
        $this->assertFileExists($path);
        $this->assertEquals($name, User::findById($this->user->id)?->avatar_name);
        $this->assertEquals($name, $this->user->avatar_name);
    }

    public function test_update_rejects_invalid_file_and_keeps_user_unchanged(): void
    {
        $this->assertFalse($this->avatar->update(UploadFixtures::phpScript('avatar.php.jpg')));
        $this->assertStringContainsString('Arquivo malicioso', (string) $this->avatar->firstError());

        $fresh = User::findById($this->user->id);
        $this->assertNull($fresh?->avatar_name);
    }

    public function test_update_requires_persisted_user(): void
    {
        $avatar = new ProfileAvatar(new User(['name' => 'Novo']), null, new TestFileStorage());
        $this->assertFalse($avatar->update(UploadFixtures::validPng()));
        $this->assertNotEmpty($avatar->errors());
    }

    public function test_remove_deletes_file_and_clears_avatar_name(): void
    {
        $this->avatar->update(UploadFixtures::validPng());
        $path = (string) $this->avatar->absolutePath();

        $this->assertTrue($this->avatar->remove());
        $this->assertFileDoesNotExist($path);
        $this->assertNull(User::findById($this->user->id)?->avatar_name);
        $this->assertEquals(ProfileAvatar::DEFAULT_AVATAR, $this->avatar->path());
    }

    public function test_remove_without_avatar_returns_false(): void
    {
        $this->assertFalse($this->avatar->remove());
        $this->assertEquals('Nenhuma foto de perfil cadastrada.', $this->avatar->firstError());
    }

    public function test_destroying_user_removes_avatar_from_filesystem(): void
    {
        $this->avatar->update(UploadFixtures::validPng());
        $path = (string) $this->avatar->absolutePath();
        $this->assertFileExists($path);

        $this->user->destroy();

        $this->assertFileDoesNotExist($path);
    }
}
