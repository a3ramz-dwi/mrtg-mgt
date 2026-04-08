<?php
declare(strict_types=1);

namespace App\Services\NetTools;

use App\Support\NetValidate;

final class Ping
{
  public static function run(string $ip, int $count = 5): array
  {
    if (!NetValidate::isValidIp($ip)) {
      return ['ok' => false, 'output' => 'Invalid IP address'];
    }

    $bin = $_ENV['PING_BIN'] ?? '/bin/ping';
    $count = max(1, min(10, $count));
    $cmd = sprintf('%s -c %d %s 2>&1', escapeshellcmd($bin), $count, escapeshellarg($ip));
    $out = shell_exec($cmd) ?? '';
    $ok = str_contains($out, 'packets transmitted');
    return ['ok' => $ok, 'output' => trim($out)];
  }
}
