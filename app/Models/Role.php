<?php
declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Role
{
  public static function all(): array
  {
    return DB::pdo()->query("SELECT * FROM roles ORDER BY name ASC")->fetchAll();
  }

  public static function find(int $id): ?array
  {
    $st = DB::pdo()->prepare("SELECT * FROM roles WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ?: null;
  }

  public static function create(string $name, string $description = ''): int
  {
    DB::pdo()->prepare("INSERT INTO roles (name, description) VALUES (?, ?)")
      ->execute([$name, $description !== '' ? $description : null]);
    return (int)DB::pdo()->lastInsertId();
  }

  public static function update(int $id, string $name, string $description = ''): void
  {
    DB::pdo()->prepare("UPDATE roles SET name = ?, description = ? WHERE id = ?")
      ->execute([$name, $description !== '' ? $description : null, $id]);
  }

  public static function delete(int $id): void
  {
    DB::pdo()->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);
  }
}