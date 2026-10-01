<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Manipulador centralizado para apresentacao e tratamento de erros e excecoes.
 */
class ErrorHandler
{
    private static bool $debug = true;

    public static function register(bool $debug = true): void
    {
        self::$debug = $debug;

        error_reporting(E_ALL);
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    public static function handleError(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        self::renderResponse(500, [
            'error' => 'Internal Server Error',
            'type' => 'PHP Notice/Warning',
            'message' => self::$debug ? $message : 'Ocorreu um erro interno.',
            'file' => self::$debug ? $file : null,
            'line' => self::$debug ? $line : null,
        ]);

        return true;
    }

    public static function handleException(Throwable $exception): void
    {
        $statusCode = (int)$exception->getCode();
        if ($statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }

        self::renderResponse($statusCode, [
            'error' => 'Internal Server Error',
            'type' => get_class($exception),
            'message' => self::$debug ? $exception->getMessage() : 'Erro interno do servidor.',
            'file' => self::$debug ? $exception->getFile() : null,
            'line' => self::$debug ? $exception->getLine() : null,
            'trace' => self::$debug ? $exception->getTraceAsString() : null,
        ]);
    }

    /**
     * @param int $statusCode
     * @param array<string, mixed> $payload
     */
    private static function renderResponse(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        // Filtra campos nulos para resposta limpa em producao
        $cleanPayload = array_filter($payload, fn ($v) => $v !== null);

        echo json_encode([
            'status' => 'error',
            'code' => $statusCode,
            'details' => $cleanPayload,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        exit;
    }
}
