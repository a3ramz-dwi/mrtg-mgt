<?php
declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class DataCenter
{
  public static function paginate(int $page, int $perPage, string $q = ''): array
  {
    $offset = max(0, ($page - 1) * $perPage);
    $pdo = DB::pdo();

    $params = [];
    $where = '';
    if ($q !== '') {
      $where = "WHERE name LIKE ? OR location LIKE ? OR category LIKE ?";
      $like = '%' . $q . '%';
      $params = [$like, $like, $like];
    }

    $countSt = $pdo->prepare("SELECT COUNT(*) FROM data_centers $where");
    $countSt->execute($params);
    $total = (int)$countSt->fetchColumn();

    $sql = "
      SELECT dc.*,
        (SELECT COUNT(*) FROM devices d WHERE d.data_center_id = dc.id) AS devices_count
      FROM data_centers dc
      $where
      ORDER BY dc.id DESC
      LIMIT $perPage OFFSET $offset
    ";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    return ['rows' => $rows, 'total' => $total];
  }

  public static function find(int $id): ?array
  {
    $st = DB::pdo()->prepare('SELECT * FROM data_centers WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function create(array $data): int
  {
    $sql = "INSERT INTO data_centers (name, category, location, description)
            VALUES (:name,:category,:location,:description)";
    $st = DB::pdo()->prepare($sql);
    $st->execute($data);
    return (int)DB::pdo()->lastInsertId();
  }

  public static function update(int $id, array $data): void
  {
    $data['id'] = $id;
    $sql = "UPDATE data_centers SET
              name=:name,
              category=:category,
              location=:location,
              description=:description
            WHERE id=:id";
    $st = DB::pdo()->prepare($sql);
    $st->execute($data);
  }

  public static function delete(int $id): void
  {
    $st = DB::pdo()->prepare('DELETE FROM data_centers WHERE id = ?');
    $st->execute([$id]);
  }

  public static function widgets(): array
  {
    $pdo = DB::pdo();

    $totalDcs = (int)$pdo->query('SELECT COUNT(*) FROM data_centers')->fetchColumn();
    $totalDevices = (int)$pdo->query('SELECT COUNT(*) FROM devices')->fetchColumn();

    $largest = $pdo->query("
      SELECT dc.id, dc.name, dc.location, COUNT(d.id) AS devices_count
      FROM data_centers dc
      LEFT JOIN devices d ON d.data_center_id = dc.id
      GROUP BY dc.id
      ORDER BY devices_count DESC, dc.name ASC
      LIMIT 1
    ")->fetch();

    $topLocation = $pdo->query("
      SELECT COALESCE(NULLIF(TRIM(location),''), '(unknown)') AS location_key,
             COUNT(*) AS dc_count
      FROM data_centers
      GROUP BY location_key
      ORDER BY dc_count DESC
      LIMIT 1
    ")->fetch();

    return [
      'total_dcs' => $totalDcs,
      'total_devices' => $totalDevices,
      'largest_dc_name' => $largest ? (string)$largest['name'] : '-',
      'largest_dc_devices' => $largest ? (int)$largest['devices_count'] : 0,
      'top_location' => $topLocation ? (string)$topLocation['location_key'] : '-',
    ];
  }

  public static function dcInfoCards(int $limit = 3): array
  {
    $pdo = DB::pdo();
    $limit = max(1, min(12, $limit));

    // Top DCs by device count, with vendor breakdown
    $sql = "
      SELECT
        dc.id, dc.name, dc.location,
        COUNT(d.id) AS devices_total,
        SUM(CASE WHEN LOWER(COALESCE(d.vendor,'')) LIKE '%mikrotik%' THEN 1 ELSE 0 END) AS devices_mikrotik,
        SUM(CASE WHEN LOWER(COALESCE(d.vendor,'')) LIKE '%linux%' THEN 1 ELSE 0 END) AS devices_linux,
        SUM(CASE WHEN LOWER(COALESCE(d.vendor,'')) NOT LIKE '%mikrotik%' AND LOWER(COALESCE(d.vendor,'')) NOT LIKE '%linux%' THEN 1 ELSE 0 END) AS devices_other
      FROM data_centers dc
      LEFT JOIN devices d ON d.data_center_id = dc.id
      GROUP BY dc.id
      ORDER BY devices_total DESC, dc.name ASC
      LIMIT {$limit}
    ";
    return $pdo->query($sql)->fetchAll();
  }

  public static function all(): array
  {
    return DB::pdo()->query('SELECT * FROM data_centers ORDER BY name')->fetchAll();
  }
}
