<?php

declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Controllers\AuthController;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

/**
 * Testes de Aceitacao - Fluxo de Autenticacao.
 *
 * Rubrica - Testes de Aceitacao (item 2):
 *   1.1 Tentativa de acesso a area restrita sem autenticacao
 *   1.2 Tentativa de autenticacao com dados incorretos
 *   1.3 Autenticacao bem-sucedida
 *   1.4 Logout
 *
 * Estes testes cobrem o COMPORTAMENTO do sistema do ponto de vista do usuario,
 * validando o fluxo completo de Request -> Middleware -> Controller -> Response.
 */
class AuthFlowTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        // Garante que nao ha sessao ativa antes de cada teste
        Session::logout();
        Session::remove('_flash');

        $this->router = new Router();

        // Configura as rotas necessarias para os testes
        $this->router->get('/login', [AuthController::class, 'showLogin']);
        $this->router->post('/login', [AuthController::class, 'login']);
        $this->router->post('/logout', [AuthController::class, 'logout']);

        // Rota protegida: requer autenticacao
        $this->router->middleware('auth')->group(function (Router $r): void {
            $r->get('/dashboard', function (Request $req): \App\Core\Response {
                return \App\Core\Response::json(['status' => 'ok', 'area' => 'dashboard']);
            });
        });
    }

    // =========================================================================
    // 1.1 - Tentativa de acesso a area restrita SEM autenticacao
    // =========================================================================

    public function testAcessoDashboardSemLoginRetorna401(): void
    {
        // Garante que nao ha sessao
        $this->assertFalse(Session::isAuthenticated());

        $request = new Request('GET', '/dashboard', [], [], ['accept' => 'application/json']);
        $response = $this->router->dispatch($request);

        $this->assertSame(401, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertIsArray($body);
        $this->assertSame('error', $body['status']);
        $this->assertSame(401, $body['code']);
    }

    public function testAcessoDashboardSemLoginContemMensagemDeNaoAutenticado(): void
    {
        $request = new Request('GET', '/dashboard', [], [], ['accept' => 'application/json']);
        $response = $this->router->dispatch($request);

        $body = json_decode($response->getContent(), true);
        $this->assertIsArray($body);
        $this->assertStringContainsStringIgnoringCase('autenticado', (string)($body['message'] ?? ''));
    }

    // =========================================================================
    // 1.2 - Tentativa de autenticacao com dados INCORRETOS
    // =========================================================================

    public function testLoginComEmailInexistenteRetornaRedirectParaLogin(): void
    {
        $request = new Request('POST', '/login', [], [
            'email' => 'naoexiste@email.com',
            'password' => 'qualquersenha',
        ]);

        $response = $this->router->dispatch($request);

        // Deve redirecionar de volta para /login (302)
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaders()['Location'] ?? '');
    }

    public function testLoginComSenhaErradaRedirectParaLogin(): void
    {
        $request = new Request('POST', '/login', [], [
            'email' => 'admin@arena.com',
            'password' => 'senhaerrada',
        ]);

        $response = $this->router->dispatch($request);

        $this->assertSame(302, $response->getStatusCode());
        // Nao deve criar sessao com credenciais erradas
        $this->assertFalse(Session::isAuthenticated());
    }

    public function testLoginComCamposVaziosRedirectParaLogin(): void
    {
        $request = new Request('POST', '/login', [], [
            'email' => '',
            'password' => '',
        ]);

        $response = $this->router->dispatch($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertFalse(Session::isAuthenticated());
    }

    // =========================================================================
    // 1.3 - Autenticacao BEM-SUCEDIDA
    // =========================================================================

    public function testLoginComAdminCorretoRedirectParaAdmin(): void
    {
        $request = new Request('POST', '/login', [], [
            'email' => 'admin@arena.com',
            'password' => 'admin123',
        ]);

        $response = $this->router->dispatch($request);

        // Deve redirecionar para /admin (manager) ou /dashboard
        $this->assertSame(302, $response->getStatusCode());
        $location = $response->getHeaders()['Location'] ?? '';
        $this->assertContains($location, ['/admin', '/dashboard']);
    }

    public function testLoginBemSucedidoCriaSessionValida(): void
    {
        $request = new Request('POST', '/login', [], [
            'email' => 'admin@arena.com',
            'password' => 'admin123',
        ]);

        $this->router->dispatch($request);

        // Sessao deve estar ativa apos login bem-sucedido
        $this->assertTrue(Session::isAuthenticated());
    }

    public function testAposLoginSessaoContemDadosDoUsuario(): void
    {
        $request = new Request('POST', '/login', [], [
            'email' => 'admin@arena.com',
            'password' => 'admin123',
        ]);

        $this->router->dispatch($request);

        $authUser = Session::getAuthUser();

        $this->assertIsArray($authUser);
        $this->assertArrayHasKey('email', $authUser);
        $this->assertArrayHasKey('role', $authUser);
        $this->assertSame('admin@arena.com', $authUser['email']);
    }

    public function testAposLoginDashboardEstaAcessivel(): void
    {
        // Primeiro: faz login
        $loginRequest = new Request('POST', '/login', [], [
            'email' => 'admin@arena.com',
            'password' => 'admin123',
        ]);
        $this->router->dispatch($loginRequest);

        $this->assertTrue(Session::isAuthenticated(), 'Deve estar autenticado apos login');

        // Segundo: acessa dashboard protegido
        $dashRequest = new Request('GET', '/dashboard', [], [], ['accept' => 'application/json']);
        $response = $this->router->dispatch($dashRequest);

        $this->assertSame(200, $response->getStatusCode());
    }

    // =========================================================================
    // 1.4 - Logout
    // =========================================================================

    public function testLogoutDestroisessaoERedirecionaParaLogin(): void
    {
        // Simula sessao ativa
        Session::loginUser(1, 'admin@arena.com', 'manager');
        $this->assertTrue(Session::isAuthenticated());

        $request = new Request('POST', '/logout');
        $response = $this->router->dispatch($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaders()['Location'] ?? '');
    }

    public function testAposLogoutSessaoNaoEstaAutenticada(): void
    {
        Session::loginUser(1, 'admin@arena.com', 'manager');

        $request = new Request('POST', '/logout');
        $this->router->dispatch($request);

        $this->assertFalse(Session::isAuthenticated());
    }

    public function testAposLogoutDashboardBloqueiaComk401(): void
    {
        // Login
        Session::loginUser(1, 'admin@arena.com', 'manager');

        // Logout
        $this->router->dispatch(new Request('POST', '/logout'));

        // Tenta acessar dashboard apos logout
        $response = $this->router->dispatch(
            new Request('GET', '/dashboard', [], [], ['accept' => 'application/json'])
        );

        $this->assertSame(401, $response->getStatusCode());
    }
}
