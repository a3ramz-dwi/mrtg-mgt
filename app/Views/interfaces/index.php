<?php declare(strict_types=1);

$deviceId = (int)($deviceId ?? 0);
$q = (string)($q ?? '');
$mrtg = $mrtg ?? null; // 1/0/null
$sort = (string)($sort ?? 'ifindex_asc');

$page = (int)($page ?? 1);
$perPage = (int)($perPage ?? 20);
$total = (int)($total ?? 0);

$imgDir = rtrim($_ENV['MRTG_IMG_DIR'] ?? '/var/www/mrtg-mgt/storage/mrtg/images', '/');

$qsBase = function(array $extra = []) use ($deviceId, $q, $mrtg, $sort) {
  $arr = [
    'device_id' => $deviceId ?: null,
    'q' => $q ?: null,
    'mrtg' => ($mrtg === null ? null : (string)$mrtg),
    'sort' => $sort ?: null,
  ];
  foreach ($extra as $k => $v) $arr[$k] = $v;
  foreach ($arr as $k => $v) {
    if ($v === null || $v === '') unset($arr[$k]);
  }
  return http_build_query($arr);
};

$currentUrl = '/interfaces?' . $qsBase(['page' => $page]);

$sortLink = function(string $newSort) use ($qsBase) {
  return '/interfaces?' . $qsBase(['sort' => $newSort, 'page' => 1]);
};

$pngStatusFor = function(int $ifIndex) use ($imgDir, $deviceId): array {
  $key = "dev{$deviceId}_if{$ifIndex}";
  $periods = ['day'=>'D','week'=>'W','month'=>'M','year'=>'Y'];

  $out = [];
  foreach ($periods as $p => $short) {
    $file = $key . '-' . $p . '.png';
    $path = $imgDir . '/' . $file;
    $exists = is_file($path);
    $mtime = $exists ? @filemtime($path) : null;

    $out[$p] = [
      'short' => $short,
      'file' => $file,
      'exists' => $exists,
      'mtime' => $mtime,
    ];
  }
  return $out;
};

$fmtTime = function($ts): string {
  if (!$ts) return '';
  return date('Y-m-d H:i:s', (int)$ts);
};
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">Interface Management</h3>
</div>

<form class="row g-2 mb-3" method="get" action="/interfaces">
  <div class="col-md-5">
    <select class="form-select" name="device_id" required>
      <option value="">-- select device --</option>
      <?php foreach ($devices as $d): ?>
        <option value="<?= (int)$d['id'] ?>" <?= (int)$deviceId === (int)$d['id'] ? 'selected' : '' ?>>
          #<?= (int)$d['id'] ?> - <?= htmlspecialchars($d['device_name']) ?> (<?= htmlspecialchars($d['ip_address']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3">
    <input class="form-control" name="q" placeholder="Search name/descr/alias" value="<?= htmlspecialchars($q) ?>">
  </div>

  <div class="col-md-2">
    <select class="form-select" name="mrtg">
      <option value="" <?= $mrtg===null ? 'selected' : '' ?>>MRTG: all</option>
      <option value="1" <?= (string)$mrtg==='1' ? 'selected' : '' ?>>MRTG: on</option>
      <option value="0" <?= (string)$mrtg==='0' ? 'selected' : '' ?>>MRTG: off</option>
    </select>
  </div>

  <div class="col-md-2">
    <select class="form-select" name="sort">
      <option value="ifindex_asc" <?= $sort==='ifindex_asc'?'selected':'' ?>>ifIndex ↑</option>
      <option value="ifindex_desc" <?= $sort==='ifindex_desc'?'selected':'' ?>>ifIndex ↓</option>
      <option value="name_asc" <?= $sort==='name_asc'?'selected':'' ?>>Name A→Z</option>
      <option value="name_desc" <?= $sort==='name_desc'?'selected':'' ?>>Name Z→A</option>
      <option value="mrtg_desc" <?= $sort==='mrtg_desc'?'selected':'' ?>>MRTG on first</option>
      <option value="mrtg_asc" <?= $sort==='mrtg_asc'?'selected':'' ?>>MRTG off first</option>
    </select>
  </div>

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-outline-secondary" type="submit">Apply</button>

    <?php if ($deviceId): ?>
      <a class="btn btn-outline-primary" href="/interfaces/discovery?device_id=<?= (int)$deviceId ?>">Discovery</a>

      <form method="post" action="/mrtg/build-device?device_id=<?= (int)$deviceId ?>" target="_blank">
        <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
        <button class="btn btn-success" type="submit">Build MRTG</button>
      </form>
    <?php endif; ?>
  </div>
