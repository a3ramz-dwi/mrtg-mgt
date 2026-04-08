<?php
declare(strict_types=1);

namespace App\Services\Snmp;

final class IfDiscovery
{
  /**
   * Parse output like:
   * .1.3.6.1.2.1.2.2.1.2.1 = STRING: "lo"
   * .1.3.6.1.2.1.2.2.1.5.1 = Gauge32: 10000000
   */
  public static function parseWalk(string $walkOutput): array
  {
    $map = []; // [ifIndex => ['field' => value]]
    $lines = preg_split('/\r?\n/', trim($walkOutput)) ?: [];

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || !str_contains($line, '=')) continue;

      [$left, $right] = array_map('trim', explode('=', $line, 2));
      $oid = ltrim($left, '.');

      // Determine which base OID it matches and extract index
      $val = self::parseValue($right);

      $field = null;
      $idx = null;

      $patterns = [
        'if_descr' => '1.3.6.1.2.1.2.2.1.2.',
        'if_speed_bps' => '1.3.6.1.2.1.2.2.1.5.',
        'admin_status' => '1.3.6.1.2.1.2.2.1.7.',
        'oper_status' => '1.3.6.1.2.1.2.2.1.8.',
        'if_name' => '1.3.6.1.2.1.31.1.1.1.1.',
        'if_alias' => '1.3.6.1.2.1.31.1.1.1.18.',
      ];

      foreach ($patterns as $f => $base) {
        if (str_starts_with($oid, $base)) {
          $field = $f;
          $idx = (int)substr($oid, strlen($base));
          break;
        }
      }

      if ($field === null || $idx === null || $idx <= 0) continue;

      if (!isset($map[$idx])) $map[$idx] = ['if_index' => $idx];

      if ($field === 'admin_status' || $field === 'oper_status') {
        $map[$idx][$field] = self::statusToEnum($val);
      } elseif ($field === 'if_speed_bps') {
        $map[$idx][$field] = is_numeric($val) ? (int)$val : null;
      } else {
        $map[$idx][$field] = (string)$val;
      }
    }

    // finalize: choose if_name fallback
    $rows = [];
    ksort($map);
    foreach ($map as $idx => $row) {
      $row['if_name'] = $row['if_name'] ?? ($row['if_descr'] ?? ('if' . $idx));
      $rows[] = $row;
    }
    return $rows;
  }

  private static function parseValue(string $right): string
  {
    // Example: STRING: "ether1"
    // Example: INTEGER: 1
    // Example: Gauge32: 10000000
    $pos = strpos($right, ':');
    $val = $pos === false ? $right : trim(substr($right, $pos + 1));
    $val = trim($val);
    if (str_starts_with($val, '"') && str_ends_with($val, '"')) {
      $val = substr($val, 1, -1);
    }
    return $val;
  }

  private static function statusToEnum(string $val): string
  {
    // IF-MIB statuses: 1 up, 2 down, 3 testing, etc.
    $n = (int)preg_replace('/\D+/', '', $val);
    return match ($n) {
      1 => 'up',
      2 => 'down',
      3 => 'testing',
      4 => 'unknown',
      5 => 'dormant',
      6 => 'notPresent',
      7 => 'lowerLayerDown',
      default => 'unknown',
    };
  }
}
