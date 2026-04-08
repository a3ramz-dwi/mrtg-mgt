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
    $st = DB::pdo()->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ?: null;
  }

  public static function paginate(int $page, int $perPage, string $q = ''): array
  {
    $offset = max(0, ($page - 1) * $perPage);
    $pdo = DB::pdo();

    $where = [];
    $params = [];
    if ($q !== '') {
      $where[] = "(username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
      $like = '%' . $q . '%';
      array_push($params, $like, $like, $like);
    }
    $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    $countSt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
    $countSt->execute($params);
    $total = (int)$countSt->fetchColumn();

    $st = $pdo->prepare("
      SELECT id, username, full_name, email, is_active, last_login_at, created_at
      FROM users
      $whereSql
      ORDER BY id DESC
      LIMIT $perPage OFFSET $offset
    ");
    $st->execute($params);
    $rows = $st->fetchAll();

    return ['rows' => $rows, 'total' => $total];
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

  public static function setRoles(int $userId, array $roleIds): void
  {
    $pdo = DB::pdo();
    $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$userId]);

    $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds), fn($v) => $v > 0)));
    foreach ($roleIds as $rid) {
      $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([$userId, $rid]);
    }
  }
}
