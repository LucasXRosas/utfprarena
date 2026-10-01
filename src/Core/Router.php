<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sistema de Roteamento MVC da aplicacao.
 */
class Router
{
    /** @var array<string, array<string, mixed>> */
    private array $routes = [];

    /**
     * @param string $path
     * @param callable|array{class-string, string} $handler
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * @param string $path
     * @param callable|array{class-string, string} $handler
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * @param string $path
     * @param callable|array{class-string, string} $handler
     */
    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * @param string $path
     * @param callable|array{class-string, string} $handler
     */
    public function delete(string $path, callable|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * @param string $method
     * @param string $path
     * @param callable|array{class-string, string} $handler
     */
    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath !== '/') {
            $normalizedPath = rtrim($normalizedPath, '/');
        }

        $this->routes[$method][$normalizedPath] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = '/' . trim($request->getUri(), '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        $routesForMethod = $this->routes[$method] ?? [];

        // 1. Tenta correspondencia exata
        if (isset($routesForMethod[$uri])) {
            return $this->executeHandler($routesForMethod[$uri], $request, []);
        }

        // 2. Tenta correspondencia com parametros dinamicos (ex: /users/{id})
        foreach ($routesForMethod as $routePattern => $handler) {
            $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter($matches, fn ($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY);
                return $this->executeHandler($handler, $request, $params);
            }
        }

        // Rota nao encontrada
        return Response::json([
            'status' => 'error',
            'message' => "Rota nao encontrada para {$method} {$uri}"
        ], 404);
    }

    /**
     * @param callable|array{class-string, string} $handler
     * @param Request $request
     * @param array<string, string> $params
     * @return Response
     */
    private function executeHandler(callable|array $handler, Request $request, array $params): Response
    {
        if (is_array($handler)) {
            [$controllerClass, $action] = $handler;
            $controller = new $controllerClass();
            /** @var Response $response */
            $response = $controller->$action($request, $params);
            return $response;
        }

        /** @var Response $response */
        $response = $handler($request, $params);
        return $response;
    }
}
