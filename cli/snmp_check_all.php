<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Bootstrap.php';

use App\Support\DB;
use App\Services\Snmp\SnmpHealthService;

$pdo = DB::pdo();

// Only active devices; must have snmp_profile_id set
$sql = "
  SELECT d.id, d.device_name, d.ip_address, sp.community
  FROM devices d
  JOIN snmp_profiles sp ON sp.id = d.snmp_profile_id
  WHERE d.is_active = 1
";
$rows = $pdo->query($sql)->fetchAll();

$okCount = 0;
$failCount = 0;

foreach ($rows as $r) {
  $id = (int)$r['id'];
  $ip = (string)$r['ip_address'];
  $comm = (string)$r['community'];

  $res = SnmpHealthService::checkDeviceV2c($id, $ip, $comm);
  if ($res['ok']) {
    $okCount++;
    echo "[OK]   #$id $ip " . ($r['device_name'] ?? '') . PHP_EOL;
  } else {
    $failCount++;
    echo "[FAIL] #$id $ip " . ($r['device_name'] ?? '') . " :: " . ($res['error'] ?? '') . PHP_EOL;
  }
}

echo "Done. OK=$okCount FAIL=$failCount TOTAL=" . count($rows) . PHP_EOL;
