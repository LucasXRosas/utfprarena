<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Core\Router;
use App\Core\Request;
use PHPUnit\Framework\TestCase;

/**
 * Testes Unitarios do Sistema de Roteamento MVC.
 *
 * Valida o despacho de rotas estaticas e com parametros dinamicos.
 */
class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    private function makeRequest(string $method, string $uri): Request
    {
        return new Request($method, $uri);
    }

    public function testRotaGetSimplesFunciona(): void
    {
        $this->router->get('/health', function (Request $req): \App\Core\Response {
            return \App\Core\Response::json(['status' => 'ok']);
        });

        $request = $this->makeRequest('GET', '/health');
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('ok', $response->getContent());
    }

    public function testRotaNaoEncontradaRetorna404(): void
    {
        $request = $this->makeRequest('GET', '/rota-inexistente');
        $response = $this->router->dispatch($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRotaComParametroDinamicoECapturado(): void
    {
        $this->router->get('/users/{id}', function (Request $req, array $params): \App\Core\Response {
            return \App\Core\Response::json(['user_id' => $params['id']]);
        });

        $request = $this->makeRequest('GET', '/users/42');
        $response = $this->router->dispatch($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('42', $response->getContent());
    }

    public function testRotaPostNaoEMatcheadaComGet(): void
    {
        $this->router->post('/auth/login', function (Request $req): \App\Core\Response {
            return \App\Core\Response::json(['token' => 'abc']);
        });

        $request = $this->makeRequest('GET', '/auth/login');
        $response = $this->router->dispatch($request);

        $this->assertSame(404, $response->getStatusCode());
    }
}
