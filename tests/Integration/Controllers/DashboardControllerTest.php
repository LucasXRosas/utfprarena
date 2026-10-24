<?php

namespace Tests\Integration\Controllers;

use App\Models\Court;
use App\Models\Invoice;
use App\Models\User;
use Lib\Authentication\Auth;

class DashboardControllerTest extends ControllerTestCase
{
    private User $student;

    public function setUp(): void
    {
        parent::setUp();

        $this->student = new User([
            'name' => 'Aluno Dashboard Test',
            'email' => 'aluno.dash@arena.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => User::ROLE_STUDENT,
            'status' => User::STATUS_ACTIVE,
            'phone' => '(41) 99999-8888'
        ]);
        $this->student->save();

        Auth::login($this->student);
    }

    public function test_index_renders_dashboard_with_courts_and_invoices(): void
    {
        $court = new Court([
            'name' => 'Quadra Areia Especial',
            'type' => Court::TYPE_BEACH_TENNIS,
            'status' => Court::STATUS_AVAILABLE
        ]);
        $court->save();

        $invoice = new Invoice([
            'user_id' => $this->student->id,
            'amount' => 199.90,
            'due_date' => '2026-05-15',
            'status' => Invoice::STATUS_PAID
        ]);
        $invoice->save();

        $response = $this->get(
            action: 'index',
            controllerName: 'App\Controllers\DashboardController'
        );

        $this->assertMatchesRegularExpression('/Área do Aluno/', $response);
        $this->assertMatchesRegularExpression('/Aluno Dashboard Test/', $response);
        $this->assertMatchesRegularExpression('/Quadra Areia Especial/', $response);
        $this->assertMatchesRegularExpression('/199,90/', $response);
    }
}
