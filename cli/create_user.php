<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Bootstrap.php';

use App\Support\DB;

if ($argc < 4) {
  echo "Usage: php cli/create_user.php <username> <password> <role>\n";
  exit(1);
}

$username = (string)$argv[1];
$password = (string)$argv[2];
$role = (string)$argv[3];

$pdo = DB::pdo();

$hash = password_hash($password, PASSWORD_BCRYPT);

$pdo->prepare("INSERT INTO users (username, password_hash, is_active) VALUES (?, ?, 1)")
    ->execute([$username, $hash]);

$userId = (int)$pdo->lastInsertId();

$st = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
$st->execute([$role]);
$roleId = (int)($st->fetchColumn() ?: 0);

if ($roleId <= 0) {
  echo "Role not found: $role\n";
  exit(1);
}

$pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")
    ->execute([$userId, $roleId]);

echo "Created user #$userId ($username) with role $role\n";
