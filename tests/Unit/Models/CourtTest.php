<?php

namespace Tests\Unit\Models;

use App\Models\Court;
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
}
