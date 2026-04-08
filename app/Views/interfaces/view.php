<?php declare(strict_types=1);

$deviceId = (int)($device['id'] ?? 0);
$ifIndex  = (int)$iface['if_index'];
$key = "dev{$deviceId}_if{$ifIndex}";

$imgDir = rtrim($_ENV['MRTG_IMG_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg/images', '/');

$periods = ['day'=>'Daily','week'=>'Weekly','month'=>'Monthly','year'=>'Yearly'];
$files = [];
foreach ($periods as $suffix => $label) {
  $fname = $key . '-' . $suffix . '.png';
  $files[$suffix] = [
    'label' => $label,
    'file' => $fname,
    'path' => $imgDir . '/' . $fname,
    'exists' => is_file($imgDir . '/' . $fname),
  ];
}
$anyExists = false;
foreach ($files as $f) { if ($f['exists']) { $anyExists = true; break; } }
?>
<h3 class="mb-1">Interface Graphs</h3>
<div class="text-muted mb-3">
  Device: <b><?= htmlspecialchars((string)($device['device_name'] ?? '')) ?></b>
  • IP: <?= htmlspecialchars((string)($device['ip_address'] ?? '')) ?>
  • Interface: <b><?= htmlspecialchars((string)$iface['if_name']) ?></b>
  (ifIndex <?= (int)$iface['if_index'] ?>)
</div>

<div class="d-flex gap-2 mb-3">
  <form method="post" action="/mrtg/build-device?device_id=<?= $deviceId ?>" target="_blank">
    <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
    <button class="btn btn-success" type="submit">Rebuild MRTG (Device)</button>
  </form>
  <a class="btn btn-outline-secondary" target="_blank" href="/mrtg/debug?device_id=<?= $deviceId ?>">Debug MRTG</a>
</div>

<?php if (!$anyExists): ?>
  <div class="alert alert-warning">
    Grafik belum muncul karena file PNG belum ada di server.<br>
    Setelah klik <b>Rebuild MRTG</b>, tunggu 5–10 menit lalu refresh.
  </div>
<?php endif; ?>

<div class="row g-3">
  <?php foreach ($files as $suffix => $f): ?>
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <span><?= htmlspecialchars($f['label']) ?> Graph</span>
          <span class="text-muted small"><?= $f['exists'] ? 'PNG: OK' : 'PNG: missing' ?></span>
        </div>
        <div class="card-body">
          <?php if ($f['exists']): ?>
            <img class="img-fluid" alt="<?= htmlspecialchars($f['label']) ?>" src="/mrtg/image?file=<?= urlencode($f['file']) ?>">
          <?php else: ?>
            <div class="text-muted">File belum ada: <code><?= htmlspecialchars($f['file']) ?></code></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="mt-3">
  <a class="btn btn-outline-secondary" href="/interfaces?device_id=<?= $deviceId ?>">Back</a>
</div>
