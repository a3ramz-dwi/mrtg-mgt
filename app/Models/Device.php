<?php
declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Device
{
  public static function paginate(int $page, int $perPage, string $q = '', ?int $dcId = null, string $sort = 'id_desc'): array
  {
    $offset = max(0, ($page - 1) * $perPage);
    $pdo = \App\Support\DB::pdo();

    $where = [];
   $params = [];

    if ($q !== '') {
      $where[] = "(d.device_name LIKE ? OR d.ip_address LIKE ? OR d.vendor LIKE ? OR d.model LIKE ?)";
      $like = '%' . $q . '%';
      array_push($params, $like, $like, $like, $like);
    }

    if ($dcId !== null && $dcId > 0) {
      $where[] = "d.data_center_id = ?";
      $params[] = $dcId;
    }

    $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    $orderBy = "d.id DESC";
    if ($sort === 'name_asc') $orderBy = "d.device_name ASC";
    if ($sort === 'name_desc') $orderBy = "d.device_name DESC";
    if ($sort === 'ip_asc') $orderBy = "INET_ATON(d.ip_address) ASC";
    if ($sort === 'ip_desc') $orderBy = "INET_ATON(d.ip_address) DESC";

    $countSql = "SELECT COUNT(*) FROM devices d $whereSql";
    $countSt = $pdo->prepare($countSql);
    $countSt->execute($params);
    $total = (int)$countSt->fetchColumn();

    $sql = "
      SELECT d.*,
        sp.name AS snmp_profile_name,
        dc.name AS data_center_name
      FROM devices d
      LEFT JOIN snmp_profiles sp ON sp.id = d.snmp_profile_id
      LEFT JOIN data_centers dc ON dc.id = d.data_center_id
      $whereSql
      ORDER BY $orderBy
      LIMIT $perPage OFFSET $offset
    ";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    return ['rows' => $rows, 'total' => $total];
  }

  public static function find(int $id): ?array
  {
    $st = DB::pdo()->prepare('SELECT * FROM devices WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function create(array $data): int
  {
    $sql = "INSERT INTO devices
      (device_name, device_type, hostname, ip_address, vendor, model, version, data_center_id, snmp_profile_id, is_mrtg_enabled, is_active, description)
      VALUES
      (:device_name,:device_type,:hostname,:ip_address,:vendor,:model,:version,:data_center_id,:snmp_profile_id,:is_mrtg_enabled,:is_active,:description)";
    $st = DB::pdo()->prepare($sql);
    $st->execute($data);
    return (int)DB::pdo()->lastInsertId();
  }

  public static function update(int $id, array $data): void
  {
    $data['id'] = $id;
    $sql = "UPDATE devices SET
      device_name=:device_name,
      device_type=:device_type,
      hostname=:hostname,
      ip_address=:ip_address,
      vendor=:vendor,
      model=:model,
      version=:version,
      data_center_id=:data_center_id,
      snmp_profile_id=:snmp_profile_id,
      is_mrtg_enabled=:is_mrtg_enabled,
      is_active=:is_active,
      description=:description
      WHERE id=:id";
    $st = DB::pdo()->prepare($sql);
    $st->execute($data);
  }

  public static function delete(int $id): void
  {
    $st = DB::pdo()->prepare('DELETE FROM devices WHERE id = ?');
    $st->execute([$id]);
  }

  public static function paginateSnmpAlerts(int $page, int $perPage, string $q = '', string $acked = ''): array
  {
    $offset = max(0, ($page - 1) * $perPage);
    $pdo = \App\Support\DB::pdo();

    $where = ["d.is_active = 1", "d.snmp_last_ok = 0"];
    $params = [];

    if ($q !== '') {
      $where[] = "(d.device_name LIKE ? OR d.ip_address LIKE ? OR d.vendor LIKE ? OR d.model LIKE ? OR d.snmp_last_error LIKE ?)";
      $like = '%' . $q . '%';
      array_push($params, $like, $like, $like, $like, $like);
    }

    if ($acked === '1') $where[] = "d.snmp_ack_at IS NOT NULL";
    if ($acked === '0') $where[] = "d.snmp_ack_at IS NULL";

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $countSt = $pdo->prepare("SELECT COUNT(*) FROM devices d $whereSql");
    $countSt->execute($params);
    $total = (int)$countSt->fetchColumn();

    $st = $pdo->prepare("
      SELECT d.*,
             sp.name AS snmp_profile_name,
             dc.name AS data_center_name
      FROM devices d
      LEFT JOIN snmp_profiles sp ON sp.id = d.snmp_profile_id
      LEFT JOIN data_centers dc ON dc.id = d.data_center_id
      $whereSql
      ORDER BY d.snmp_ack_at IS NULL DESC, d.snmp_last_checked_at DESC, d.id DESC
      LIMIT $perPage OFFSET $offset
    ");
    $st->execute($params);
    $rows = $st->fetchAll();

    return ['rows' => $rows, 'total' => $total];
  }

  public static function findMany(array $ids): array
  {
    if (count($ids) === 0) return [];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
    if (count($ids) === 0) return [];

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $st = \App\Support\DB::pdo()->prepare("SELECT * FROM devices WHERE id IN ($placeholders)");
    $st->execute($ids);
    return $st->fetchAll();
  }
}
