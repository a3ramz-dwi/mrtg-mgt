<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\SnmpProfile;
use App\Services\Auth\AuthService;
use App\Services\Audit\AuditService;
use App\Services\Snmp\IfDiscovery;
use App\Services\Snmp\SnmpCli;
use App\Services\Mrtg\MrtgBuildService;
use App\Support\View;
use App\Support\DB;

final class InterfacesController
{
  public function index(): void
  {
    AuthMiddleware::requireLogin();

    $devices = \App\Models\Device::paginate(1, 500, '')['rows'];

    $deviceId = (int)($_GET['device_id'] ?? 0);
    $device = $deviceId ? \App\Models\Device::find($deviceId) : null;

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_ENV['PAGINATION_PER_PAGE'] ?? 20);

    $q = trim((string)($_GET['q'] ?? ''));
    $mrtg = isset($_GET['mrtg']) && (string)$_GET['mrtg'] !== '' ? (int)$_GET['mrtg'] : null; // 1/0/null
    $sort = (string)($_GET['sort'] ?? 'ifindex_asc');

    $ifaces = [];
    $total = 0;

    if ($device) {
      $res = \App\Models\DeviceInterface::paginateByDevice($deviceId, $page, $perPage, $q, $mrtg, $sort);
      $ifaces = $res['rows'];
      $total = $res['total'];
    }

