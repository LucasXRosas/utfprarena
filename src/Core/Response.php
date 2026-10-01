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
     * @return self
     */
    public static function json(mixed $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return new self($json ?: '{}', $statusCode, $headers);
    }

    /**
     * @param string $html
     * @param int $statusCode
     * @param array<string, string> $headers
     * @return self
     */
    public static function html(string $html, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=utf-8';
        return new self($html, $statusCode, $headers);
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
}
