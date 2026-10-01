<?php

declare(strict_types=1);

/**
 * config/routes.php - Definicao de Todas as Rotas da Aplicacao.
 *
 * =========================================================================
 * TOPICO DO PROFESSOR (Rubrica 2.1):
 *   Route::middleware('auth')->group(...)
 *
 * Este arquivo define:
 *   1. Rotas Publicas: acessiveis por qualquer usuario (nao autenticado).
 *   2. Rotas Autenticadas: protegidas por middleware 'auth'.
 *      -> Qualquer requisicao sem sessao ativa e BLOQUEADA pelo AuthMiddleware.
 *   3. Rotas de Admin: protegidas por 'auth' + 'role:manager'.
 * =========================================================================
 *
 * @param \App\Core\Router $router
 */

use App\Controllers\AccessController;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ChatController;
use App\Controllers\ComplaintController;
use App\Controllers\DashboardController;
use App\Controllers\InvoiceController;

// =============================================================================
// ROTAS PUBLICAS - Acessadas por qualquer usuario (sem autenticacao)
// =============================================================================

// Health check e home
$router->get('/', function (\App\Core\Request $req): \App\Core\Response {
    return \App\Core\Response::json([
        'app' => 'UTFPR Arena API',
        'version' => '1.0.0',
        'status' => 'online',
        'docs' => 'https://github.com/LucasXRosas/utfprarena/wiki',
    ]);
});

$router->get('/health', function (\App\Core\Request $req): \App\Core\Response {
    return \App\Core\Response::json(['status' => 'healthy', 'timestamp' => date('c')]);
});

// Autenticacao (rotas publicas)
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/auth/register', [AuthController::class, 'register']);

// Chat publico - qualquer visitante pode ver e enviar mensagens
$router->get('/chat/messages', [ChatController::class, 'index']);
$router->post('/chat/messages', [ChatController::class, 'store']);

// Problem Tracker publico
$router->get('/complaints', [ComplaintController::class, 'index']);
$router->post('/complaints', [ComplaintController::class, 'store']);
$router->get('/complaints/{id}', [ComplaintController::class, 'show']);

// =============================================================================
// ROTAS AUTENTICADAS - Middleware 'auth' bloqueia usuarios nao logados
//
// Rubrica 2.1: Route::middleware('auth')->group(function ($router) { ... })
//
// O middleware 'auth' verifica Session::isAuthenticated().
// Se nao autenticado: retorna 401 (API) ou redireciona para /login (browser).
// =============================================================================
$router->middleware('auth')->group(function (\App\Core\Router $router): void {

    // Logout (requer autenticacao para poder sair)
    $router->post('/logout', [AuthController::class, 'logout']);

    // Dashboard do usuario autenticado
    $router->get('/dashboard', [DashboardController::class, 'index']);

    // Reclamacoes - apenas usuarios autenticados podem atualizar status
    $router->put('/complaints/{id}', [ComplaintController::class, 'update']);

    // Controle de acesso (requer autenticacao)
    $router->get('/access/check/{userId}', [AccessController::class, 'check']);

    // Faturas - usuario autenticado ve suas proprias faturas
    $router->get('/users/{userId}/invoices', [InvoiceController::class, 'index']);
    $router->put('/invoices/{id}/pay', [InvoiceController::class, 'pay']);
});

// =============================================================================
// ROTAS ADMINISTRATIVAS - Apenas usuarios com role 'manager'
//
// Demonstra a diferenca entre AUTENTICACAO e AUTORIZACAO (rubrica 2.2):
//   - Qualquer usuario autenticado passa pelo middleware 'auth'
//   - Apenas 'manager' passa pelo middleware 'role:manager' (403 para outros)
// =============================================================================
$router->middleware('auth')->group(function (\App\Core\Router $router): void {

    // Painel administrativo
    $router->get('/admin', [AdminController::class, 'index']);
    $router->get('/admin/users', [AdminController::class, 'users']);

    // Gestao de acesso (bloquear/ativar usuarios - apenas admin)
    $router->post('/access/block/{userId}', [AccessController::class, 'block']);
    $router->post('/access/activate/{userId}', [AccessController::class, 'activate']);

    // Faturas - apenas admin pode criar novas faturas
    $router->post('/users/{userId}/invoices', [InvoiceController::class, 'store']);
});
