<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Middleware de Autorizacao por Papel (Role).
 *
 * Garante que apenas usuarios com o papel (role) correto acessem determinadas rotas.
 * Exemplo: apenas 'manager' pode acessar /admin/*.
 *
 * Diferenca entre Autenticacao e Autorizacao (topico 2.2 da rubrica):
 *   - Autenticacao: "Quem e voce?" (login com email/senha)
 *   - Autorizacao: "O que voce pode fazer?" (role-based access control)
 */
class RoleMiddleware implements MiddlewareInterface
{
    /** @var string[] */
    private array $allowedRoles;

    public function __construct(string ...$allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(Request $request, callable $next): Response
    {
        $authUser = Session::getAuthUser();

        if ($authUser === null) {
            return Response::json([
                'status' => 'error',
                'code' => 401,
                'message' => 'Nao autenticado.',
            ], 401);
        }

        $userRole = (string)($authUser['role'] ?? '');

        if (!in_array($userRole, $this->allowedRoles, true)) {
            FlashMessage::set('error', 'Acesso negado. Voce nao tem permissao para acessar esta area.');
            return Response::json([
                'status' => 'error',
                'code' => 403,
                'message' => "Acesso negado. Esta area requer papel: " . implode(' ou ', $this->allowedRoles),
                'your_role' => $userRole,
            ], 403);
        }

        return $next($request);
    }
}
