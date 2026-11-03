<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\ImageValidator;
use App\Services\ProfileAvatar;
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
