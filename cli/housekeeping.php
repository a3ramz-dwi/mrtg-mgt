<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Bootstrap.php';

use App\Support\DB;

$pdo = DB::pdo();

$auditDays = (int)($_ENV['AUDIT_RETENTION_DAYS'] ?? 30);
$auditDays = max(7, min(3650, $auditDays));

$logDays = (int)($_ENV['SNMP_CHECK_LOG_RETENTION_DAYS'] ?? 14);
$logDays = max(1, min(3650, $logDays));

echo "Housekeeping start: " . date('Y-m-d H:i:s') . PHP_EOL;

// 1) audit_logs retention
$st = $pdo->prepare("DELETE FROM audit_logs WHERE created_at < (NOW() - INTERVAL ? DAY)");
$st->execute([$auditDays]);
echo "audit_logs retention: keep {$auditDays} days" . PHP_EOL;

// 2) snmp_check.log retention (simple rotate by truncating if too old / too big)
$logFile = __DIR__ . '/../storage/logs/snmp_check.log';
if (is_file($logFile)) {
  $mtime = filemtime($logFile) ?: time();
  $ageDays = (time() - $mtime) / 86400;

  $size = filesize($logFile) ?: 0;
  $maxSize = 50 * 1024 * 1024; // 50MB cap

  if ($ageDays > $logDays || $size > $maxSize) {
    $arch = __DIR__ . '/../storage/logs/snmp_check-' . date('Ymd-His') . '.log';
    @rename($logFile, $arch);
    @file_put_contents($logFile, '');
    echo "Rotated snmp_check.log -> " . basename($arch) . PHP_EOL;
  } else {
    echo "snmp_check.log OK (ageDays=" . round($ageDays, 1) . ", size=" . $size . ")" . PHP_EOL;
  }
} else {
  echo "snmp_check.log not found (skip)" . PHP_EOL;
}

echo "Housekeeping done: " . date('Y-m-d H:i:s') . PHP_EOL;
