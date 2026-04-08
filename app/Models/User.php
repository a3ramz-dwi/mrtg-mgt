<?php
declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class User
{
  public static function findByUsername(string $username): ?array
  {
    $st = DB::pdo()->prepare("SELECT * FROM users WHERE username = ?");
    $st->execute([$username]);
    $r = $st->fetch();
    return $r ?: null;
  }

  public static function find(int $id): ?array
  {
    $st = DB::pdo()->prepare("SELECT * FROM users WHERE id = ?");
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ?: null;
  }

  public static function roles(int $userId): array
  {
    $st = DB::pdo()->prepare("
      SELECT r.name
      FROM roles r
      JOIN user_roles ur ON ur.role_id = r.id
      WHERE ur.user_id = ?
      ORDER BY r.name ASC
    ");
    $st->execute([$userId]);
    return array_map(fn($x) => (string)$x['name'], $st->fetchAll());
  }
}
