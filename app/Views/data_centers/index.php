<?php declare(strict_types=1); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">Data Center</h3>
  <a class="btn btn-primary" href="/data-centers/create">Add Data Center</a>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card"><div class="card-body">
      <div class="text-muted small">Total DCs</div>
      <div class="fs-4"><?= (int)$widgets['total_dcs'] ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card"><div class="card-body">
      <div class="text-muted small">Total Devices</div>
      <div class="fs-4"><?= (int)$widgets['total_devices'] ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card"><div class="card-body">
      <div class="text-muted small">Largest DC</div>
      <div class="fs-6 fw-semibold"><?= htmlspecialchars((string)$widgets['largest_dc_name']) ?></div>
      <div class="text-muted small"><?= (int)$widgets['largest_dc_devices'] ?> devices</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card"><div class="card-body">
      <div class="text-muted small">Top Location</div>
      <div class="fs-6 fw-semibold"><?= htmlspecialchars((string)$widgets['top_location']) ?></div>
    </div></div>
  </div>
</div>

<div class="row g-3 mb-3">
  <?php foreach ($infoCards as $c): ?>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="fw-semibold"><?= htmlspecialchars((string)$c['name']) ?></div>
          <div class="text-muted small mb-2"><?= htmlspecialchars((string)($c['location'] ?? '-')) ?></div>
          <div class="row g-2">
            <div class="col-6">
              <div class="text-muted small">Devices</div>
              <div class="fw-semibold"><?= (int)$c['devices_total'] ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted small">MikroTik</div>
              <div class="fw-semibold"><?= (int)$c['devices_mikrotik'] ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Linux</div>
              <div class="fw-semibold"><?= (int)$c['devices_linux'] ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Other</div>
              <div class="fw-semibold"><?= (int)$c['devices_other'] ?></div>
            </div>
          </div>
        </div>
        <div class="card-footer bg-white">
          <a class="btn btn-sm btn-outline-primary" href="/data-centers/edit?id=<?= (int)$c['id'] ?>">Edit</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<form class="row g-2 mb-3" method="get" action="/data-centers">
  <div class="col-md-4">
    <input class="form-control" name="q" placeholder="Search name/location/category" value="<?= htmlspecialchars($q) ?>">
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
        <th>Category</th>
        <th>Location</th>
        <th>Devices</th>
        <th>Created</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars((string)$r['name']) ?></td>
        <td><?= htmlspecialchars((string)$r['category']) ?></td>
        <td><?= htmlspecialchars((string)($r['location'] ?? '')) ?></td>
        <td><?= (int)$r['devices_count'] ?></td>
        <td><?= htmlspecialchars((string)$r['created_at']) ?></td>
        <td class="d-flex gap-1">
          <a class="btn btn-sm btn-outline-primary" href="/data-centers/edit?id=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" action="/data-centers/delete?id=<?= (int)$r['id'] ?>" onsubmit="return confirm('Delete this data center?')">
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
        <a class="page-link" href="/data-centers?page=<?= $p ?>&q=<?= urlencode($q) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
