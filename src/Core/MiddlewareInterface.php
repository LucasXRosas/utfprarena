<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Interface para todos os Middlewares da aplicacao.
 *
 * Um middleware intercepta a requisicao ANTES de chegar ao controller,
 * podendo: bloquear, redirecionar, ou permitir a passagem para o proximo handler.
 *
 * Topico do professor: "Papel do middleware na autenticacao"
 */
interface MiddlewareInterface
{
    /**
     * Processa a requisicao.
     *
     * @param Request $request
     * @param callable(): Response $next Handler seguinte na cadeia
     * @return Response
     */
    public function handle(Request $request, callable $next): Response;
}
