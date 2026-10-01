<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Models\Complaint;
use PHPUnit\Framework\TestCase;

/**
 * Testes Unitarios da entidade Complaint (Problem Tracker).
 */
class ComplaintTest extends TestCase
{
    public function testReclamacaoIniciaComStatusOpen(): void
    {
        $complaint = new Complaint(null, 'Quadra danificada', 'Buraco na quadra 2.', 'aluno@email.com');

        $this->assertSame(Complaint::STATUS_OPEN, $complaint->getStatus());
    }

    public function testIniciarAnaliseAlteraStatusParaInProgress(): void
    {
        $complaint = new Complaint(1, 'Problema', 'Descricao.', 'aluno@email.com');
        $complaint->startAnalysis();

        $this->assertSame(Complaint::STATUS_IN_PROGRESS, $complaint->getStatus());
    }

    public function testResolverReclamacaoAlteraStatusParaResolved(): void
    {
        $complaint = new Complaint(1, 'Problema', 'Descricao.', 'aluno@email.com');
        $complaint->resolve();

        $this->assertSame(Complaint::STATUS_RESOLVED, $complaint->getStatus());
    }

    public function testToArrayRetornaEstruturaCerta(): void
    {
        $complaint = new Complaint(1, 'Titulo', 'Descricao', 'autor@email.com');
        $array = $complaint->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('author_email', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('created_at', $array);
    }
}
