<?php declare(strict_types=1);

$qsBase = function(array $extra = []) use ($q, $eventType, $deviceId) {
  $arr = [
    'q' => $q ?: null,
    'event_type' => $eventType ?: null,
    'device_id' => ($deviceId === null ? null : (string)$deviceId),
  ];
  foreach ($extra as $k => $v) $arr[$k] = $v;
  foreach ($arr as $k => $v) if ($v === null || $v === '') unset($arr[$k]);
  return http_build_query($arr);
};
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">Event Timeline</h3>
</div>

<form class="row g-2 mb-3" method="get" action="/event-timeline">
  <div class="col-md-4">
    <input class="form-control" name="q" placeholder="Search message/type/context" value="<?= htmlspecialchars($q) ?>">
  </div>

  <div class="col-md-3">
    <select class="form-select" name="event_type">
      <option value="">-- all event types --</option>
      <?php foreach (($eventTypes ?? []) as $t): ?>
        <option value="<?= htmlspecialchars($t) ?>" <?= $eventType === $t ? 'selected' : '' ?>>
          <?= htmlspecialchars($t) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3">
    <select class="form-select" name="device_id">
      <option value="">-- all devices --</option>
      <?php foreach (($devices ?? []) as $d): ?>
        <option value="<?= (int)$d['id'] ?>" <?= (int)$deviceId === (int)$d['id'] ? 'selected' : '' ?>>
          #<?= (int)$d['id'] ?> - <?= htmlspecialchars($d['device_name']) ?> (<?= htmlspecialchars($d['ip_address']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-2">
    <button class="btn btn-outline-secondary w-100" type="submit">Apply</button>
  </div>
</form>

<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>ID</th>
        <th>Time</th>
        <th>Type</th>
        <th>Message</th>
        <th>Entity</th>
        <th>Context</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
      <?php
        $ctx = (string)($r['context_json'] ?? '');
        $ctxPretty = '';
        if ($ctx !== '') {
          $decoded = json_decode($ctx, true);
          $ctxPretty = $decoded ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $ctx;
        }
      ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td style="white-space:nowrap;"><?= htmlspecialchars((string)$r['created_at']) ?></td>
        <td><code><?= htmlspecialchars((string)$r['event_type']) ?></code></td>
        <td><?= htmlspecialchars((string)$r['message']) ?></td>
        <td>
          <?php if (!empty($r['entity_type'])): ?>
            <span class="text-muted"><?= htmlspecialchars((string)$r['entity_type']) ?>#<?= htmlspecialchars((string)$r['entity_id']) ?></span>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
        <td style="max-width: 520px;">
          <?php if ($ctxPretty !== ''): ?>
            <details>
              <summary class="small">view</summary>
              <pre class="mb-0 small" style="white-space: pre-wrap;"><?= htmlspecialchars($ctxPretty) ?></pre>
            </details>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
$totalPages = (int)ceil(($total ?? 0) / max(1, (int)$perPage));
if ($totalPages > 1):
?>
<nav>
  <ul class="pagination pagination-sm">
    <?php for ($p=1; $p <= $totalPages; $p++): ?>
      <li class="page-item <?= (int)$page === $p ? 'active' : '' ?>">
        <a class="page-link" href="/event-timeline?<?= htmlspecialchars($qsBase(['page' => $p])) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
