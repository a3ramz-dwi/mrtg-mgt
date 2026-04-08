<?php
declare(strict_types=1);

namespace App\Support;

final class SnmpValidate
{
  public static function isValidOid(string $oid): bool
  {
    $oid = trim($oid);
    if ($oid === '') return false;
    // numeric OID only for MVP: digits + dots, optional leading dot
    return (bool)preg_match('/^\.?\d+(\.\d+)*$/', $oid);
  }
}
