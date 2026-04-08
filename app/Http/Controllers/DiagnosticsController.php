<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Models\Device;
use App\Models\SnmpProfile;
use App\Services\Auth\AuthService;
use App\Services\Audit\AuditService;
use App\Services\NetTools\Ping;
use App\Services\NetTools\Traceroute;
use App\Services\Snmp\SnmpCli;
use App\Support\SnmpValidate;
use App\Support\View;

final class DiagnosticsController
{
  public function index(): void
  {
    AuthMiddleware::requireLogin();

    $deviceId = (int)($_GET['device_id'] ?? 0);
    $device = $deviceId ? Device::find($deviceId) : null;

    View::render('diagnostics/index.php', [
      'pageTitle' => 'Diagnostics',
      'devices' => Device::paginate(1, 500, '')['rows'], // MVP (same as interfaces page)
      'deviceId' => $deviceId,
      'device' => $device,
      'output' => null,
      'action' => null,
    ]);
  }

  public function ping(): void
  {
    $this->runForDevice('ping', function (array $device, ?array $sp): array {
      return Ping::run((string)$device['ip_address'], 5);
    });
  }

  public function traceroute(): void
  {
    $this->runForDevice('traceroute', function (array $device, ?array $sp): array {
      return Traceroute::run((string)$device['ip_address'], 20);
    });
  }

  public function snmpTest(): void
  {
    $this->runForDevice('snmp-test', function (array $device, ?array $sp): array {
      if (!$sp) return ['ok' => false, 'output' => 'SNMP profile not set'];
      $cli = SnmpCli::fromEnv();
      $r = $cli->testV2c((string)$device['ip_address'], (string)$sp['community']);
      return ['ok' => (bool)$r['ok'], 'output' => $r['output'] ?: ($r['ok'] ? 'OK' : 'FAILED')];
    });
  }

  public function snmpWalk(): void
  {
    $oid = trim((string)($_POST['oid'] ?? ''));
    if (!SnmpValidate::isValidOid($oid)) {
      $this->renderResult('snmp-walk', null, "Invalid OID. Use numeric OID e.g. 1.3.6.1.2.1.1");
      return;
    }

    $this->runForDevice('snmp-walk', function (array $device, ?array $sp) use ($oid): array {
      if (!$sp) return ['ok' => false, 'output' => 'SNMP profile not set'];
      $cli = SnmpCli::fromEnv();
      $r = $cli->walkV2c((string)$device['ip_address'], (string)$sp['community'], $oid);
      return ['ok' => (bool)$r['ok'], 'output' => $r['output'] ?: ($r['ok'] ? 'OK' : 'FAILED')];
    }, ['oid' => $oid]);
  }

  private function runForDevice(string $action, callable $fn, array $context = []): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $deviceId = (int)($_GET['device_id'] ?? 0);
    $device = $deviceId ? Device::find($deviceId) : null;

    if (!$device) {
      http_response_code(404);
      echo "Device not found";
      return;
    }

    $sp = $device['snmp_profile_id'] ? SnmpProfile::find((int)$device['snmp_profile_id']) : null;

    $res = $fn($device, $sp);
    $out = (string)($res['output'] ?? '');
    $ok = (bool)($res['ok'] ?? false);

    // limit output size to avoid huge page (and log spam)
    if (strlen($out) > 20000) {
      $out = substr($out, 0, 20000) . "\n... (truncated)\n";
    }

    AuditService::log(
      $auth->userId(),
      'diagnostics.' . $action,
      'Diagnostics executed',
      'device',
      $deviceId,
      array_merge([
        'device_name' => $device['device_name'] ?? null,
        'ip_address' => $device['ip_address'] ?? null,
        'ok' => $ok,
      ], $context)
    );

    $this->renderResult($action, $device, $out);
  }

  private function renderResult(string $action, ?array $device, string $output): void
  {
    $deviceId = (int)($_GET['device_id'] ?? 0);

    View::render('diagnostics/index.php', [
      'pageTitle' => 'Diagnostics',
      'devices' => Device::paginate(1, 500, '')['rows'],
      'deviceId' => $deviceId,
      'device' => $device,
      'output' => $output,
      'action' => $action,
    ]);
  }
}
