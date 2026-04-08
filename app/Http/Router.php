<?php
declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\CsrfMiddleware;

final class Router
{
  /** @var array<string, array<string, callable>> */
  private array $routes = ['GET' => [], 'POST' => []];

  public function get(string $path, callable $handler): void
  {
    $this->routes['GET'][$path] = $handler;
  }

  public function post(string $path, callable $handler): void
  {
    $this->routes['POST'][$path] = $handler;
  }

  public function dispatch(): void
  {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    $handler = $this->routes[$method][$uri] ?? null;
    if (!$handler) {
      http_response_code(404);
      header('Content-Type: text/plain; charset=utf-8');
      echo "404 Not Found";
      return;
    }

    // CSRF for all POST
    if ($method === 'POST') {
      CsrfMiddleware::verifyPost();
    }

    $handler();
  }
}
