<?php declare(strict_types=1); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">Diagnostics</h3>
</div>

<form class="row g-2 mb-3" method="get" action="/diagnostics">
  <div class="col-md-8">
    <select class="form-select" name="device_id" required>
      <option value="">-- select device --</option>
      <?php foreach ($devices as $d): ?>
        <option value="<?= (int)$d['id'] ?>" <?= (int)$deviceId === (int)$d['id'] ? 'selected' : '' ?>>
          #<?= (int)$d['id'] ?> - <?= htmlspecialchars($d['device_name']) ?> (<?= htmlspecialchars($d['ip_address']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <button class="btn btn-outline-secondary w-100" type="submit">Open</button>
  </div>
</form>

<?php if (!$device): ?>
  <div class="alert alert-info">Select a device first.</div>
<?php else: ?>
  <div class="card mb-3">
    <div class="card-body">
      <div class="fw-semibold"><?= htmlspecialchars((string)$device['device_name']) ?></div>
      <div class="text-muted small">
        IP: <?= htmlspecialchars((string)$device['ip_address']) ?>
        • Vendor: <?= htmlspecialchars((string)($device['vendor'] ?? '-')) ?>
        • Model: <?= htmlspecialchars((string)($device['model'] ?? '-')) ?>
      </div>

      <div class="d-flex flex-wrap gap-2 mt-3">
        <form method="post" action="/diagnostics/ping?device_id=<?= (int)$device['id'] ?>">
          <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
          <button class="btn btn-outline-primary" type="submit">Ping</button>
        </form>

        <form method="post" action="/diagnostics/traceroute?device_id=<?= (int)$device['id'] ?>">
          <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
          <button class="btn btn-outline-primary" type="submit">Traceroute</button>
        </form>

        <form method="post" action="/diagnostics/snmp-test?device_id=<?= (int)$device['id'] ?>">
          <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
          <button class="btn btn-outline-success" type="submit">SNMP Test</button>
        </form>

        <form class="d-flex gap-2" method="post" action="/diagnostics/snmp-walk?device_id=<?= (int)$device['id'] ?>">
          <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
          <input class="form-control" style="min-width: 320px;"
                 name="oid" placeholder="Numeric OID e.g. 1.3.6.1.2.1.1"
                 value="<?= htmlspecialchars((string)($_POST['oid'] ?? '1.3.6.1.2.1.1')) ?>">
          <button class="btn btn-outline-success" type="submit">SNMP Walk</button>
        </form>
      </div>
    </div>
  </div>

  <?php if ($output !== null): ?>
    <div class="card">
      <div class="card-header">
        Output<?= $action ? ' - ' . htmlspecialchars((string)$action) : '' ?>
      </div>
      <div class="card-body">
        <pre class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars((string)$output) ?></pre>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>
