<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Representacao orientada a objetos de uma resposta HTTP.
 */
class Response
{
    private int $statusCode;
    private string $content;
    /** @var array<string, string> */
    private array $headers;

    /**
     * @param string $content
     * @param int $statusCode
     * @param array<string, string> $headers
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * @param mixed $data
     * @param int $statusCode
     * @param array<string, string> $headers
     */
    public static function json(mixed $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return new self($json ?: '{}', $statusCode, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function html(string $html, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=utf-8';
        return new self($html, $statusCode, $headers);
    }

    /**
     * Redireciona o browser para outra URL (HTTP 302).
     * Usado apos login/logout com FlashMessage.
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
