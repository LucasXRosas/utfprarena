<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\FlashMessage;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * DashboardController - Area protegida do usuario autenticado.
 *
 * Esta rota SÓ e acessivel por usuarios autenticados (middleware 'auth').
 *
 * Demonstracao rubrica 1.1: Acessar /dashboard sem login
 *   -> AuthMiddleware intercepta -> redireciona para /login
 *
 * Demonstracao rubrica 1.3: Apos login bem-sucedido
 *   -> Session contem dados do usuario -> dashboard e exibido
 */
class DashboardController extends BaseController
{
    /**
     * GET /dashboard
     * Exibe o painel do usuario autenticado.
     *
     * @param Request $request
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        $authUser = Session::getAuthUser();

        return $this->view('dashboard/index', [
            'title' => 'Meu Painel',
            'user' => $authUser,
            'success' => FlashMessage::get('success'),
        ]);
    }
}
