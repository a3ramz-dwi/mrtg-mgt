<?php declare(strict_types=1); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">History (Audit Trail)</h3>
</div>

<form class="row g-2 mb-3" method="get" action="/history">
  <div class="col-md-6">
    <input class="form-control" name="q" placeholder="Search event/message/entity/user" value="<?= htmlspecialchars($q) ?>">
  </div>
  <div class="col-md-2">
    <button class="btn btn-outline-secondary w-100" type="submit">Search</button>
  </div>
</form>

<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>ID</th>
        <th>Time (UTC)</th>
        <th>User</th>
        <th>Event</th>
        <th>Entity</th>
        <th>Message</th>
        <th>IP</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars((string)$r['created_at']) ?></td>
        <td><?= htmlspecialchars((string)($r['username'] ?? '-')) ?></td>
        <td><code><?= htmlspecialchars((string)$r['event_type']) ?></code></td>
        <td>
          <?= htmlspecialchars((string)($r['entity_type'] ?? '-')) ?>
          <?= $r['entity_id'] ? '#'.htmlspecialchars((string)$r['entity_id']) : '' ?>
        </td>
        <td><?= htmlspecialchars((string)$r['message']) ?></td>
        <td><?= htmlspecialchars((string)($r['ip_address'] ?? '')) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
$totalPages = (int)ceil($total / max(1, $perPage));
if ($totalPages > 1):
?>
<nav>
  <ul class="pagination pagination-sm">
    <?php for ($p=1; $p <= $totalPages; $p++): ?>
      <li class="page-item <?= $p===$page ? 'active' : '' ?>">
        <a class="page-link" href="/history?page=<?= $p ?>&q=<?= urlencode($q) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
