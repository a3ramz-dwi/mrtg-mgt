<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Models\Device;
use App\Models\SnmpProfile;
use App\Services\Audit\AuditService;
use App\Services\Auth\AuthService;
use App\Services\Snmp\SnmpHealthService;
use App\Support\DB;
use App\Support\View;

final class AlertsController
{
  public function index(): void
  {
    AuthMiddleware::requireLogin();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_ENV['PAGINATION_PER_PAGE'] ?? 20);

    $q = trim((string)($_GET['q'] ?? ''));
    $acked = (string)($_GET['acked'] ?? ''); // '', '0', '1'

    $res = Device::paginateSnmpAlerts($page, $perPage, $q, $acked);

    View::render('alerts/index.php', [
      'pageTitle' => 'Alerts',
      'rows' => $res['rows'],
      'total' => $res['total'],
      'page' => $page,
      'perPage' => $perPage,
      'q' => $q,
      'acked' => $acked,
    ]);
  }

  public function recheck(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids)) $ids = [];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
    if (count($ids) === 0) {
      header('Location: /alerts');
      exit;
    }

    $devices = Device::findMany($ids);
    foreach ($devices as $d) {
      if (empty($d['snmp_profile_id'])) continue;
      $sp = SnmpProfile::find((int)$d['snmp_profile_id']);
      if (!$sp) continue;

      SnmpHealthService::checkDeviceV2c((int)$d['id'], (string)$d['ip_address'], (string)$sp['community']);
    }

    AuditService::log($auth->userId(), 'alerts.recheck', 'Rechecked selected devices', null, null, ['count' => count($ids)]);
    header('Location: /alerts');
    exit;
  }

  public function recheckFail(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $rows = DB::pdo()->query("
      SELECT d.id, d.ip_address, d.snmp_profile_id, sp.community
      FROM devices d
      JOIN snmp_profiles sp ON sp.id = d.snmp_profile_id
      WHERE d.is_active = 1 AND d.snmp_last_ok = 0
    ")->fetchAll();

    foreach ($rows as $r) {
      SnmpHealthService::checkDeviceV2c((int)$r['id'], (string)$r['ip_address'], (string)$r['community']);
    }

    AuditService::log($auth->userId(), 'alerts.recheck_fail', 'Rechecked all SNMP FAIL devices', null, null, ['count' => count($rows)]);
    header('Location: /alerts');
    exit;
  }

  public function ack(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $note = trim((string)($_POST['note'] ?? ''));
    if (strlen($note) > 255) $note = substr($note, 0, 255);

    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids)) $ids = [];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
    if (count($ids) === 0) {
      header('Location: /alerts');
      exit;
    }

    // ack only FAIL devices (optional restriction)
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$auth->userId(), $note], $ids);

    DB::pdo()->prepare("
      UPDATE devices
      SET snmp_ack_at = NOW(),
          snmp_ack_by = ?,
          snmp_ack_note = ?
      WHERE id IN ($placeholders)
    ")->execute($params);

    AuditService::log($auth->userId(), 'alerts.ack', 'Acknowledged alerts', null, null, ['count' => count($ids), 'note' => $note]);
    header('Location: /alerts');
    exit;
  }

  public function unack(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids)) $ids = [];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));
    if (count($ids) === 0) {
      header('Location: /alerts');
      exit;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    DB::pdo()->prepare("
      UPDATE devices
      SET snmp_ack_at = NULL,
          snmp_ack_by = NULL,
          snmp_ack_note = NULL
      WHERE id IN ($placeholders)
    ")->execute($ids);

    AuditService::log($auth->userId(), 'alerts.unack', 'Unacknowledged alerts', null, null, ['count' => count($ids)]);
    header('Location: /alerts');
    exit;
  }
}
