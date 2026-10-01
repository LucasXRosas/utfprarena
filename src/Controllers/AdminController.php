<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\FlashMessage;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * AdminController - Area protegida exclusiva do administrador (manager).
 *
 * Requer DOIS middlewares empilhados:
 *   1. 'auth': usuario deve estar autenticado
 *   2. 'role:manager': usuario deve ter papel de manager
 *
 * Demonstracao da diferenca entre Autenticacao e Autorizacao (rubrica 2.2):
 *   - Um aluno autenticado acessando /admin -> 403 Forbidden (autorizado? Nao!)
 *   - Um manager autenticado acessando /admin -> 200 OK
 */
class AdminController extends BaseController
{
    /**
     * GET /admin
     * Exibe o painel administrativo.
     *
     * @param Request $request
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        $authUser = Session::getAuthUser();

        // Dados de gestao para demonstracao
        $stats = [
            'total_alunos' => 47,
            'alunos_bloqueados' => 3,
            'faturas_vencidas' => 8,
            'receita_mes' => 7050.00,
        ];

        return $this->view('admin/index', [
            'title' => 'Painel Administrativo',
            'user' => $authUser,
            'stats' => $stats,
            'success' => FlashMessage::get('success'),
            'error' => FlashMessage::get('error'),
        ]);
    }

    /**
     * GET /admin/users
     * Lista todos os usuarios do sistema (area restrita).
     *
     * @param Request $request
     * @param array<string, string> $params
     */
    public function users(Request $request, array $params = []): Response
    {
        // TODO: Buscar usuarios do banco de dados
        return $this->json([
            'status' => 'success',
            'message' => 'Lista de usuarios (apenas para administradores)',
            'data' => [],
        ]);
    }
}
