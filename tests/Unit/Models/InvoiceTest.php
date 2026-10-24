<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use App\Models\User;
use Core\Database\Database;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    private User $user;
    private Invoice $inv1;
    private Invoice $inv2;

    public function setUp(): void
    {
        parent::setUp();

        $pdo = Database::getDatabaseConn();
        $pdo->exec("DELETE FROM invoices;");

        $this->user = new User([
            'name' => 'Aluno Teste',
            'email' => 'aluno.invoice@arena.com',
            'password' => '123456',
            'password_confirmation' => '123456',
            'role' => User::ROLE_STUDENT,
            'status' => User::STATUS_ACTIVE
        ]);
        $this->user->save();

        $this->inv1 = new Invoice([
            'user_id' => $this->user->id,
            'amount' => 150.00,
            'due_date' => '2026-01-10',
            'status' => Invoice::STATUS_PAID
        ]);
        $this->inv1->save();

        $this->inv2 = new Invoice([
            'user_id' => $this->user->id,
            'amount' => 180.00,
            'due_date' => '2026-02-10',
            'status' => Invoice::STATUS_PENDING
        ]);
        $this->inv2->save();
    }

    public function test_should_create_invoice_with_defaults(): void
    {
        $inv = new Invoice([
            'user_id' => $this->user->id,
            'amount' => 200.00,
            'due_date' => '2026-03-10'
        ]);
        $this->assertEquals(Invoice::STATUS_PENDING, $inv->status);
        $this->assertTrue($inv->isValid());
        $this->assertTrue($inv->save());
    }

    public function test_validates_required_fields(): void
    {
        $inv = new Invoice();
        $this->assertFalse($inv->isValid());
        $this->assertFalse($inv->save());
        $this->assertEquals('não pode ser vazio!', $inv->errors('user_id'));
        $this->assertEquals('não pode ser vazio!', $inv->errors('amount'));
        $this->assertEquals('não pode ser vazio!', $inv->errors('due_date'));
    }

    public function test_status_helpers(): void
    {
        $this->assertTrue($this->inv1->isPaid());
        $this->assertFalse($this->inv1->isPending());

        $this->assertTrue($this->inv2->isPending());
        $this->assertFalse($this->inv2->isPaid());

        $overdue = new Invoice([
            'user_id' => $this->user->id,
            'amount' => 100.00,
            'due_date' => '2025-12-01',
            'status' => Invoice::STATUS_OVERDUE
        ]);
        $this->assertTrue($overdue->isOverdue());
    }

    public function test_all_by_user_ordered_by_due_date_desc(): void
    {
        $invoices = Invoice::allByUser((int) $this->user->id);
        $this->assertCount(2, $invoices);
        $this->assertEquals('2026-02-10', $invoices[0]->due_date);
        $this->assertEquals('2026-01-10', $invoices[1]->due_date);
    }

    public function test_invoice_belongs_to_user(): void
    {
        $associatedUser = $this->inv1->user();
        $this->assertInstanceOf(\Core\Database\ActiveRecord\BelongsTo::class, $associatedUser);
        $this->assertEquals($this->user->id, $associatedUser->get()->id);
    }

    public function test_invoice_implements_array_access(): void
    {
        $this->assertTrue(isset($this->inv1['amount']));
        $this->assertEquals(150.00, (float) $this->inv1['amount']);
        $this->assertEquals('paid', $this->inv1['status']);
    }
}
