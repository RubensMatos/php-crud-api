<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Request;
use App\Http\Response;

final class Router
{
    /** @var array<int, array{method: string, pattern: string, handler: callable}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['pattern']);
            $regex = '#^' . $pattern . '$#';
            if ($regex === '#^$#') {
                continue;
            }

            if (preg_match($regex, $request->path, $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $params[$key] = $value;
                }
            }

            call_user_func($route['handler'], $request, $params);
            return;
        }

        Response::json([
            'success' => false,
            'error' => 'Rota nao encontrada.',
        ], 404);
    }
}
