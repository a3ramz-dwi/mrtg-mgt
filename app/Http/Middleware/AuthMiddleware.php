<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Auth\AuthService;

final class AuthMiddleware
{
  public static function requireLogin(): void
  {
    (new AuthService())->requireLogin();
  }

  public static function requireRole(string $role): void
  {
    self::requireLogin();

    $auth = new \App\Services\Auth\AuthService();
    if (!$auth->hasRole($role)) {
      http_response_code(403);
      echo "Forbidden (requires role: {$role})";
      exit;
    }
  }
}
