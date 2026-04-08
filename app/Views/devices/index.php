<?php declare(strict_types=1);

$dcId = $dcId ?? null;
$sort = $sort ?? 'id_desc';

$qsBase = function(array $extra = []) use ($q, $dcId, $sort) {
  $arr = [
    'q' => $q,
    'dc_id' => $dcId,
    'sort' => $sort,
  ];
  foreach ($extra as $k => $v) $arr[$k] = $v;
  foreach ($arr as $k => $v) {
    if ($v === null || $v === '') unset($arr[$k]);
  }
  return http_build_query($arr);
};

$sortLink = function(string $newSort) use ($qsBase) {
  return '/devices?' . $qsBase(['sort' => $newSort, 'page' => 1]);
};

$snmpBadge = function($okVal, $checkedAt, $err) {
  if ($okVal === null) {
    return '<span class="badge text-bg-secondary" title="Never checked">NEVER</span>';
  }
  if ((int)$okVal === 1) {
    $t = $checkedAt ? ('Last: ' . $checkedAt) : 'OK';
    return '<span class="badge text-bg-success" title="'.htmlspecialchars($t).'">OK</span>';
  }
  $tip = 'FAIL';
  if ($checkedAt) $tip .= ' • Last: ' . $checkedAt;
  if ($err) $tip .= ' • ' . $err;
  return '<span class="badge text-bg-danger" title="'.htmlspecialchars($tip).'">FAIL</span>';
};
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">Devices Management</h3>
  <div class="d-flex gap-2">
    <form method="post" action="/devices/snmp-check-all" onsubmit="return confirm('Run SNMP check for ALL active devices?')">
      <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
      <button class="btn btn-outline-success" type="submit">SNMP Check All</button>
    </form>
    <a class="btn btn-primary" href="/devices/create">Add Device</a>
  </div>
</div>

<form class="row g-2 mb-3" method="get" action="/devices">
  <div class="col-md-4">
    <input class="form-control" name="q" placeholder="Search device/ip/vendor/model" value="<?= htmlspecialchars($q) ?>">
  </div>

  <div class="col-md-3">
    <select class="form-select" name="dc_id">
      <option value="">-- all data centers --</option>
      <?php foreach (($dataCenters ?? []) as $dc): ?>
        <option value="<?= (int)$dc['id'] ?>" <?= (int)$dcId === (int)$dc['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)$dc['name']) ?>
          <?= !empty($dc['location']) ? ' - ' . htmlspecialchars((string)$dc['location']) : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3">
    <select class="form-select" name="sort">
      <option value="id_desc" <?= $sort==='id_desc'?'selected':'' ?>>Newest</option>
      <option value="name_asc" <?= $sort==='name_asc'?'selected':'' ?>>Name A→Z</option>
      <option value="name_desc" <?= $sort==='name_desc'?'selected':'' ?>>Name Z→A</option>
      <option value="ip_asc" <?= $sort==='ip_asc'?'selected':'' ?>>IP Asc</option>
      <option value="ip_desc" <?= $sort==='ip_desc'?'selected':'' ?>>IP Desc</option>
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
        <th>
          Device Name<br>
          <a class="small" href="<?= htmlspecialchars($sortLink('name_asc')) ?>">↑</a>
          <a class="small" href="<?= htmlspecialchars($sortLink('name_desc')) ?>">↓</a>
        </th>
        <th>
          IP Address<br>
          <a class="small" href="<?= htmlspecialchars($sortLink('ip_asc')) ?>">↑</a>
          <a class="small" href="<?= htmlspecialchars($sortLink('ip_desc')) ?>">↓</a>
        </th>
        <th>Data Center</th>
        <th>SNMP</th>
        <th>Vendor</th>
        <th>Model</th>
        <th>SNMP Profile</th>
        <th>MRTG</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars((string)$r['device_name']) ?></td>
        <td><?= htmlspecialchars((string)$r['ip_address']) ?></td>
        <td><?= htmlspecialchars((string)($r['data_center_name'] ?? '-')) ?></td>
        <td>
          <?php
            echo $snmpBadge(
              $r['snmp_last_ok'] ?? null,
              $r['snmp_last_checked_at'] ?? null,
              $r['snmp_last_error'] ?? null
            );
          ?>
          <?php if (!empty($r['snmp_last_checked_at'])): ?>
            <div class="small text-muted"><?= htmlspecialchars((string)$r['snmp_last_checked_at']) ?></div>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars((string)($r['vendor'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($r['model'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($r['snmp_profile_name'] ?? '-')) ?></td>
        <td><?= (int)$r['is_mrtg_enabled'] ? 'Yes' : 'No' ?></td>
        <td class="d-flex gap-1">
          <form method="post" action="/devices/ping?id=<?= (int)$r['id'] ?>" target="_blank">
            <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Ping</button>
          </form>

          <form method="post" action="/devices/snmp-test?id=<?= (int)$r['id'] ?>" target="_blank">
            <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
            <button class="btn btn-sm btn-outline-secondary" type="submit">SNMP Test</button>
          </form>

          <form method="post" action="/devices/snmp-check?id=<?= (int)$r['id'] ?>">
            <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
            <button class="btn btn-sm btn-outline-success" type="submit">SNMP Check</button>
          </form>

          <a class="btn btn-sm btn-outline-primary" href="/devices/edit?id=<?= (int)$r['id'] ?>">Edit</a>

          <form method="post" action="/devices/delete?id=<?= (int)$r['id'] ?>" onsubmit="return confirm('Delete this device?')">
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
        <a class="page-link" href="/devices?<?= htmlspecialchars($qsBase(['page' => $p])) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
