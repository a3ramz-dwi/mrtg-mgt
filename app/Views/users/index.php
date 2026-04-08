<?php declare(strict_types=1);

$qsBase = function(array $extra = []) use ($q) {
  $arr = ['q' => $q ?: null];
  foreach ($extra as $k => $v) $arr[$k] = $v;
  foreach ($arr as $k => $v) if ($v === null || $v === '') unset($arr[$k]);
  return http_build_query($arr);
};

$page = (int)($page ?? 1);
$perPage = (int)($perPage ?? 20);
$total = (int)($total ?? 0);
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">Users</h3>
  <a class="btn btn-primary" href="/users/create">Add User</a>
</div>

<form class="row g-2 mb-3" method="get" action="/users">
  <div class="col-md-10">
    <input class="form-control" name="q" placeholder="Search username/full_name/email" value="<?= htmlspecialchars($q) ?>">
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
        <th>Username</th>
        <th>Full name</th>
        <th>Email</th>
        <th>Active</th>
        <th>Roles</th>
        <th>Last login</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars((string)$r['username']) ?></td>
        <td><?= htmlspecialchars((string)($r['full_name'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($r['email'] ?? '')) ?></td>
        <td><?= (int)$r['is_active'] ? 'Yes' : 'No' ?></td>
        <td><?= htmlspecialchars(implode(', ', (array)($r['_roles'] ?? []))) ?></td>
        <td style="white-space:nowrap;"><?= htmlspecialchars((string)($r['last_login_at'] ?? '-')) ?></td>
        <td class="d-flex gap-1">
          <a class="btn btn-sm btn-outline-primary" href="/users/edit?id=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" action="/users/delete?id=<?= (int)$r['id'] ?>" onsubmit="return confirm('Delete this user?')">
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
      <li class="page-item <?= $page === $p ? 'active' : '' ?>">
        <a class="page-link" href="/users?<?= htmlspecialchars($qsBase(['page' => $p])) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>