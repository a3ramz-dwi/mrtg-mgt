<?php
declare(strict_types=1);

namespace App\Services\Auth;

final class CsrfService
{
  private const SESSION_KEY = '_csrf_token';

  public static function token(): string
  {
    if (empty($_SESSION[self::SESSION_KEY])) {
      $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION[self::SESSION_KEY];
  }

  public static function rotate(): void
  {
    $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
  }

  public static function validate(?string $token): bool
  {
    if (!$token || empty($_SESSION[self::SESSION_KEY])) return false;
    return hash_equals((string)$_SESSION[self::SESSION_KEY], (string)$token);
  }

  public static function fieldHtml(): string
  {
    $t = self::token();
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($t, ENT_QUOTES) . '">';
  }
}
