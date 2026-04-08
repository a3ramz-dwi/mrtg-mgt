<?php
declare(strict_types=1);

namespace App\Services\Mrtg;

final class MrtgRunner
{
  public static function run(string $cfgPath): array
  {
    $mrtgBin = '/usr/bin/mrtg';
    $cmd = sprintf('env LANG=C %s %s 2>&1', escapeshellcmd($mrtgBin), escapeshellarg($cfgPath));
    $out = shell_exec($cmd) ?? '';
    $ok = !str_contains(strtolower($out), 'error');
    return ['ok' => $ok, 'output' => trim($out)];
  }

  public static function indexMaker(string $cfgPath, string $title = 'MRTG Index'): array
  {
    $indexmaker = '/usr/bin/indexmaker';
    $htmlDir = rtrim($_ENV['MRTG_HTML_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg/html', '/');
    if (!is_dir($htmlDir)) @mkdir($htmlDir, 0750, true);

    $outFile = $htmlDir . '/index.html';
    $cmd = sprintf(
      'env LANG=C %s --title=%s --output=%s %s 2>&1',
      escapeshellcmd($indexmaker),
      escapeshellarg($title),
      escapeshellarg($outFile),
      escapeshellarg($cfgPath)
    );
    $out = shell_exec($cmd) ?? '';
    $ok = is_file($outFile);
    return ['ok' => $ok, 'output' => trim($out), 'file' => $outFile];
  }
}