    View::render('interfaces/index.php', [
      'pageTitle' => 'Interface Management',
      'devices' => $devices,
      'deviceId' => $deviceId,
      'device' => $device,
      'ifaces' => $ifaces,
      'total' => $total,
      'page' => $page,
      'perPage' => $perPage,
      'q' => $q,
      'mrtg' => $mrtg,
      'sort' => $sort,
    ]);
  }

  public function discovery(): void
  {
    AuthMiddleware::requireLogin();

    $deviceId = (int)($_GET['device_id'] ?? 0);
    $device = Device::find($deviceId);
    if (!$device) {
      http_response_code(404);
      echo "Device not found";
      return;
    }

    $sp = $device['snmp_profile_id'] ? SnmpProfile::find((int)$device['snmp_profile_id']) : null;
    if (!$sp) {
      http_response_code(400);
      echo "SNMP profile not set";
      return;
    }

    $cli = SnmpCli::fromEnv();

    // Walk required OIDs and merge
    $walks = [
      'ifDescr' => '1.3.6.1.2.1.2.2.1.2',
      'ifName'  => '1.3.6.1.2.1.31.1.1.1.1',
      'ifAlias' => '1.3.6.1.2.1.31.1.1.1.18',
      'ifSpeed' => '1.3.6.1.2.1.2.2.1.5',
      'ifAdmin' => '1.3.6.1.2.1.2.2.1.7',
      'ifOper'  => '1.3.6.1.2.1.2.2.1.8',
    ];

    $mergedRows = [];
    foreach ($walks as $k => $oid) {
      $res = $cli->walkV2c((string)$device['ip_address'], (string)$sp['community'], $oid);
      if (!$res['ok']) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "SNMP walk failed ($k):\n" . $res['output'];
        return;
      }
      $rows = IfDiscovery::parseWalk($res['output']);
      foreach ($rows as $r) {
        $idx = (int)$r['if_index'];
        if (!isset($mergedRows[$idx])) $mergedRows[$idx] = ['if_index' => $idx];
        $mergedRows[$idx] = array_merge($mergedRows[$idx], $r);
      }
    }
    ksort($mergedRows);

    // existing ifIndex set
    $existing = [];
    foreach (DeviceInterface::byDevice($deviceId) as $e) {
      $existing[(int)$e['if_index']] = true;
    }

    View::render('interfaces/discovery.php', [
      'pageTitle' => 'Interface Discovery',
      'device' => $device,
      'rows' => array_values($mergedRows),
      'existing' => $existing,
    ]);
  }

  public function discoveryAdd(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $deviceId = (int)($_GET['device_id'] ?? 0);
    $device = Device::find($deviceId);
    if (!$device) { http_response_code(404); echo "Device not found"; return; }

    $selected = $_POST['selected'] ?? [];
    if (!is_array($selected) || count($selected) === 0) {
      header('Location: /interfaces/discovery?device_id=' . $deviceId);
      exit;
    }

    $json = (string)($_POST['payload_json'] ?? '[]');
    $all = json_decode($json, true);
    if (!is_array($all)) $all = [];

    $picked = [];
    $set = array_fill_keys(array_map('intval', $selected), true);

    foreach ($all as $r) {
      $idx = (int)($r['if_index'] ?? 0);
      if ($idx > 0 && isset($set[$idx])) {
        $picked[] = $r;
      }
    }

    $added = DeviceInterface::addMany($deviceId, $picked);

    AuditService::log(
      $auth->userId(),
      'interface.discovery_add',
      'Added interfaces from discovery',
      'device',
      $deviceId,
      ['device_name' => $device['device_name'], 'selected_count' => count($selected), 'added' => $added]
    );

    // Auto build MRTG after interface add (MVP)
    $build = MrtgBuildService::buildDevice($deviceId, 3);

    AuditService::log(
      $auth->userId(),
      'mrtg.auto_build_after_discovery',
      'Auto MRTG build after interface discovery add',
      'device',
      $deviceId,
      ['ok' => $build['ok'] ?? false, 'cfg' => $build['cfg'] ?? null]
    );

    header('Location: /interfaces?device_id=' . $deviceId);
    exit;
  }

  public function view(): void
  {
    AuthMiddleware::requireLogin();

    $id = (int)($_GET['id'] ?? 0);
    $iface = DeviceInterface::find($id);
    if (!$iface) {
      http_response_code(404);
      echo "Interface not found";
      return;
    }
    $device = Device::find((int)$iface['device_id']);

    View::render('interfaces/view.php', [
      'pageTitle' => 'Interface Graphs',
      'device' => $device,
      'iface' => $iface,
    ]);
  }

  public function toggleMrtg(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    $iface = \App\Models\DeviceInterface::find($id);
    if (!$iface) {
      http_response_code(404);
      echo "Interface not found";
      return;
    }

    $deviceId = (int)$iface['device_id'];
    $new = (int)$iface['is_mrtg_enabled'] ? 0 : 1;

    // update flag
    $st = DB::pdo()->prepare('UPDATE device_interfaces SET is_mrtg_enabled = ? WHERE id = ?');
    $st->execute([$new, $id]);

    AuditService::log(
      $auth->userId(),
      'interface.toggle_mrtg',
      $new ? 'Enabled MRTG for interface' : 'Disabled MRTG for interface',
      'device_interface',
      $id,
      ['device_id' => $deviceId, 'if_index' => (int)$iface['if_index'], 'if_name' => $iface['if_name'], 'new' => $new]
    );

    // rebuild cfg + prime run
    $build = MrtgBuildService::buildDevice($deviceId, 2);
    AuditService::log(
      $auth->userId(),
      'mrtg.rebuild_after_toggle',
      'Rebuilt MRTG config after interface toggle',
      'device',
      $deviceId,
      ['ok' => $build['ok'] ?? false, 'cfg' => $build['cfg'] ?? null]
    );

    $return = (string)($_POST['_return'] ?? '');
    if ($return !== '' && str_starts_with($return, '/interfaces')) {
      header('Location: ' . $return);
      exit;
    }

    header('Location: /interfaces?device_id=' . $deviceId);
    exit;
  }

  public function bulkMrtg(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $deviceId = (int)($_GET['device_id'] ?? 0);
    if ($deviceId <= 0) {
      http_response_code(400);
      echo "device_id required";
      return;
    }

    $mode = (string)($_POST['mode'] ?? 'enable'); // enable|disable
    $newVal = ($mode === 'disable') ? 0 : 1;

    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids)) $ids = [];

    // sanitize IDs (checkboxes from current page only)
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v) => $v > 0)));

    $return = (string)($_POST['_return'] ?? '');
    $fallback = '/interfaces?device_id=' . $deviceId;

    if (count($ids) === 0) {
      header('Location: ' . (($return !== '' && str_starts_with($return, '/interfaces')) ? $return : $fallback));
      exit;
    }

    // update only interfaces belonging to the device
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$newVal, $deviceId], $ids);

    $sql = "UPDATE device_interfaces
            SET is_mrtg_enabled = ?
            WHERE device_id = ? AND id IN ($placeholders)";
    DB::pdo()->prepare($sql)->execute($params);

    AuditService::log(
      $auth->userId(),
      'interface.bulk_mrtg',
      $newVal ? 'Bulk enabled MRTG (current page selection)' : 'Bulk disabled MRTG (current page selection)',
      'device',
      $deviceId,
      ['count' => count($ids), 'mode' => $mode]
    );

    // rebuild once + prime a couple runs (fast)
    $build = MrtgBuildService::buildDevice($deviceId, 2);
    AuditService::log(
      $auth->userId(),
      'mrtg.rebuild_after_bulk_toggle',
      'Rebuilt MRTG config after bulk interface toggle',
      'device',
      $deviceId,
      ['ok' => $build['ok'] ?? false, 'cfg' => $build['cfg'] ?? null]
    );

    if ($return !== '' && str_starts_with($return, '/interfaces')) {
      header('Location: ' . $return);
      exit;
    }

    header('Location: ' . $fallback);
    exit;
  }

  public function bulkMrtgByFilter(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $deviceId = (int)($_GET['device_id'] ?? 0);
    if ($deviceId <= 0) {
      http_response_code(400);
      header('Content-Type: text/plain; charset=utf-8');
      echo "device_id required";
      return;
    }

    $mode = (string)($_POST['mode'] ?? 'enable'); // enable|disable
    $newVal = ($mode === 'disable') ? 0 : 1;

    // filters from current UI state (posted as hidden fields)
    $q = trim((string)($_POST['q'] ?? ''));
    $mrtg = (string)($_POST['mrtg'] ?? ''); // '', '1', '0'

    $where = ["device_id = ?"];
    $params = [$deviceId];

    if ($q !== '') {
      $where[] = "(if_name LIKE ? OR if_descr LIKE ? OR if_alias LIKE ?)";
      $like = '%' . $q . '%';
      array_push($params, $like, $like, $like);
    }

    if ($mrtg === '1' || $mrtg === '0') {
      $where[] = "is_mrtg_enabled = ?";
      $params[] = (int)$mrtg;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    // Safety guard: refuse to apply "ALL filtered" action when no filter is provided
    // (prevents accidental enable/disable for the entire device).
    if ($q === '' && $mrtg === '') {
      http_response_code(400);
      header('Content-Type: text/plain; charset=utf-8');
      echo "Refusing bulk action: please set a filter (search q and/or MRTG on/off) before using ALL filtered bulk toggle.";
      return;
    }

    // Update all rows matching filter
    $sql = "UPDATE device_interfaces SET is_mrtg_enabled = ? $whereSql";
    DB::pdo()->prepare($sql)->execute(array_merge([$newVal], $params));

    // Count matched rows AFTER update (approx OK for audit; same filter should match same set)
    // This avoids DB-specific affected-rows APIs.
    $countSt = DB::pdo()->prepare("SELECT COUNT(*) FROM device_interfaces $whereSql");
    $countSt->execute($params);
    $matched = (int)$countSt->fetchColumn();

    AuditService::log(
      $auth->userId(),
      'interface.bulk_mrtg_by_filter',
      $newVal ? 'Bulk enabled MRTG by filter' : 'Bulk disabled MRTG by filter',
      'device',
      $deviceId,
      [
        'q' => $q,
        'mrtg_filter' => ($mrtg === '' ? null : (int)$mrtg),
        'matched_rows' => $matched,
        'new_value' => $newVal,
      ]
    );

    // Rebuild MRTG cfg once + prime runs
    $build = MrtgBuildService::buildDevice($deviceId, 2);

    AuditService::log(
      $auth->userId(),
      'mrtg.rebuild_after_bulk_toggle_filter',
      'Rebuilt MRTG config after bulk toggle by filter',
      'device',
      $deviceId,
      ['ok' => $build['ok'] ?? false, 'cfg' => $build['cfg'] ?? null]
    );

    $return = (string)($_POST['_return'] ?? '');
    $fallback = '/interfaces?device_id=' . $deviceId;

    if ($return !== '' && str_starts_with($return, '/interfaces')) {
      header('Location: ' . $return);
      exit;
    }

    header('Location: ' . $fallback);
    exit;
  }
}
