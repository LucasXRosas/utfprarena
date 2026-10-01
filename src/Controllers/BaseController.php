<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;

/**
 * Controlador Base para utilitarios de resposta em JSON ou Views.
 */
abstract class BaseController
{
    /**
     * @param mixed $data
     * @param int $statusCode
     * @return Response
     */
    protected function json(mixed $data, int $statusCode = 200): Response
    {
        return Response::json($data, $statusCode);
    }

    /**
     * @param string $viewPath
     * @param array<string, mixed> $data
     * @param int $statusCode
     * @return Response
     */
    protected function view(string $viewPath, array $data = [], int $statusCode = 200): Response
    {
        extract($data);
        ob_start();
        $file = __DIR__ . '/../Views/' . ltrim($viewPath, '/') . '.php';

        if (!file_exists($file)) {
            return Response::html("<h1>View {$viewPath} nao encontrada</h1>", 404);
        }

        require $file;
        $html = (string)ob_get_clean();

        return Response::html($html, $statusCode);
    }
}
