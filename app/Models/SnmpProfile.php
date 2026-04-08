<?php
declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class SnmpProfile
{
  public static function all(): array
  {
    return DB::pdo()->query('SELECT * FROM snmp_profiles ORDER BY name')->fetchAll();
  }

  public static function paginate(int $page, int $perPage, string $q = ''): array
  {
    $offset = max(0, ($page - 1) * $perPage);
    $pdo = DB::pdo();

    $params = [];
    $where = '';
    if ($q !== '') {
      $where = "WHERE name LIKE ? OR version LIKE ? OR community LIKE ?";
      $like = '%' . $q . '%';
      $params = [$like, $like, $like];
    }

    $countSt = $pdo->prepare("SELECT COUNT(*) FROM snmp_profiles $where");
    $countSt->execute($params);
    $total = (int)$countSt->fetchColumn();

    $sql = "SELECT * FROM snmp_profiles $where ORDER BY id DESC LIMIT $perPage OFFSET $offset";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    return ['rows' => $rows, 'total' => $total];
  }

  public static function find(int $id): ?array
  {
    $st = DB::pdo()->prepare('SELECT * FROM snmp_profiles WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function create(array $data): int
  {
    $sql = "INSERT INTO snmp_profiles
      (name, version, community, v3_sec_level, v3_auth_user, v3_auth_proto, v3_auth_pass, v3_priv_proto, v3_priv_pass, timeout_ms, retries)
      VALUES
      (:name,:version,:community,:v3_sec_level,:v3_auth_user,:v3_auth_proto,:v3_auth_pass,:v3_priv_proto,:v3_priv_pass,:timeout_ms,:retries)";
    $st = DB::pdo()->prepare($sql);
    $st->execute($data);
    return (int)DB::pdo()->lastInsertId();
  }

  public static function update(int $id, array $data): void
  {
    $data['id'] = $id;
    $sql = "UPDATE snmp_profiles SET
      name=:name,
      version=:version,
      community=:community,
      v3_sec_level=:v3_sec_level,
      v3_auth_user=:v3_auth_user,
      v3_auth_proto=:v3_auth_proto,
      v3_auth_pass=:v3_auth_pass,
      v3_priv_proto=:v3_priv_proto,
      v3_priv_pass=:v3_priv_pass,
      timeout_ms=:timeout_ms,
      retries=:retries
      WHERE id=:id";
    $st = DB::pdo()->prepare($sql);
    $st->execute($data);
  }

  public static function delete(int $id): void
  {
    $st = DB::pdo()->prepare('DELETE FROM snmp_profiles WHERE id = ?');
    $st->execute([$id]);
  }
}
