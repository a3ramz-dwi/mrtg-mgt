<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\SnmpProfile;
use App\Services\Auth\AuthService;
use App\Services\Audit\AuditService;
use App\Services\Mrtg\MrtgConfigGenerator;
use App\Services\Mrtg\MrtgRunner;

final class MrtgController
{
  public function buildDevice(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $deviceId = (int)($_GET['device_id'] ?? 0);
    $device = Device::find($deviceId);
    if (!$device) { http_response_code(404); echo "Device not found"; return; }

    $sp = $device['snmp_profile_id'] ? SnmpProfile::find((int)$device['snmp_profile_id']) : null;
    if (!$sp) { http_response_code(400); echo "SNMP profile not set"; return; }

    $ifaces = DeviceInterface::byDevice($deviceId);
    $cfg = MrtgConfigGenerator::generate($device, $sp, $ifaces);

    $r1 = MrtgRunner::run($cfg);
    $r2 = MrtgRunner::indexMaker($cfg, ($_ENV['APP_NAME'] ?? 'MRTG') . ' - ' . $device['device_name']);

    AuditService::log(
      $auth->userId(),
      'mrtg.build_device',
      'Generated and executed MRTG build for device',
      'device',
      $deviceId,
      ['cfg' => $cfg, 'mrtg_ok' => $r1['ok'], 'index_ok' => $r2['ok']]
    );

    header('Content-Type: text/plain; charset=utf-8');
    echo "CFG: $cfg\n\n";
    echo "MRTG:\n" . ($r1['output'] ?: ($r1['ok'] ? 'OK' : 'FAILED')) . "\n\n";
    echo "INDEXMAKER:\n" . ($r2['output'] ?: ($r2['ok'] ? 'OK' : 'FAILED')) . "\n";
  }

  public function image(): void
  {
    AuthMiddleware::requireLogin();

    $file = (string)($_GET['file'] ?? '');
    // allow only simple filenames
    if ($file === '' || preg_match('/[^a-zA-Z0-9_\-\.]/', $file)) {
      http_response_code(400);
      echo "Invalid file";
      return;
    }

    $dir = rtrim($_ENV['MRTG_IMG_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg/images', '/');
    $path = $dir . '/' . $file;

    if (!is_file($path)) {
      http_response_code(404);
      echo "Not found";
      return;
    }

    header('Content-Type: image/png');
    header('Cache-Control: private, max-age=60');
    readfile($path);
  }

  public function debug(): void
  {
    AuthMiddleware::requireLogin();

    $deviceId = (int)($_GET['device_id'] ?? 0);
    if ($deviceId <= 0) {
      http_response_code(400);
      echo "device_id required";
      return;
    }

    $cfg = \App\Services\Mrtg\MrtgConfigGenerator::deviceCfgPath($deviceId);
    $logDir = rtrim($_ENV['MRTG_LOG_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg/log', '/');

    header('Content-Type: text/plain; charset=utf-8');

    echo "CFG: $cfg\n";
    echo "CFG exists: " . (is_file($cfg) ? 'YES' : 'NO') . "\n\n";

    // list log files
    $files = glob($logDir . '/*') ?: [];
    rsort($files);

    echo "LOG DIR: $logDir\n";
    echo "LOG FILES (top 10):\n";
    foreach (array_slice($files, 0, 10) as $f) {
      echo " - $f\n";
    }
    echo "\n";

    // also show last 200 lines of global mrtg log if exists
    $globalLog = $logDir . '/mrtg.log';
    if (is_file($globalLog)) {
      echo "=== Tail mrtg.log ===\n";
      $tail = shell_exec('tail -n 200 ' . escapeshellarg($globalLog) . ' 2>&1') ?? '';
      echo $tail . "\n";
    } else {
      echo "No global mrtg.log found (optional).\n";
    }
  }
}
