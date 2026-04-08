<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Support\DB;

final class RbacService
{
  /** @var array<string, bool> */
  private array $permCache = [];

  public function userHasPermission(int $userId, string $permissionName): bool
  {
    $key = $userId . ':' . $permissionName;
    if (array_key_exists($key, $this->permCache)) {
      return $this->permCache[$key];
    }

    $pdo = DB::pdo();
    $sql = "
      SELECT 1
      FROM user_roles ur
      JOIN role_permissions rp ON rp.role_id = ur.role_id
      JOIN permissions p ON p.id = rp.permission_id
      WHERE ur.user_id = ? AND p.name = ?
      LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $permissionName]);
    $ok = (bool)$stmt->fetchColumn();

    $this->permCache[$key] = $ok;
    return $ok;
  }
}
