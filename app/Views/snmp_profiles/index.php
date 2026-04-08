<?php declare(strict_types=1); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">SNMP Profiles</h3>
  <a class="btn btn-primary" href="/snmp-profiles/create">Add Profile</a>
</div>

<form class="row g-2 mb-3" method="get" action="/snmp-profiles">
  <div class="col-md-4">
    <input class="form-control" name="q" placeholder="Search name/version/community" value="<?= htmlspecialchars($q) ?>">
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
        <th>Name</th>
        <th>Version</th>
        <th>Community</th>
        <th>Timeout (ms)</th>
        <th>Retries</th>
        <th>Created</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars((string)$r['name']) ?></td>
        <td><?= htmlspecialchars((string)$r['version']) ?></td>
        <td><?= htmlspecialchars((string)($r['community'] ?? '-')) ?></td>
        <td><?= (int)$r['timeout_ms'] ?></td>
        <td><?= (int)$r['retries'] ?></td>
        <td><?= htmlspecialchars((string)$r['created_at']) ?></td>
        <td class="d-flex gap-1">
          <a class="btn btn-sm btn-outline-primary" href="/snmp-profiles/edit?id=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" action="/snmp-profiles/delete?id=<?= (int)$r['id'] ?>" onsubmit="return confirm('Delete this SNMP profile?')">
            <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
          </form>
        </td>
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
        <a class="page-link" href="/snmp-profiles?page=<?= $p ?>&q=<?= urlencode($q) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
