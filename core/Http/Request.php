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

    public function __construct()
    {
        $this->method = $_REQUEST['_method'] ?? $_SERVER['REQUEST_METHOD'];
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->params = $_REQUEST;
        $this->headers = function_exists('getallheaders') ? getallheaders() : [];
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
