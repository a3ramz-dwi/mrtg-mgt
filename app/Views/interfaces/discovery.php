<?php declare(strict_types=1); ?>
<h3 class="mb-3">Interface Discovery - <?= htmlspecialchars($device['device_name']) ?></h3>

<form method="post" action="/interfaces/discovery-add?device_id=<?= (int)$device['id'] ?>">
  <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>

  <div class="table-responsive">
    <table class="table table-sm table-striped align-middle">
      <thead>
        <tr>
          <th>Select</th>
          <th>ifIndex</th>
          <th>Name</th>
          <th>Descr</th>
          <th>Alias</th>
          <th>Speed (bps)</th>
          <th>Admin</th>
          <th>Oper</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <?php
            $idx = (int)$r['if_index'];
            $isExisting = isset($existing[$idx]);
          ?>
          <tr class="<?= $isExisting ? 'table-secondary' : '' ?>">
            <td>
              <?php if (!$isExisting): ?>
                <input type="checkbox" name="selected[]" value="<?= $idx ?>">
              <?php else: ?>
                <span class="text-muted">Added</span>
              <?php endif; ?>
            </td>
            <td><?= $idx ?></td>
            <td><?= htmlspecialchars((string)($r['if_name'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($r['if_descr'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($r['if_alias'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($r['if_speed_bps'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($r['admin_status'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($r['oper_status'] ?? '')) ?></td>
            <td><?= $isExisting ? 'Existing' : 'New' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <input type="hidden" name="payload_json" value="<?= htmlspecialchars(json_encode($rows, JSON_UNESCAPED_SLASHES)) ?>">

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Add Selected</button>
    <a class="btn btn-outline-secondary" href="/interfaces?device_id=<?= (int)$device['id'] ?>">Back</a>
  </div>
</form>
