<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Models\Invoice;
use App\Models\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Testes Unitarios da entidade Invoice.
 *
 * Valida o motor de regras de negocio de bloqueio por inadimplencia:
 *  - < 2 faturas vencidas -> ATIVO
 *  - >= 2 faturas vencidas -> BLOQUEADO
 */
class InvoiceTest extends TestCase
{
    private DateTimeImmutable $today;

    protected function setUp(): void
    {
        $this->today = new DateTimeImmutable('2024-01-15');
    }

    public function testInvoiceSemVencimentoNaoEVencida(): void
    {
        $dueDate = $this->today->modify('+30 days');
        $invoice = new Invoice(1, 1, 150.0, $dueDate);

        $this->assertFalse($invoice->isOverdue($this->today));
    }

    public function testInvoiceVencidaNaoEstaEmDia(): void
    {
        $dueDate = $this->today->modify('-10 days');
        $invoice = new Invoice(1, 1, 150.0, $dueDate);

        $this->assertTrue($invoice->isOverdue($this->today));
    }

    public function testInvoicePagaNaoEVencida(): void
    {
        $dueDate = $this->today->modify('-10 days');
        $invoice = new Invoice(1, 1, 150.0, $dueDate, $this->today);

        $this->assertFalse($invoice->isOverdue($this->today));
    }

    public function testMarkAsPaidMarcaFaturaComoPaga(): void
    {
        $dueDate = $this->today->modify('-10 days');
        $invoice = new Invoice(1, 1, 150.0, $dueDate);

        $this->assertFalse($invoice->isPaid());
        $invoice->markAsPaid($this->today);
        $this->assertTrue($invoice->isPaid());
    }

    // ==========================================================================
    // Testes do Motor de Regras de Acesso
    // ==========================================================================

    public function testSemFaturasVencidasUsuarioFicaAtivo(): void
    {
        $invoices = [
            new Invoice(1, 1, 150.0, $this->today->modify('+30 days')),
            new Invoice(2, 1, 150.0, $this->today->modify('+60 days')),
        ];

        $status = Invoice::evaluateAccessStatus($invoices, $this->today);

        $this->assertSame(User::STATUS_ACTIVE, $status);
    }

    public function testUmaFaturaVencidaUsuarioFicaAtivo(): void
    {
        $invoices = [
            new Invoice(1, 1, 150.0, $this->today->modify('-10 days')),
            new Invoice(2, 1, 150.0, $this->today->modify('+30 days')),
        ];

        $status = Invoice::evaluateAccessStatus($invoices, $this->today);

        $this->assertSame(User::STATUS_ACTIVE, $status);
    }

    public function testDuasFaturasVencidasUsuarioFicaBloqueado(): void
    {
        $invoices = [
            new Invoice(1, 1, 150.0, $this->today->modify('-40 days')),
            new Invoice(2, 1, 150.0, $this->today->modify('-10 days')),
        ];

        $status = Invoice::evaluateAccessStatus($invoices, $this->today);

        $this->assertSame(User::STATUS_BLOCKED, $status);
    }

    public function testTresFaturasVencidasUsuarioFicaBloqueado(): void
    {
        $invoices = [
            new Invoice(1, 1, 150.0, $this->today->modify('-70 days')),
            new Invoice(2, 1, 150.0, $this->today->modify('-40 days')),
            new Invoice(3, 1, 150.0, $this->today->modify('-10 days')),
        ];

        $status = Invoice::evaluateAccessStatus($invoices, $this->today);

        $this->assertSame(User::STATUS_BLOCKED, $status);
    }

    public function testAposQuitarFaturaUsuarioFicaAtivo(): void
    {
        $today = $this->today;
        $invoice1 = new Invoice(1, 1, 150.0, $today->modify('-40 days'));
        $invoice2 = new Invoice(2, 1, 150.0, $today->modify('-10 days'));

        // Antes do pagamento: 2 vencidas -> BLOQUEADO
        $statusBefore = Invoice::evaluateAccessStatus([$invoice1, $invoice2], $today);
        $this->assertSame(User::STATUS_BLOCKED, $statusBefore);

        // Paga uma das faturas
        $invoice1->markAsPaid($today);

        // Depois do pagamento: 1 vencida -> ATIVO
        $statusAfter = Invoice::evaluateAccessStatus([$invoice1, $invoice2], $today);
        $this->assertSame(User::STATUS_ACTIVE, $statusAfter);
    }
}
