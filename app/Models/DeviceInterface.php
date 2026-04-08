<?php
declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class DeviceInterface
{
  public static function byDevice(int $deviceId): array
  {
    $st = DB::pdo()->prepare('SELECT * FROM device_interfaces WHERE device_id = ? ORDER BY if_index');
    $st->execute([$deviceId]);
    return $st->fetchAll();
  }

  public static function addMany(int $deviceId, array $rows): int
  {
    $pdo = DB::pdo();
    $pdo->beginTransaction();
    $added = 0;

    $sql = "INSERT IGNORE INTO device_interfaces
      (device_id, if_index, if_name, if_descr, if_alias, if_speed_bps, admin_status, oper_status, is_mrtg_enabled)
      VALUES (?,?,?,?,?,?,?,?,?)";
    $st = $pdo->prepare($sql);

    foreach ($rows as $r) {
      $st->execute([
        $deviceId,
        (int)$r['if_index'],
        (string)$r['if_name'],
        (string)($r['if_descr'] ?? null),
        (string)($r['if_alias'] ?? null),
        $r['if_speed_bps'] !== null ? (int)$r['if_speed_bps'] : null,
        $r['admin_status'] ?? null,
        $r['oper_status'] ?? null,
        1,
      ]);
      $added += $st->rowCount() > 0 ? 1 : 0;
    }

    $pdo->commit();
    return $added;
  }

  public static function find(int $id): ?array
  {
    $st = \App\Support\DB::pdo()->prepare('SELECT * FROM device_interfaces WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function paginateByDevice(
    int $deviceId,
    int $page,
    int $perPage,
    string $q = '',
    ?int $mrtg = null,     // 1=on, 0=off, null=all
    string $sort = 'ifindex_asc'
  ): array {
    $offset = max(0, ($page - 1) * $perPage);
    $pdo = \App\Support\DB::pdo();

    $where = ["device_id = ?"];
    $params = [$deviceId];

    if ($q !== '') {
      $where[] = "(if_name LIKE ? OR if_descr LIKE ? OR if_alias LIKE ?)";
      $like = '%' . $q . '%';
      array_push($params, $like, $like, $like);
    }

    if ($mrtg !== null) {
      $where[] = "is_mrtg_enabled = ?";
      $params[] = (int)$mrtg;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $orderBy = "if_index ASC";
    if ($sort === 'ifindex_desc') $orderBy = "if_index DESC";
    if ($sort === 'name_asc') $orderBy = "if_name ASC";
    if ($sort === 'name_desc') $orderBy = "if_name DESC";
    if ($sort === 'mrtg_desc') $orderBy = "is_mrtg_enabled DESC, if_index ASC";
    if ($sort === 'mrtg_asc') $orderBy = "is_mrtg_enabled ASC, if_index ASC";

    $countSt = $pdo->prepare("SELECT COUNT(*) FROM device_interfaces $whereSql");
    $countSt->execute($params);
    $total = (int)$countSt->fetchColumn();

    $sql = "
      SELECT *
      FROM device_interfaces
      $whereSql
      ORDER BY $orderBy
      LIMIT $perPage OFFSET $offset
    ";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    return ['rows' => $rows, 'total' => $total];
  }
}
