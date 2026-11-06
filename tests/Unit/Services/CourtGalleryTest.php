<?php

namespace Tests\Unit\Services;

use App\Models\Court;
use App\Models\CourtImage;
use App\Services\CourtGallery;
use App\Services\ImageValidator;
use Tests\Support\Upload\TestFileStorage;
use Tests\Support\Upload\UploadFixtures;
use Tests\TestCase;

class CourtGalleryTest extends TestCase
{
    private Court $court;
    private CourtGallery $gallery;

    public function setUp(): void
    {
        parent::setUp();

        $this->court = new Court(['name' => 'Quadra Galeria']);
        $this->court->save();

        $this->gallery = new CourtGallery($this->court, new ImageValidator(), new TestFileStorage());
    }

    public function tearDown(): void
    {
        UploadFixtures::cleanup();
        parent::tearDown();
    }

    public function test_add_saves_file_and_database_record(): void
    {
        $image = $this->gallery->add(UploadFixtures::validJpeg('vista lateral.jpg'), '  Vista lateral  ');

        $this->assertInstanceOf(CourtImage::class, $image);
        $this->assertGreaterThan(0, $image->id);

        // Banco
        $saved = CourtImage::findById($image->id);
        $this->assertNotNull($saved);
        $this->assertEquals($this->court->id, $saved->court_id);
        $this->assertEquals('vista lateral.jpg', $saved->original_name);
        $this->assertEquals('image/jpeg', $saved->mime_type);
        $this->assertEquals('Vista lateral', $saved->caption);
        $this->assertMatchesRegularExpression('/^court_[a-f0-9]{32}\.jpg$/', $saved->file_name);
        $this->assertGreaterThan(0, (int) $saved->size);

        // Filesystem
        $this->assertTrue($this->gallery->fileExists($saved));
        $this->assertStringContainsString('/courts/' . $this->court->id . '/', $this->gallery->url($saved));
    }

    public function test_add_multiple_images_relation_one_to_many(): void
    {
        $this->gallery->add(UploadFixtures::validPng());
        $this->gallery->add(UploadFixtures::validJpeg());
        $this->gallery->add(UploadFixtures::validPng('terceira.png'));

        $this->assertCount(3, $this->gallery->images());
        $this->assertEquals(3, $this->court->imagesCount());
    }

    public function test_add_rejects_invalid_file_without_writing_anything(): void
    {
        $this->assertNull($this->gallery->add(UploadFixtures::oversized()));
        $this->assertStringContainsString('2MB', (string) $this->gallery->firstError());

        $this->assertNull($this->gallery->add(UploadFixtures::polyglotPng()));
        $this->assertStringContainsString('código embutido', (string) $this->gallery->firstError());

        $this->assertCount(0, $this->gallery->images());
        $this->assertDirectoryDoesNotExist((new TestFileStorage())->absolutePath($this->gallery->directory()));
    }

    public function test_add_to_nonexistent_gallery_fails(): void
    {
        $ghost = new Court(['name' => 'Fantasma']);
        $ghost->id = 9999;
        $gallery = new CourtGallery($ghost, null, new TestFileStorage());

        $this->assertNull($gallery->add(UploadFixtures::validPng()));
        $this->assertStringContainsString('Galeria inexistente', (string) $gallery->firstError());
        $this->assertCount(0, CourtImage::all());
    }

    public function test_remove_deletes_file_and_record(): void
    {
        $image = $this->gallery->add(UploadFixtures::validPng());
        $this->assertNotNull($image);
        $path = (new TestFileStorage())->absolutePath($image->relativePath());
        $this->assertFileExists($path);

        $this->assertTrue($this->gallery->remove($image));

        $this->assertFileDoesNotExist($path);
        $this->assertNull(CourtImage::findById($image->id));
    }

    public function test_remove_image_from_another_gallery_is_denied(): void
    {
        $other = new Court(['name' => 'Outra Quadra']);
        $other->save();
        $otherGallery = new CourtGallery($other, null, new TestFileStorage());
        $image = $otherGallery->add(UploadFixtures::validPng());
        $this->assertNotNull($image);

        $this->assertFalse($this->gallery->remove($image));
        $this->assertEquals('A imagem não pertence a esta galeria.', $this->gallery->firstError());
        $this->assertNotNull(CourtImage::findById($image->id));
    }

    public function test_destroying_court_removes_images_from_database_and_filesystem(): void
    {
        $this->gallery->add(UploadFixtures::validPng());
        $this->gallery->add(UploadFixtures::validJpeg());
        $directory = (new TestFileStorage())->absolutePath($this->gallery->directory());
        $this->assertDirectoryExists($directory);
        $this->assertCount(2, CourtImage::where(['court_id' => $this->court->id]));

        $this->court->destroy();

        // Banco: ON DELETE CASCADE removeu as imagens
        $this->assertCount(0, CourtImage::where(['court_id' => $this->court->id]));
        // Filesystem: Court::destroy() apagou a pasta da galeria
        $this->assertDirectoryDoesNotExist($directory);
    }
}
