<?php

declare(strict_types=1);

/**
 * Front Controller - Ponto de Entrada Unico da Aplicacao (Padrao MVC)
 *
 * Todo o trafico HTTP e redirecionado aqui pelo Nginx.
 * Este arquivo inicializa o ambiente, registra o tratamento de erros
 * e despacha a requisicao para o roteador.
 */

// =============================================================================
// 1. BOOTSTRAP: Carregamento do Autoload do Composer (PSR-4)
// =============================================================================
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Router;

// =============================================================================
// 2. AMBIENTE: Detecta se e desenvolvimento ou producao para debug de erros
// =============================================================================
$appEnv = getenv('APP_ENV') ?: 'development';
$debug = $appEnv !== 'production';

ErrorHandler::register($debug);

// =============================================================================
// 3. CORS: Cabecalhos para permitir requisicoes de origens cruzadas (API)
// =============================================================================
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// =============================================================================
// 4. ROTEAMENTO: Instancia o Router e define as rotas da aplicacao
// =============================================================================
$router = new Router();

// --- Rota de Saude / Health Check ---
$router->get('/', function (Request $request): \App\Core\Response {
    return \App\Core\Response::json([
        'status' => 'ok',
        'app' => 'UTFPR Arena API',
        'version' => '1.0.0',
        'environment' => getenv('APP_ENV') ?: 'development',
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
});

$router->get('/health', function (Request $request): \App\Core\Response {
    return \App\Core\Response::json(['status' => 'healthy', 'timestamp' => date('c')]);
});

// --- Rotas de Autenticacao ---
use App\Controllers\AuthController;
$router->post('/auth/register', [AuthController::class, 'register']);
$router->post('/auth/login', [AuthController::class, 'login']);

// --- Rotas de Controle de Acesso ---
use App\Controllers\AccessController;
$router->get('/access/check/{userId}', [AccessController::class, 'check']);
$router->post('/access/block/{userId}', [AccessController::class, 'block']);
$router->post('/access/activate/{userId}', [AccessController::class, 'activate']);

// --- Rotas de Chat ---
use App\Controllers\ChatController;
$router->get('/chat/messages', [ChatController::class, 'index']);
$router->post('/chat/messages', [ChatController::class, 'store']);

// --- Rotas de Reclamacoes (Problem Tracker) ---
use App\Controllers\ComplaintController;
$router->get('/complaints', [ComplaintController::class, 'index']);
$router->post('/complaints', [ComplaintController::class, 'store']);
$router->get('/complaints/{id}', [ComplaintController::class, 'show']);
$router->put('/complaints/{id}', [ComplaintController::class, 'update']);

// --- Rotas de Faturas ---
use App\Controllers\InvoiceController;
$router->get('/users/{userId}/invoices', [InvoiceController::class, 'index']);
$router->post('/users/{userId}/invoices', [InvoiceController::class, 'store']);
$router->put('/invoices/{id}/pay', [InvoiceController::class, 'pay']);

// =============================================================================
// 5. DISPATCH: Captura a requisicao e envia para o controlador correto
// =============================================================================
$request = Request::createFromGlobals();
$response = $router->dispatch($request);
$response->send();
