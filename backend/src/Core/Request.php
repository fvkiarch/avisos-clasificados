<?php

declare(strict_types=1);

final class Request
{
    private ?array $json = null;

    public function __construct(
        private readonly array $server,
        private readonly array $query,
        private readonly array $post,
        private readonly string $rawBody,
        private readonly array $headers,
    ) {
    }

    public static function capture(): self
    {
        $headers = [];

        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($k, 5)))] = (string) $v;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        if (!isset($headers['authorization']) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return new self($_SERVER, $_GET, $_POST, (string) file_get_contents('php://input'), $headers);
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function basePath(): string
    {
        $dir = str_replace('\\', '/', dirname($this->server['SCRIPT_NAME'] ?? ''));

        return ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
    }

    public function url(string $path = '/'): string
    {
        return $this->basePath() . $path;
    }

    public function path(): string
    {
        $uri = (string) (parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $base = $this->basePath();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        return '/' . trim(rawurldecode($uri), '/');
    }

    public function isApi(): bool
    {
        return str_starts_with($this->path(), '/api/');
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function bearerToken(): ?string
    {
        $h = $this->header('authorization');

        return ($h !== null && preg_match('/^Bearer\s+(\S+)$/i', $h, $m)) ? $m[1] : null;
    }

    public function all(): array
    {
        return str_contains((string) $this->header('content-type'), 'application/json') ? $this->json() : $this->post;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    private function json(): array
    {
        if ($this->json === null) {
            if (trim($this->rawBody) === '') {
                return $this->json = [];
            }

            try {
                $data = json_decode($this->rawBody, true, 16, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new BadRequestException('El cuerpo no es un JSON válido');
            }

            if (!is_array($data)) {
                throw new BadRequestException('El cuerpo JSON tiene que ser un objeto');
            }
            $this->json = $data;
        }

        return $this->json;
    }
}
