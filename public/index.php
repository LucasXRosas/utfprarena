<?php

/**
 * Front Controller - Ponto de Entrada Unico da Aplicacao (Padrao MVC)
 *
 * Todo o trafico HTTP e redirecionado aqui pelo Nginx.
 */

declare(strict_types=1);

// =============================================================================
// 1. BOOTSTRAP: Autoload PSR-4 do Composer
// =============================================================================
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

// =============================================================================
// 2. AMBIENTE: Modo debug de erros conforme APP_ENV
// =============================================================================
$appEnv = getenv('APP_ENV') ?: 'development';
$debug = $appEnv !== 'production';

ErrorHandler::register($debug);

// =============================================================================
// 3. SESSAO: Inicia a sessao PHP (necessario para autenticacao e FlashMessage)
// =============================================================================
Session::start();

// =============================================================================
// 4. CORS: Cabecalhos para API
// =============================================================================
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// =============================================================================
// 5. ROTEAMENTO: Carrega as rotas do arquivo de configuracao
// =============================================================================
$router = new Router();

// Carrega as rotas definidas em config/routes.php
// Este arquivo contem: rotas publicas, autenticadas e de admin
require_once __DIR__ . '/../config/routes.php';

// =============================================================================
// 6. DISPATCH: Captura a requisicao e despacha para o controller
// =============================================================================
$request = Request::createFromGlobals();
$response = $router->dispatch($request);
$response->send();
