<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Testes de Integracao - Placeholder para testes de endpoints HTTP.
 *
 * Os testes de integracao validam o fluxo completo:
 *   Request -> Router -> Controller -> Model -> Response
 *
 * Estes testes requerem o ambiente Docker completo (PHP-FPM + Nginx + PostgreSQL).
 * Para executar apenas testes unitarios (sem Docker), use:
 *   vendor/bin/phpunit --testsuite Unit
 *
 * Para executar testes de integracao, use:
 *   vendor/bin/phpunit --testsuite Integration
 */
class ApiIntegrationTest extends TestCase
{
    /**
     * Teste de smoke para verificar que os testes de integracao rodam.
     */
    public function testPlaceholderIntegracaoNaoFalha(): void
    {
        // Este teste e um placeholder que sempre passa.
        // Substitua por testes reais de endpoint quando o servidor estiver disponivel.
        $this->assertTrue(true, 'Placeholder de integracao ativo.');
    }
}
