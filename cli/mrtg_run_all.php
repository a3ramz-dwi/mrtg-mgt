<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Bootstrap.php';

use App\Support\DB;

$cfgDir = rtrim($_ENV['MRTG_CFG_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg/cfg', '/');
$imgDir = rtrim($_ENV['MRTG_IMG_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg/images', '/');
$logDir = rtrim($_ENV['MRTG_LOG_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg/log', '/');
$htmlDir = rtrim($_ENV['MRTG_HTML_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg/html', '/');

foreach ([$cfgDir, $imgDir, $logDir, $htmlDir] as $d) {
  if (!is_dir($d)) @mkdir($d, 0750, true);
}

$cfgFiles = glob($cfgDir . '/device-*.cfg') ?: [];
if (count($cfgFiles) === 0) {
  fwrite(STDOUT, "No cfg files in {$cfgDir}\n");
  exit(0);
}

$mrtgBin = '/usr/bin/mrtg';
$indexmakerBin = '/usr/bin/indexmaker';

$okCount = 0;
$failCount = 0;

foreach ($cfgFiles as $cfg) {
  //$cmd = escapeshellcmd($mrtgBin) . ' ' . escapeshellarg($cfg) . ' 2>&1';
  $cmd = 'env LANG=C ' . escapeshellcmd($mrtgBin) . ' ' . escapeshellarg($cfg) . ' 2>&1';
  $out = shell_exec($cmd) ?? '';

  $hasError = str_contains(strtolower($out), 'error') || str_contains(strtolower($out), 'failed');
  if ($hasError) {
    $failCount++;
    fwrite(STDERR, "MRTG FAILED for {$cfg}\n{$out}\n\n");
    continue;
  }

  $okCount++;
  fwrite(STDOUT, "MRTG OK for {$cfg}\n");
}

# Rebuild a global index for convenience (optional)
$indexOut = $htmlDir . '/index.html';
//$cmd = escapeshellcmd($indexmakerBin) .
//  ' --title=' . escapeshellarg(($_ENV['APP_NAME'] ?? 'MRTG') . ' - Index') .
//  ' --output=' . escapeshellarg($indexOut) . ' ' .
//  implode(' ', array_map('escapeshellarg', $cfgFiles)) .
//  ' 2>&1';

$cmd = 'env LANG=C ' . escapeshellcmd($indexmakerBin) .
  ' --title=' . escapeshellarg(($_ENV['APP_NAME'] ?? 'MRTG') . ' - Index') .
  ' --output=' . escapeshellarg($indexOut) . ' ' .
  implode(' ', array_map('escapeshellarg', $cfgFiles)) .
  ' 2>&1';

$idx = shell_exec($cmd) ?? '';
fwrite(STDOUT, "INDEXMAKER:\n{$idx}\n");
fwrite(STDOUT, "DONE. ok={$okCount} fail={$failCount}\n");

exit($failCount > 0 ? 2 : 0);

