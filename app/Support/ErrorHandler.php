<?php
declare(strict_types=1);

namespace App\Support;

use Throwable;

final class ErrorHandler
{
  public static function register(): void
  {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    set_exception_handler(function (Throwable $e): void {
      http_response_code(500);
      header('Content-Type: text/plain; charset=utf-8');
      echo "Internal Server Error\n";
      self::log($e);
    });

    set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
      self::log(new \ErrorException($message, 0, $severity, $file, $line));
      return false;
    });
  }

  private static function log(Throwable $e): void
  {
    $dir = $_ENV['LOG_DIR'] ?? '/var/log/apps';
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    $file = rtrim($dir, '/') . '/mrtg-mgt-error.log';

    $msg = sprintf(
      "[%s] %s in %s:%d\n%s\n\n",
      gmdate('c'),
      $e->getMessage(),
      $e->getFile(),
      $e->getLine(),
      $e->getTraceAsString()
    );
    @file_put_contents($file, $msg, FILE_APPEND);
  }
}
