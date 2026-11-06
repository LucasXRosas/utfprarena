<?php

namespace Tests\Unit\Models;

use App\Models\Court;
use App\Models\CourtImage;
use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\Database;
use Tests\TestCase;

class CourtImageTest extends TestCase
{
    private Court $court;
    private CourtImage $image;

    public function setUp(): void
    {
        parent::setUp();

        $this->court = new Court(['name' => 'Quadra Central']);
        $this->court->save();

        $this->image = new CourtImage([
            'court_id' => $this->court->id,
            'file_name' => 'court_abc.png',
            'original_name' => 'foto.png',
            'mime_type' => 'image/png',
            'size' => 2048,
            'caption' => 'Vista aérea',
        ]);
        $this->image->save();
    }

    public function test_should_create_image_with_created_at(): void
    {
        $this->assertGreaterThan(0, $this->image->id);
        $this->assertNotNull($this->image->created_at);
        $this->assertCount(1, CourtImage::all());
    }

    public function test_validates_required_fields(): void
    {
        $image = new CourtImage();

        $this->assertFalse($image->isValid());
        $this->assertFalse($image->save());
        $this->assertEquals('não pode ser vazio!', $image->errors('court_id'));
        $this->assertEquals('não pode ser vazio!', $image->errors('file_name'));
        $this->assertEquals('não pode ser vazio!', $image->errors('original_name'));
        $this->assertEquals('não pode ser vazio!', $image->errors('mime_type'));
    }

    public function test_validates_that_court_exists(): void
    {
        $image = new CourtImage([
            'court_id' => 9999,
            'file_name' => 'court_x.png',
            'original_name' => 'x.png',
            'mime_type' => 'image/png',
            'size' => 10,
        ]);

        $this->assertFalse($image->save());
        $this->assertEquals('registro relacionado inexistente!', $image->errors('court_id'));
    }

    public function test_image_belongs_to_court(): void
    {
        $this->assertInstanceOf(BelongsTo::class, $this->image->court());
        $this->assertEquals($this->court->id, $this->image->court()->get()->id);
        $this->assertEquals('Quadra Central', $this->image->court->name);
    }

    public function test_court_has_many_images(): void
    {
        $second = new CourtImage([
            'court_id' => $this->court->id,
            'file_name' => 'court_def.jpg',
            'original_name' => 'foto2.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 4096,
        ]);
        $second->save();

        $images = $this->court->images;
        $this->assertCount(2, $images);
        $this->assertContainsOnlyInstancesOf(CourtImage::class, $images);
        $this->assertEquals($this->image->id, $this->court->images()->findById($this->image->id)?->id);
    }

    public function test_relative_path_url_and_size_label(): void
    {
        $this->assertEquals('courts/' . $this->court->id . '/court_abc.png', $this->image->relativePath());
        $this->assertStringEndsWith('/courts/' . $this->court->id . '/court_abc.png', $this->image->url());
        $this->assertEquals('2,0 KB', $this->image->sizeLabel());

        $this->image->size = 3 * 1024 * 1024;
        $this->assertEquals('3,00 MB', $this->image->sizeLabel());
    }

    /* ---------------- Integridade referencial no banco ---------------- */

    public function test_database_rejects_image_for_nonexistent_court(): void
    {
        // Ignorando a validação do model, o próprio MySQL barra o INSERT pela FOREIGN KEY
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessageMatches('/foreign key constraint fails/');

        Database::getDatabaseConn()->exec(
            "INSERT INTO court_images (court_id, file_name, original_name, mime_type, size)
             VALUES (9999, 'court_fantasma.png', 'fantasma.png', 'image/png', 10)"
        );
    }

    public function test_database_cascade_deletes_images_with_court(): void
    {
        Database::getDatabaseConn()->exec('DELETE FROM courts WHERE id = ' . (int) $this->court->id);

        $this->assertNull(CourtImage::findById($this->image->id));
    }
}
