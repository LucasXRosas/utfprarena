<?php

namespace Core\Http;

class Request
{
    private string $method;
    private string $uri;

    /** @var mixed[] */
    private array $params;

    /** @var array<string, string> */
    private array $headers;

    /**
     * Arquivos enviados em formulários multipart/form-data ($_FILES).
     * @var array<string, array<string, mixed>>
     */
    private array $files;

    public function __construct()
    {
        $this->method = $_REQUEST['_method'] ?? $_SERVER['REQUEST_METHOD'];
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->params = $_REQUEST;
        $this->headers = function_exists('getallheaders') ? getallheaders() : [];
        $this->files = $_FILES;
    }

    /**
     * Retorna a entrada de $_FILES de um campo (name, type, tmp_name, error, size)
     * ou null quando nenhum arquivo foi enviado nesse campo.
     *
     * @return array<string, mixed>|null
     */
    public function getFile(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if (!is_array($file) || !isset($file['error'])) {
            return null;
        }

        return $file;
    }

    public function hasFile(string $key): bool
    {
        $file = $this->getFile($key);
        return $file !== null && (int) $file['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * @param array<string, array<string, mixed>> $files
     */
    public function addFiles(array $files): void
    {
        $this->files = array_merge($this->files, $files);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /** @return mixed[] */
    public function getParams(): array
    {
        return $this->params;
    }

    /** @return array<string, string>*/
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /** @param mixed[] $params*/
    public function addParams(array $params): void
    {
        $this->params = array_merge($this->params, $params);
    }

    public function acceptJson(): bool
    {
        return (isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] === 'application/json');
    }

    public function hasParam(string $key): bool
    {
        if (array_key_exists($key, $this->params)) {
            return true;
        }

        if (str_contains($key, '.')) {
            $segments = explode('.', $key);
            $current = $this->params;
            foreach ($segments as $segment) {
                if (!is_array($current) || !array_key_exists($segment, $current)) {
                    return false;
                }
                $current = $current[$segment];
            }
            return true;
        }

        return false;
    }

    /**
     * @param string[] $keys
     */
    public function hasParams(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->hasParam($key)) {
                return false;
            }
        }

        return true;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->params)) {
            return $this->params[$key];
        }

        if (str_contains($key, '.')) {
            $segments = explode('.', $key);
            $current = $this->params;
            foreach ($segments as $segment) {
                if (!is_array($current) || !array_key_exists($segment, $current)) {
                    return $default;
                }
                $current = $current[$segment];
            }
            return $current;
        }

        return $default;
    }
}
