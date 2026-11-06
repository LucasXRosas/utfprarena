<?php

namespace Tests\Unit\Services;

use App\Services\FileStorage;
use PHPUnit\Framework\TestCase;
use Tests\Support\Upload\TestFileStorage;
use Tests\Support\Upload\UploadFixtures;

class FileStorageTest extends TestCase
{
    private TestFileStorage $storage;

    public function setUp(): void
    {
        $this->storage = new TestFileStorage();
    }

    public function tearDown(): void
    {
        UploadFixtures::cleanup();
    }

    public function test_store_writes_file_on_filesystem(): void
    {
        $file = UploadFixtures::validPng();
        $relative = $this->storage->store($file['tmp_name'], 'courts/77', 'foto.png');

        $this->assertEquals('courts/77/foto.png', $relative);
        $this->assertFileExists($this->storage->absolutePath($relative));
        $this->assertTrue($this->storage->exists($relative));
        $this->assertStringEndsWith('/assets/uploads/test/courts/77/foto.png', $this->storage->url($relative));
    }

    public function test_store_ignores_directory_traversal_in_file_name(): void
    {
        $file = UploadFixtures::validPng();
        $relative = $this->storage->store($file['tmp_name'], 'courts/77', '../../../evil.png');

        $this->assertEquals('courts/77/evil.png', $relative);
    }

    public function test_delete_removes_file(): void
    {
        $relative = $this->storage->store(UploadFixtures::validPng()['tmp_name'], 'courts/77', 'foto.png');

        $this->assertTrue($this->storage->delete($relative));
        $this->assertFileDoesNotExist($this->storage->absolutePath($relative));
        $this->assertFalse($this->storage->delete($relative));
    }

    public function test_delete_directory_removes_all_files(): void
    {
        $this->storage->store(UploadFixtures::validPng()['tmp_name'], 'courts/77', 'a.png');
        $this->storage->store(UploadFixtures::validPng()['tmp_name'], 'courts/77', 'b.png');

        $this->storage->deleteDirectory('courts/77');

        $this->assertDirectoryDoesNotExist($this->storage->absolutePath('courts/77'));
    }

    public function test_real_storage_refuses_files_not_uploaded_via_http(): void
    {
        // move_uploaded_file() só aceita arquivos vindos de um POST HTTP real
        $this->expectException(\RuntimeException::class);
        (new FileStorage())->store(UploadFixtures::validPng()['tmp_name'], 'courts/77', 'foto.png');
    }

    public function test_random_name_is_unpredictable(): void
    {
        $a = FileStorage::randomName('court', 'png');
        $b = FileStorage::randomName('court', 'png');

        $this->assertMatchesRegularExpression('/^court_[a-f0-9]{32}\.png$/', $a);
        $this->assertNotEquals($a, $b);
    }
}
