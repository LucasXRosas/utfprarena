<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Middleware de Autenticacao.
 *
 * Intercepta TODA requisicao a uma rota protegida.
 * Se o usuario NAO estiver autenticado (sem sessao ativa), retorna 401.
 * Se estiver autenticado, passa a requisicao adiante ($next).
 *
 * Topico do professor: "Papel do middleware na autenticacao"
 *
 * Uso no sistema de rotas:
 *   Route::middleware('auth')->group(function ($router) {
 *       $router->get('/dashboard', [DashboardController::class, 'index']);
 *   });
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Verifica se o usuario esta autenticado antes de passar para o controller.
     *
     * Fluxo 1.1 da rubrica: "Tentativa de acesso a area restrita sem autenticacao"
     *   - Sem sessao ativa -> retorna 401 Unauthorized
     *
     * Fluxo 1.3 da rubrica: "Autenticacao bem-sucedida"
     *   - Com sessao ativa -> deixa passar para o controller
     */
    public function handle(Request $request, callable $next): Response
    {
        if (!Session::isAuthenticated()) {
            // Detecta se a requisicao quer JSON (API) ou HTML (browser)
            $acceptsJson = str_contains(
                $request->getHeaders()['accept'] ?? '',
                'application/json'
            );

            if ($acceptsJson) {
                return Response::json([
                    'status' => 'error',
                    'code' => 401,
                    'message' => 'Nao autenticado. Faca login para acessar esta area.',
                ], 401);
            }

            // Redireciona para login com flash message
            FlashMessage::set('error', 'Voce precisa fazer login para acessar esta area.');
            return Response::redirect('/login');
        }

        return $next($request);
    }
}