</form>

<?php if (!$device): ?>
  <div class="alert alert-info">Select a device then click Apply.</div>
<?php else: ?>

  <!-- Bulk action: selected checkboxes on current page -->
  <form class="d-flex flex-wrap gap-2 mb-2" id="bulkForm"
        method="post"
        action="/interfaces/bulk-mrtg?device_id=<?= (int)$deviceId ?>"
        onsubmit="return confirm('Apply MRTG change to selected interfaces on this page?')">
    <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
    <input type="hidden" name="_return" value="<?= htmlspecialchars($currentUrl) ?>">
    <input type="hidden" name="mode" id="bulkMode" value="enable">
    <div id="bulkIds"></div>

    <button class="btn btn-sm btn-success" type="submit"
            onclick="document.getElementById('bulkMode').value='enable'">
      Enable MRTG (selected)
    </button>

    <button class="btn btn-sm btn-outline-secondary" type="submit"
            onclick="document.getElementById('bulkMode').value='disable'">
      Disable MRTG (selected)
    </button>

    <span class="text-muted small align-self-center">
      Bulk selected hanya untuk interface yang dicentang di page ini.
    </span>
  </form>

  <!-- Bulk action: ALL results that match current filter (across pages) -->
  <form class="d-flex flex-wrap gap-2 mb-3"
        method="post"
        action="/interfaces/bulk-mrtg-filter?device_id=<?= (int)$deviceId ?>"
        onsubmit="return confirm('Apply MRTG change to ALL interfaces that match current filters (across all pages)?')">
    <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
    <input type="hidden" name="_return" value="<?= htmlspecialchars($currentUrl) ?>">
    <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
    <input type="hidden" name="mrtg" value="<?= htmlspecialchars($mrtg === null ? '' : (string)$mrtg) ?>">
    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

    <input type="hidden" name="mode" id="bulkFilterMode" value="enable">

    <button class="btn btn-sm btn-warning" type="submit"
            onclick="document.getElementById('bulkFilterMode').value='enable'">
      Enable MRTG (ALL filtered)
    </button>

    <button class="btn btn-sm btn-outline-warning" type="submit"
            onclick="document.getElementById('bulkFilterMode').value='disable'">
      Disable MRTG (ALL filtered)
    </button>

    <span class="text-muted small align-self-center">
      Ini menerapkan ke semua hasil filter (lintas semua page), bukan hanya yang dicentang.
    </span>
  </form>

  <div class="table-responsive">
    <table class="table table-sm table-striped align-middle">
      <thead>
        <tr>
          <th style="width:40px;">
            <input type="checkbox" id="selectAll" title="Select all on this page">
          </th>
          <th>ID</th>
          <th>
            ifIndex
            <a class="small" href="<?= htmlspecialchars($sortLink('ifindex_asc')) ?>">↑</a>
            <a class="small" href="<?= htmlspecialchars($sortLink('ifindex_desc')) ?>">↓</a>
          </th>
          <th>
            Name
            <a class="small" href="<?= htmlspecialchars($sortLink('name_asc')) ?>">↑</a>
            <a class="small" href="<?= htmlspecialchars($sortLink('name_desc')) ?>">↓</a>
          </th>
          <th>Descr</th>
          <th>Alias</th>
          <th>Speed (bps)</th>
          <th>Admin</th>
          <th>Oper</th>
          <th>PNG</th>
          <th>
            MRTG
            <a class="small" href="<?= htmlspecialchars($sortLink('mrtg_desc')) ?>">↑</a>
            <a class="small" href="<?= htmlspecialchars($sortLink('mrtg_asc')) ?>">↓</a>
          </th>
          <th>Action</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($ifaces as $r): ?>
        <?php
          $ifIndex = (int)$r['if_index'];
          $png = $pngStatusFor($ifIndex);
        ?>
        <tr>
          <td>
            <input type="checkbox" class="rowChk" value="<?= (int)$r['id'] ?>">
          </td>
          <td><?= (int)$r['id'] ?></td>
          <td><?= $ifIndex ?></td>
          <td><?= htmlspecialchars((string)$r['if_name']) ?></td>
          <td><?= htmlspecialchars((string)($r['if_descr'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($r['if_alias'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($r['if_speed_bps'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($r['admin_status'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($r['oper_status'] ?? '')) ?></td>

          <td style="white-space:nowrap;">
            <?php foreach (['day','week','month','year'] as $p): ?>
              <?php
                $it = $png[$p];
                $tip = $it['file'];
                if ($it['exists'] && $it['mtime']) {
                  $tip .= " • mtime " . $fmtTime($it['mtime']);
                }
                $href = '/mrtg/image?file=' . urlencode($it['file']);
              ?>
              <?php if ($it['exists']): ?>
                <a class="badge text-bg-success text-decoration-none"
                   target="_blank"
                   href="<?= htmlspecialchars($href) ?>"
                   title="<?= htmlspecialchars($tip) ?>">
                  <?= htmlspecialchars($it['short']) ?>
                </a>
              <?php else: ?>
                <span class="badge text-bg-secondary" title="<?= htmlspecialchars($tip) ?>">
                  <?= htmlspecialchars($it['short']) ?>
                </span>
              <?php endif; ?>
            <?php endforeach; ?>
          </td>

          <td>
            <form method="post" action="/interfaces/toggle-mrtg?id=<?= (int)$r['id'] ?>">
              <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
              <input type="hidden" name="_return" value="<?= htmlspecialchars($currentUrl) ?>">
              <?php if ((int)$r['is_mrtg_enabled']): ?>
                <button class="btn btn-sm btn-success" type="submit">On</button>
              <?php else: ?>
                <button class="btn btn-sm btn-outline-secondary" type="submit">Off</button>
              <?php endif; ?>
            </form>
          </td>

          <td>
            <a class="btn btn-sm btn-outline-primary" href="/interfaces/view?id=<?= (int)$r['id'] ?>">Graphs</a>
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
          <a class="page-link" href="/interfaces?<?= htmlspecialchars($qsBase(['page' => $p])) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>

  <script>
  (function(){
    var selectAll = document.getElementById('selectAll');
    var bulkForm = document.getElementById('bulkForm');
    var bulkIds = document.getElementById('bulkIds');

    function rowChks(){
      return Array.prototype.slice.call(document.querySelectorAll('.rowChk'));
    }

    if (selectAll) {
      selectAll.addEventListener('change', function(){
        rowChks().forEach(function(c){ c.checked = selectAll.checked; });
      });
    }

    if (bulkForm) {
      bulkForm.addEventListener('submit', function(e){
        bulkIds.innerHTML = '';

        var selected = rowChks().filter(function(c){ return c.checked; });
        if (selected.length === 0) {
          alert('No interfaces selected on this page.');
          e.preventDefault();
          return false;
        }

        selected.forEach(function(c){
          var inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = 'ids[]';
          inp.value = c.value;
          bulkIds.appendChild(inp);
        });
      });
    }
  })();
  </script>

<?php endif; ?>
