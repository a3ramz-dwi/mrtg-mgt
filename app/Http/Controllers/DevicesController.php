<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Models\DataCenter;
use App\Models\Device;
use App\Models\SnmpProfile;
use App\Services\Auth\AuthService;
use App\Services\Audit\AuditService;
use App\Services\NetTools\Ping;
use App\Services\Snmp\SnmpCli;
use App\Support\View;

final class DevicesController
{
  public function index(): void
  {
    AuthMiddleware::requireLogin();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_ENV['PAGINATION_PER_PAGE'] ?? 20);
    $q = trim((string)($_GET['q'] ?? ''));

    $dcId = isset($_GET['dc_id']) && (string)$_GET['dc_id'] !== '' ? (int)$_GET['dc_id'] : null;
    $sort = (string)($_GET['sort'] ?? 'id_desc');

    $res = Device::paginate($page, $perPage, $q, $dcId, $sort);

    View::render('devices/index.php', [
      'pageTitle' => 'Devices Management',
      'rows' => $res['rows'],
      'total' => $res['total'],
      'page' => $page,
      'perPage' => $perPage,
      'q' => $q,
      'dcId' => $dcId,
      'sort' => $sort,
      'dataCenters' => DataCenter::all(),
    ]);
  }

  public function create(): void
  {
    AuthMiddleware::requireLogin();

    View::render('devices/form.php', [
      'pageTitle' => 'Add Device',
      'mode' => 'create',
      'device' => null,
      'snmpProfiles' => SnmpProfile::all(),
      'dataCenters' => DataCenter::all(),
    ]);
  }

  public function store(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $payload = $this->devicePayloadFromPost();
    $id = Device::create($payload);

    AuditService::log(
      $auth->userId(),
      'device.create',
      'Created device',
      'device',
      $id,
      ['device_name' => $payload['device_name'], 'ip_address' => $payload['ip_address']]
    );

    header('Location: /devices?created=' . $id);
    exit;
  }

  public function edit(): void
  {
    AuthMiddleware::requireLogin();

    $id = (int)($_GET['id'] ?? 0);
    $device = Device::find($id);
    if (!$device) {
      http_response_code(404);
      echo "Device not found";
      return;
    }

    View::render('devices/form.php', [
      'pageTitle' => 'Edit Device',
      'mode' => 'edit',
      'device' => $device,
      'snmpProfiles' => SnmpProfile::all(),
      'dataCenters' => DataCenter::all(),
    ]);
  }

  public function update(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    $payload = $this->devicePayloadFromPost();
    Device::update($id, $payload);

    AuditService::log(
      $auth->userId(),
      'device.update',
      'Updated device',
      'device',
      $id,
      ['device_name' => $payload['device_name'], 'ip_address' => $payload['ip_address']]
    );

    header('Location: /devices?updated=' . $id);
    exit;
  }

  public function delete(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    $old = Device::find($id);
    Device::delete($id);

    AuditService::log(
      $auth->userId(),
      'device.delete',
      'Deleted device',
      'device',
      $id,
      ['device_name' => $old['device_name'] ?? null, 'ip_address' => $old['ip_address'] ?? null]
    );

    header('Location: /devices?deleted=' . $id);
    exit;
  }

  public function ping(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    $device = Device::find($id);
    if (!$device) {
      http_response_code(404);
      echo "Device not found";
      return;
    }

    $res = Ping::run((string)$device['ip_address'], 5);

    AuditService::log(
      $auth->userId(),
      'device.ping',
      'Ping executed',
      'device',
      $id,
      ['ip_address' => $device['ip_address'], 'ok' => $res['ok']]
    );

    header('Content-Type: text/plain; charset=utf-8');
    echo $res['output'];
  }

  public function snmpTest(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    $device = Device::find($id);
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
    $res = $cli->testV2c((string)$device['ip_address'], (string)$sp['community']);

    AuditService::log(
      $auth->userId(),
      'device.snmp_test',
      'SNMP test executed',
      'device',
      $id,
      ['ip_address' => $device['ip_address'], 'profile' => $sp['name'] ?? null, 'ok' => $res['ok']]
    );

    header('Content-Type: text/plain; charset=utf-8');
    echo $res['output'] ?: ($res['ok'] ? 'OK' : 'FAILED');
  }

  public function snmpCheck(): void
  {
    AuthMiddleware::requireLogin();

    $id = (int)($_GET['id'] ?? 0);
    $d = \App\Models\Device::find($id);
    if (!$d) { http_response_code(404); echo "Device not found"; return; }

    if (empty($d['snmp_profile_id'])) {
      http_response_code(400);
      echo "SNMP profile not set";
      return;
    }

    $sp = \App\Models\SnmpProfile::find((int)$d['snmp_profile_id']);
    if (!$sp) { http_response_code(400); echo "SNMP profile not found"; return; }

    \App\Services\Snmp\SnmpHealthService::checkDeviceV2c(
      (int)$d['id'],
      (string)$d['ip_address'],
      (string)$sp['community']
    );

    header('Location: /devices');
    exit;
  }

  public function snmpCheckAll(): void
  {
    AuthMiddleware::requireLogin();

    $pdo = \App\Support\DB::pdo();
    $sql = "
      SELECT d.id, d.ip_address, sp.community
      FROM devices d
      JOIN snmp_profiles sp ON sp.id = d.snmp_profile_id
      WHERE d.is_active = 1
    ";
    $rows = $pdo->query($sql)->fetchAll();

    foreach ($rows as $r) {
      \App\Services\Snmp\SnmpHealthService::checkDeviceV2c(
        (int)$r['id'],
        (string)$r['ip_address'],
        (string)$r['community']
      );
    }

    header('Location: /devices');
    exit;
  }

  private function devicePayloadFromPost(): array
  {
    $toNull = fn($v) => ($v === '' || $v === null) ? null : $v;

    return [
      'device_name' => trim((string)($_POST['device_name'] ?? '')),
      'device_type' => trim((string)($_POST['device_type'] ?? 'router')),
      'hostname' => $toNull(trim((string)($_POST['hostname'] ?? ''))),
      'ip_address' => trim((string)($_POST['ip_address'] ?? '')),
      'vendor' => $toNull(trim((string)($_POST['vendor'] ?? ''))),
      'model' => $toNull(trim((string)($_POST['model'] ?? ''))),
      'version' => $toNull(trim((string)($_POST['version'] ?? ''))),
      'data_center_id' => $toNull((string)($_POST['data_center_id'] ?? '')) ? (int)$_POST['data_center_id'] : null,
      'snmp_profile_id' => $toNull((string)($_POST['snmp_profile_id'] ?? '')) ? (int)$_POST['snmp_profile_id'] : null,
      'is_mrtg_enabled' => isset($_POST['is_mrtg_enabled']) ? 1 : 0,
      'is_active' => isset($_POST['is_active']) ? 1 : 0,
      'description' => $toNull(trim((string)($_POST['description'] ?? ''))),
    ];
  }
}
