<?php declare(strict_types=1);
$device = $device ?? null;
$dataCenters = $dataCenters ?? [];
?>
<h3 class="mb-3"><?= $mode === 'edit' ? 'Edit Device' : 'Add Device' ?></h3>

<form method="post" action="<?= $mode === 'edit' ? '/devices/edit?id='.(int)$device['id'] : '/devices/create' ?>">
  <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Device Name</label>
      <input class="form-control" name="device_name" required value="<?= htmlspecialchars((string)($device['device_name'] ?? '')) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Device Type</label>
      <input class="form-control" name="device_type" value="<?= htmlspecialchars((string)($device['device_type'] ?? 'router')) ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Hostname</label>
      <input class="form-control" name="hostname" value="<?= htmlspecialchars((string)($device['hostname'] ?? '')) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">IP Address</label>
      <input class="form-control" name="ip_address" required value="<?= htmlspecialchars((string)($device['ip_address'] ?? '')) ?>">
    </div>

    <div class="col-md-4">
      <label class="form-label">Vendor</label>
      <input class="form-control" name="vendor" value="<?= htmlspecialchars((string)($device['vendor'] ?? '')) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Model</label>
      <input class="form-control" name="model" value="<?= htmlspecialchars((string)($device['model'] ?? '')) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Version</label>
      <input class="form-control" name="version" value="<?= htmlspecialchars((string)($device['version'] ?? '')) ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Data Center</label>
      <select class="form-select" name="data_center_id">
        <option value="">-- select --</option>
        <?php foreach ($dataCenters as $dc): ?>
          <option value="<?= (int)$dc['id'] ?>"
            <?= (int)($device['data_center_id'] ?? 0) === (int)$dc['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)$dc['name']) ?>
            <?= !empty($dc['location']) ? ' - ' . htmlspecialchars((string)$dc['location']) : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">SNMP Profile (v2c)</label>
      <select class="form-select" name="snmp_profile_id">
        <option value="">-- select --</option>
        <?php foreach ($snmpProfiles as $sp): ?>
          <option value="<?= (int)$sp['id'] ?>" <?= (int)($device['snmp_profile_id'] ?? 0) === (int)$sp['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($sp['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label">Status</label>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" <?= (int)($device['is_active'] ?? 1) ? 'checked' : '' ?>>
        <label class="form-check-label">Active</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_mrtg_enabled" <?= (int)($device['is_mrtg_enabled'] ?? 1) ? 'checked' : '' ?>>
        <label class="form-check-label">MRTG Enabled</label>
      </div>
    </div>

    <div class="col-12">
      <label class="form-label">Description</label>
      <textarea class="form-control" rows="3" name="description"><?= htmlspecialchars((string)($device['description'] ?? '')) ?></textarea>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit"><?= $mode === 'edit' ? 'Update' : 'Save' ?></button>
    <a class="btn btn-outline-secondary" href="/devices">Back</a>
  </div>
</form>
