<?php
declare(strict_types=1);

namespace App\Services\Mrtg;

final class MrtgConfigGenerator
{
  public static function deviceCfgPath(int $deviceId): string
  {
    $dir = rtrim($_ENV['MRTG_CFG_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg/cfg', '/');
    return $dir . "/device-{$deviceId}.cfg";
  }

  public static function workDir(int $deviceId): string
  {
    $base = rtrim($_ENV['MRTG_BASE_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg', '/');
    return $base . "/work/device-{$deviceId}";
  }

  public static function ensureDirs(): void
  {
    foreach (['MRTG_CFG_DIR','MRTG_HTML_DIR','MRTG_IMG_DIR','MRTG_LOG_DIR'] as $k) {
      $d = $_ENV[$k] ?? null;
      if ($d && !is_dir($d)) @mkdir($d, 0750, true);
    }
    $workBase = rtrim($_ENV['MRTG_BASE_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg', '/') . '/work';
    if (!is_dir($workBase)) @mkdir($workBase, 0750, true);
  }

  /**
   * @param array $device row from devices
   * @param array $snmpProfile row from snmp_profiles (v2c)
   * @param array $interfaces rows from device_interfaces
   */
  public static function generate(array $device, array $snmpProfile, array $interfaces): string
  {
    self::ensureDirs();

    $workDir = self::workDir((int)$device['id']);
    if (!is_dir($workDir)) @mkdir($workDir, 0750, true);

    $cfgPath = self::deviceCfgPath((int)$device['id']);
    $community = (string)($snmpProfile['community'] ?? 'public');
    $ip = (string)$device['ip_address'];

    $global = [];
    $global[] = "WorkDir: " . ($_ENV['MRTG_IMG_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg/images');
    $global[] = "LogDir: " . ($_ENV['MRTG_LOG_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg/log');
    $global[] = "Options[_]: growright,bits";
    $global[] = "EnableIPv6: no";
    $global[] = "Language: english";
    $global[] = "Interval: 5";
    $global[] = "";

    $targets = [];
    foreach ($interfaces as $iface) {
      if (!(int)$iface['is_mrtg_enabled']) continue;

      $ifIndex = (int)$iface['if_index'];
      $key = "dev{$device['id']}_if{$ifIndex}";
      $title = $device['device_name'] . " - " . ($iface['if_name'] ?: ('if' . $ifIndex));

      $oids = self::trafficOidsForIfIndex($ifIndex);
      $hcIn = $oids['hc']['in'];
      $hcOut = $oids['hc']['out'];
      $in32 = $oids['v32']['in'];
      $out32 = $oids['v32']['out'];

      // Primary: 64-bit. Backup: 32-bit.
      $targets[] = "Target[$key]: $hcIn&$hcOut:$community@$ip:::$in32&$out32:$community@$ip";
      $targets[] = "SetEnv[$key]: MRTG_INT_IP=\"$ip\" MRTG_INT_DESCR=\"" . addslashes((string)$iface['if_descr']) . "\"";
      $targets[] = "MaxBytes[$key]: " . self::maxBytesFromSpeed($iface['if_speed_bps'] ?? null);
      $targets[] = "Title[$key]: " . $title;
      $targets[] = "PageTop[$key]: <H1>" . htmlspecialchars($title) . "</H1>";
      $targets[] = "Options[$key]: growright,bits";
      $targets[] = "YLegend[$key]: Bits per Second";
      $targets[] = "ShortLegend[$key]: b/s";
      $targets[] = "LegendI[$key]: In:";
      $targets[] = "LegendO[$key]: Out:";
      $targets[] = "";
    }

    $content = implode("\n", array_merge($global, $targets));
    file_put_contents($cfgPath, $content);
    return $cfgPath;
  }

  private static function maxBytesFromSpeed($speedBps): int
  {
    // MRTG MaxBytes expects Bytes/sec, not bits.
    // If unknown, use 12500000 (100Mbps) default to avoid flatline.
    $bps = is_numeric($speedBps) ? (int)$speedBps : 100_000_000;
    $bytes = (int)max(1, $bps / 8);
    return $bytes;
  }

  private static function trafficOidsForIfIndex(int $ifIndex): array
  {
    // 64-bit counters (IF-MIB::ifHCInOctets/ifHCOutOctets)
    $hcIn  = "1.3.6.1.2.1.31.1.1.1.6.$ifIndex";
    $hcOut = "1.3.6.1.2.1.31.1.1.1.10.$ifIndex";

    // 32-bit counters (IF-MIB::ifInOctets/ifOutOctets)
    $in32  = "1.3.6.1.2.1.2.2.1.10.$ifIndex";
    $out32 = "1.3.6.1.2.1.2.2.1.16.$ifIndex";

    return [
      'hc' => ['in' => $hcIn, 'out' => $hcOut],
      'v32' => ['in' => $in32, 'out' => $out32],
    ];
  }
}
