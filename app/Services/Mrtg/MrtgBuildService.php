<?php
declare(strict_types=1);

namespace App\Services\Mrtg;

use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\SnmpProfile;

final class MrtgBuildService
{
  /**
   * Build MRTG cfg and run mrtg/indexmaker.
   * Also primes mrtg multiple times to generate initial pngs.
   */
  public static function buildDevice(int $deviceId, int $primeRuns = 3): array
  {
    $device = Device::find($deviceId);
    if (!$device) return ['ok' => false, 'error' => 'Device not found'];

    $sp = $device['snmp_profile_id'] ? SnmpProfile::find((int)$device['snmp_profile_id']) : null;
    if (!$sp) return ['ok' => false, 'error' => 'SNMP profile not set'];

    $ifaces = DeviceInterface::byDevice($deviceId);

    $cfg = MrtgConfigGenerator::generate($device, $sp, $ifaces);

    $runs = [];
    for ($i=1; $i<=$primeRuns; $i++) {
      $runs[] = MrtgRunner::run($cfg);
    }

    $idx = MrtgRunner::indexMaker($cfg, ($_ENV['APP_NAME'] ?? 'MRTG') . ' - ' . $device['device_name']);

    $allOk = true;
    foreach ($runs as $r) $allOk = $allOk && (bool)$r['ok'];
    $allOk = $allOk && (bool)$idx['ok'];

    return [
      'ok' => $allOk,
      'cfg' => $cfg,
      'runs' => $runs,
      'index' => $idx,
    ];
  }
}
