<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Auth\CsrfService;

final class CsrfMiddleware
{
  public static function verifyPost(): void
  {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method !== 'POST') return;

    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!CsrfService::validate(is_string($token) ? $token : null)) {
      http_response_code(419);
      header('Content-Type: text/plain; charset=utf-8');
      echo "CSRF token mismatch (419). Please refresh and try again.";
      exit;
    }
  }
}
