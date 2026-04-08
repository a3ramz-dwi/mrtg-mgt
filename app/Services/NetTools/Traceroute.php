<?php
declare(strict_types=1);

namespace App\Services\NetTools;

use App\Support\NetValidate;

final class Traceroute
{
  public static function run(string $ip, int $maxHops = 20): array
  {
    if (!NetValidate::isValidIp($ip)) {
      return ['ok' => false, 'output' => 'Invalid IP address'];
    }

    $bin = $_ENV['TRACEROUTE_BIN'] ?? '/usr/bin/traceroute';
    $maxHops = max(5, min(30, $maxHops));

    // -n numeric output, -m max ttl, -w wait time (sec), -q queries per hop
    $cmd = sprintf(
      '%s -n -m %d -w 2 -q 1 %s 2>&1',
      escapeshellcmd($bin),
      $maxHops,
      escapeshellarg($ip)
    );

    $out = shell_exec($cmd) ?? '';
    $ok = trim($out) !== '';
    return ['ok' => $ok, 'output' => trim($out)];
  }
}
