<?php
declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class AuditLog
{
  public static function paginate(
    int $page,
    int $perPage,
    string $q = '',
    string $eventType = '',
    ?int $deviceId = null
  ): array {
    $offset = max(0, ($page - 1) * $perPage);
    $pdo = DB::pdo();

    $where = [];
    $params = [];

    if ($q !== '') {
      $where[] = "(message LIKE ? OR event_type LIKE ? OR context_json LIKE ?)";
      $like = '%' . $q . '%';
      array_push($params, $like, $like, $like);
    }

    if ($eventType !== '') {
      $where[] = "event_type = ?";
      $params[] = $eventType;
    }

    // treat device events as entity_type='device' with entity_id=deviceId
    if ($deviceId !== null && $deviceId > 0) {
      $where[] = "(entity_type = 'device' AND entity_id = ?)";
      $params[] = $deviceId;
    }

    $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    $countSt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs $whereSql");
    $countSt->execute($params);
    $total = (int)$countSt->fetchColumn();

    $st = $pdo->prepare("
      SELECT *
      FROM audit_logs
      $whereSql
      ORDER BY id DESC
      LIMIT $perPage OFFSET $offset
    ");
    $st->execute($params);
    $rows = $st->fetchAll();

    return ['rows' => $rows, 'total' => $total];
  }

  public static function distinctEventTypes(int $limit = 200): array
  {
    $pdo = DB::pdo();
    $st = $pdo->prepare("SELECT DISTINCT event_type FROM audit_logs ORDER BY event_type ASC LIMIT ?");
    $st->execute([$limit]);
    return array_map(fn($r) => (string)$r['event_type'], $st->fetchAll());
  }
}
