<?php

declare(strict_types=1);

final class Router
{
    private array $routes = [];

    private string $prefix = '';

    private array $groupMiddleware = [];

    public function __construct(private readonly Container $container)
    {
    }

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function delete(string $path, array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    public function group(string $prefix, array $middleware, Closure $routes): void
    {
        [$oldPrefix, $oldMw] = [$this->prefix, $this->groupMiddleware];
        $this->prefix .= $prefix;
        $this->groupMiddleware = [...$this->groupMiddleware, ...$middleware];
        $routes($this);
        [$this->prefix, $this->groupMiddleware] = [$oldPrefix, $oldMw];
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $full = '/' . trim($this->prefix . $path, '/');
        $segments = array_map(
            fn (string $s) => preg_match('/^\{(\w+)\}$/', $s, $m) ? "(?P<{$m[1]}>[^/]+)" : preg_quote($s, '#'),
            explode('/', trim($full, '/')),
        );
        $this->routes[] = [
            'method' => $method,
            'regex' => '#^/' . implode('/', $segments) . '$#',
            'handler' => $handler,
            'middleware' => [...$this->groupMiddleware, ...$middleware],
        ];
    }

    public function dispatch(Request $request): Response
    {
        $path = $request->path();
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            $pathMatched = true;

            if ($route['method'] !== $request->method()) {
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

            return $this->runPipeline($route, $params, $request);
        }

        throw $pathMatched ? new MethodNotAllowedException() : new NotFoundException('Página no encontrada');
    }

    private function runPipeline(array $route, array $params, Request $request): Response
    {
        $next = function (Request $r) use ($route, $params): Response {
            $result = $this->container->call($route['handler'], $params);

            if (!$result instanceof Response) {
                throw new RuntimeException('Esto tiene que retornar una respuesta');
            }

            return $result;
        };

        foreach (array_reverse($route['middleware']) as $class) {
            $inner = $next;
            $next = fn (Request $r): Response => $this->container->get($class)->handle($r, $inner);
        }

        return $next($request);
    }
}
