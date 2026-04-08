<?php
declare(strict_types=1);

namespace App\Support;

final class NetValidate
{
  public static function isValidIp(string $ip): bool
  {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
  }
}
