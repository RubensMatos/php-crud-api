<?php

declare(strict_types=1);

use App\Controllers\TaskController;
use App\Http\Request;
use App\Http\Response;
use App\Support\Router;

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Response::json(['success' => true], 200);
    exit;
}

$request = Request::fromGlobals();
$controller = new TaskController();
$router = new Router();

$router->add('GET', '/health', [$controller, 'health']);
$router->add('GET', '/api/tasks', [$controller, 'index']);
$router->add('GET', '/api/tasks/{id}', [$controller, 'show']);
$router->add('POST', '/api/tasks', [$controller, 'store']);
$router->add('PUT', '/api/tasks/{id}', [$controller, 'update']);
$router->add('DELETE', '/api/tasks/{id}', [$controller, 'destroy']);

try {
    $router->dispatch($request);
} catch (Throwable $exception) {
    Response::json([
        'success' => false,
        'error' => 'Erro interno da aplicacao.',
        'details' => $exception->getMessage(),
    ], 500);
}
