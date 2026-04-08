<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Auth\AuthService;
use App\Services\Audit\AuditService;

final class AuthController
{
  public function showLogin(): void
  {
    require __DIR__ . '/../../Views/auth/login.php';
  }

  public function login(): void
  {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $auth = new AuthService();
    if ($auth->attempt($username, $password)) {
      $u = $auth->currentUser();
      AuditService::log(
        $auth->userId(),
        'auth.login',
        'User logged in',
        'user',
        $auth->userId(),
        ['username' => $u['username'] ?? $username]
      );

      header('Location: /');
      exit;
    }

    // audit failed login without user_id
    AuditService::log(null, 'auth.login_failed', 'Failed login attempt', 'user', null, ['username' => $username]);

    $error = 'Invalid username or password';
    require __DIR__ . '/../../Views/auth/login.php';
  }

  public function logout(): void
  {
    $auth = new AuthService();
    $uid = $auth->userId();
    if ($uid) {
      AuditService::log($uid, 'auth.logout', 'User logged out', 'user', $uid);
    }
    $auth->logout();
    header('Location: /login');
    exit;
  }
}
