<?php

namespace Tests\Unit\Models;

use App\Models\Court;
use App\Models\CourtImage;
use App\Services\CourtGallery;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\Database;
use Tests\TestCase;

class CourtTest extends TestCase
{
    private Court $court1;
    private Court $court2;

    public function setUp(): void
    {
        parent::setUp();

        $pdo = Database::getDatabaseConn();
        $pdo->exec("DELETE FROM courts;");

        $this->court1 = new Court([
            'name' => 'Quadra 1',
            'type' => Court::TYPE_BEACH_TENNIS,
            'status' => Court::STATUS_AVAILABLE
        ]);
        $this->court1->save();

        $this->court2 = new Court([
            'name' => 'Quadra 2',
            'type' => Court::TYPE_BEACH_TENNIS,
            'status' => Court::STATUS_MAINTENANCE
        ]);
        $this->court2->save();
    }

    public function test_should_create_court_with_defaults(): void
    {
        $court = new Court(['name' => 'Quadra 3']);
        $this->assertEquals(Court::TYPE_BEACH_TENNIS, $court->type);
        $this->assertEquals(Court::STATUS_AVAILABLE, $court->status);
        $this->assertTrue($court->isValid());
        $this->assertTrue($court->save());
    }

    public function test_validates_name_not_empty(): void
    {
        $court = new Court(['name' => '']);
        $this->assertFalse($court->isValid());
        $this->assertFalse($court->save());
        $this->assertEquals('não pode ser vazio!', $court->errors('name'));
    }

    public function test_is_available_and_is_maintenance(): void
    {
        $this->assertTrue($this->court1->isAvailable());
        $this->assertFalse($this->court1->isMaintenance());

        $this->assertFalse($this->court2->isAvailable());
        $this->assertTrue($this->court2->isMaintenance());
    }

    public function test_all_should_return_all_courts_ordered(): void
    {
        $all = Court::all();
        $this->assertCount(2, $all);
        $this->assertEquals('Quadra 1', $all[0]->name);
        $this->assertEquals('Quadra 2', $all[1]->name);
    }

    public function test_available_should_return_only_available_courts(): void
    {
        $available = Court::available();
        $this->assertCount(1, $available);
        $this->assertEquals('Quadra 1', $available[0]->name);
    }

    public function test_court_implements_array_access(): void
    {
        $this->assertTrue(isset($this->court1['name']));
        $this->assertEquals('Quadra 1', $this->court1['name']);
        $this->assertEquals(Court::STATUS_AVAILABLE, $this->court1['status']);

        $this->court1['name'] = 'Quadra Modificada';
        $this->assertEquals('Quadra Modificada', $this->court1->name);
    }

    public function test_court_images_relationship_is_has_many(): void
    {
        $this->assertInstanceOf(HasMany::class, $this->court1->images());
        $this->assertInstanceOf(CourtGallery::class, $this->court1->gallery());
        $this->assertEmpty($this->court1->images);
        $this->assertEquals(0, $this->court1->imagesCount());
        $this->assertNull($this->court1->coverImage());
    }

    public function test_cover_image_is_first_image_of_gallery(): void
    {
        $first = $this->court1->images()->new([
            'file_name' => 'court_1.png', 'original_name' => '1.png', 'mime_type' => 'image/png', 'size' => 1,
        ]);
        $first->save();
        $second = $this->court1->images()->new([
            'file_name' => 'court_2.png', 'original_name' => '2.png', 'mime_type' => 'image/png', 'size' => 1,
        ]);
        $second->save();

        $this->assertEquals(2, $this->court1->imagesCount());
        $this->assertEquals($first->id, $this->court1->coverImage()?->id);
        $this->assertEquals(0, $this->court2->imagesCount());
    }

    public function test_destroy_removes_court_and_its_images(): void
    {
        $image = $this->court1->images()->new([
            'file_name' => 'court_x.png', 'original_name' => 'x.png', 'mime_type' => 'image/png', 'size' => 1,
        ]);
        $image->save();

        $this->assertTrue($this->court1->destroy());
        $this->assertNull(Court::findById($this->court1->id));
        $this->assertNull(CourtImage::findById($image->id));
    }
}
