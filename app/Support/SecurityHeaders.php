<?php
declare(strict_types=1);

namespace App\Support;

final class SecurityHeaders
{
  public static function apply(): void
  {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
  }
}
