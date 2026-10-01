<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sistema de Roteamento MVC com suporte a Middlewares e Grupos de Rota.
 *
 * Topico do professor (rubrica 2.1):
 *   Route::middleware('auth')->group(function ($router) {
 *       $router->get('/dashboard', [DashboardController::class, 'index']);
 *   });
 *
 * Este padrao garante que todas as rotas dentro do grupo so podem ser
 * acessadas por usuarios autenticados (via AuthMiddleware).
 */
class Router
{
    /** @var array<string, array<string, array{handler: callable|array<mixed>, middlewares: string[]}>> */
    private array $routes = [];

    /** @var array<string, class-string<MiddlewareInterface>> */
    private array $middlewareMap = [
        'auth' => AuthMiddleware::class,
    ];

    /** @var string[] Middlewares ativos durante um group() */
    private array $activeMiddlewares = [];

    // =========================================================================
    // Registro de Rotas
    // =========================================================================

    /**
     * @param callable|array<mixed> $handler
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * @param callable|array<mixed> $handler
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * @param callable|array<mixed> $handler
     */
    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * @param callable|array<mixed> $handler
     */
    public function delete(string $path, callable|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    // =========================================================================
    // Grupos de Middleware
    // =========================================================================

    /**
     * Define um middleware a ser aplicado em um grupo de rotas.
     *
     * Uso (config/routes.php):
     *   $router->middleware('auth')->group(function ($router) {
     *       $router->get('/dashboard', [DashboardController::class, 'index']);
     *   });
     */
    public function middleware(string ...$names): self
    {
        $clone = clone $this;
        $clone->activeMiddlewares = array_merge($this->activeMiddlewares, $names);
        return $clone;
    }

    /**
     * Define um grupo de rotas que compartilham os mesmos middlewares.
     *
     * @param callable(self): void $callback
     */
    public function group(callable $callback): void
    {
        $callback($this);
    }

    // =========================================================================
    // Despacho de Requisicoes
    // =========================================================================

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = '/' . trim($request->getUri(), '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        $routesForMethod = $this->routes[$method] ?? [];

        // 1. Correspondencia exata
        if (isset($routesForMethod[$uri])) {
            return $this->runWithMiddlewares(
                $routesForMethod[$uri]['handler'],
                $routesForMethod[$uri]['middlewares'],
                $request,
                []
            );
        }

        // 2. Correspondencia com parametros dinamicos (ex: /users/{id})
        foreach ($routesForMethod as $routePattern => $routeData) {
            $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter($matches, fn ($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY);
                return $this->runWithMiddlewares(
                    $routeData['handler'],
                    $routeData['middlewares'],
                    $request,
                    $params
                );
            }
        }

        return Response::json([
            'status' => 'error',
            'message' => "Rota nao encontrada: {$method} {$uri}",
        ], 404);
    }

    // =========================================================================
    // Metodos Privados
    // =========================================================================

    /**
     * @param callable|array<mixed> $handler
     * @param string[] $middlewareNames
     * @param array<string, string> $params
     */
    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath !== '/') {
            $normalizedPath = rtrim($normalizedPath, '/');
        }

        $this->routes[$method][$normalizedPath] = [
            'handler' => $handler,
            'middlewares' => $this->activeMiddlewares,
        ];
    }

    /**
     * Executa o handler encadeado com os middlewares da rota.
     *
     * @param callable|array<mixed> $handler
     * @param string[] $middlewareNames
     * @param array<string, string> $params
     */
    private function runWithMiddlewares(
        callable|array $handler,
        array $middlewareNames,
        Request $request,
        array $params
    ): Response {
        // Monta a cadeia de execucao: middlewares -> controller
        $core = function (Request $req) use ($handler, $params): Response {
            return $this->executeHandler($handler, $req, $params);
        };

        // Empilha os middlewares na ordem inversa (o primeiro da lista e o mais externo)
        $chain = $core;
        foreach (array_reverse($middlewareNames) as $name) {
            $middlewareClass = $this->middlewareMap[$name] ?? null;
            if ($middlewareClass === null) {
                continue;
            }
            /** @var MiddlewareInterface $middleware */
            $middleware = new $middlewareClass();
            $next = $chain;
            $chain = fn (Request $req): Response => $middleware->handle($req, $next);
        }

        return $chain($request);
    }

    /**
     * @param callable|array<mixed> $handler
     * @param array<string, string> $params
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

    /**
     * Retorna as rotas registradas (usado nos testes de acesso).
     *
     * @return array<string, array<string, array{handler: callable|array<mixed>, middlewares: string[]}>>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
