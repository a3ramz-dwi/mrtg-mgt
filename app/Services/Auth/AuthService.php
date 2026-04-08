<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Support\DB;

final class AuthService
{
  public function attempt(string $username, string $password): bool
  {
    $pdo = DB::pdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) return false;
    if (!password_verify($password, $user['password_hash'])) return false;

    $_SESSION['user_id'] = (int)$user['id'];
    session_regenerate_id(true);

    $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);
    return true;
  }

  public function logout(): void
  {
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
  }

  public function userId(): ?int
  {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
  }

  public function requireLogin(): void
  {
    if (!$this->userId()) {
      header('Location: /login');
      exit;
    }
  }

  public function currentUser(): ?array
  {
    $uid = $this->userId();
    if (!$uid) return null;
    $st = DB::pdo()->prepare('SELECT id, username, full_name, email FROM users WHERE id = ? LIMIT 1');
    $st->execute([$uid]);
    $u = $st->fetch();
    return $u ?: null;
  }

  public function userRoles(): array
  {
    $uid = $this->userId();
    if (!$uid) return [];
    return \App\Models\User::roles((int)$uid);
  }

  public function hasRole(string $role): bool
  {
    return in_array($role, $this->userRoles(), true);
  }
}
