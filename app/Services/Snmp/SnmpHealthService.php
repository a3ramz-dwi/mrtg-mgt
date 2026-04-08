<?php
declare(strict_types=1);

namespace App\Services\Snmp;

use App\Support\DB;
use App\Services\Audit\AuditService;

final class SnmpHealthService
{
  /**
   * Returns: ['ok'=>bool, 'error'=>string|null, 'output'=>string]
   */
  public static function checkDeviceV2c(int $deviceId, string $ip, string $community): array
  {
    $cli = SnmpCli::fromEnv();
    $res = $cli->testV2c($ip, $community);

    $ok = (bool)($res['ok'] ?? false);
    $out = (string)($res['output'] ?? '');
    $err = null;

    if (!$ok) {
      $err = trim($out) !== '' ? trim($out) : 'SNMP failed (unknown)';
      if (strlen($err) > 500) $err = substr($err, 0, 500);
    }

    if ($ok) {
      DB::pdo()->prepare("
        UPDATE devices
        SET snmp_last_ok = 1,
            snmp_last_checked_at = NOW(),
            snmp_last_error = NULL,
            snmp_ack_at = NULL,
            snmp_ack_by = NULL,
            snmp_ack_note = NULL
        WHERE id = ?
      ")->execute([$deviceId]);

      // 1-line audit record for timeline
      AuditService::log(null, 'alerts.auto_unack_ok', 'Auto reset SNMP ACK because SNMP is OK again', 'device', $deviceId, ['ip' => $ip]);
    } else {
      DB::pdo()->prepare("
        UPDATE devices
        SET snmp_last_ok = 0,
            snmp_last_checked_at = NOW(),
            snmp_last_error = ?
        WHERE id = ?
      ")->execute([$err, $deviceId]);
    }

    return ['ok' => $ok, 'error' => $err, 'output' => $out];
  }
}
