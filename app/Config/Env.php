<?php
declare(strict_types=1);

namespace App\Config;

final class Env
{
  private static array $data = [];
  private static bool $loaded = false;

  public static function load(string $path): void
  {
    if (self::$loaded) return;
    self::$loaded = true;

    if (!is_file($path)) {
      return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return;

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || str_starts_with($line, '#')) continue;

      $pos = strpos($line, '=');
      if ($pos === false) continue;

      $key = trim(substr($line, 0, $pos));
      $val = trim(substr($line, $pos + 1));

      if ($val !== '' && ($val[0] === '"' || $val[0] === "'")) {
        $q = $val[0];
        if (substr($val, -1) === $q) {
          $val = substr($val, 1, -1);
        }
      }

      self::$data[$key] = $val;
      $_ENV[$key] = $val;
    }
  }

  public static function get(string $key, ?string $default = null): ?string
  {
    return self::$data[$key] ?? $_ENV[$key] ?? $default;
  }
}
