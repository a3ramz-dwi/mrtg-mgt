<?php declare(strict_types=1);

$qsBase = function(array $extra = []) use ($q, $acked) {
  $arr = [
    'q' => $q ?: null,
    'acked' => $acked ?: null,
  ];
  foreach ($extra as $k => $v) $arr[$k] = $v;
  foreach ($arr as $k => $v) if ($v === null || $v === '') unset($arr[$k]);
  return http_build_query($arr);
};

$page = (int)($page ?? 1);
$perPage = (int)($perPage ?? 20);
$total = (int)($total ?? 0);
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">Alerts (SNMP FAIL)</h3>

  <form method="post" action="/alerts/recheck-fail" onsubmit="return confirm('Recheck ALL SNMP FAIL devices?')">
    <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
    <button class="btn btn-outline-success" type="submit">Recheck all FAIL</button>
  </form>
</div>

<form class="row g-2 mb-3" method="get" action="/alerts">
  <div class="col-md-6">
    <input class="form-control" name="q" placeholder="Search device/ip/error" value="<?= htmlspecialchars($q) ?>">
  </div>
  <div class="col-md-3">
    <select class="form-select" name="acked">
      <option value="" <?= $acked===''?'selected':'' ?>>Acked: all</option>
      <option value="0" <?= $acked==='0'?'selected':'' ?>>Acked: no (unacked)</option>
      <option value="1" <?= $acked==='1'?'selected':'' ?>>Acked: yes</option>
    </select>
  </div>
  <div class="col-md-3">
    <button class="btn btn-outline-secondary w-100" type="submit">Apply</button>
  </div>
</form>

<form method="post" id="bulkAlertForm" action="/alerts/ack" onsubmit="return confirm('Apply action to selected devices?')">
  <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
  <div id="bulkIds"></div>

  <div class="d-flex flex-wrap gap-2 mb-2">
    <button class="btn btn-sm btn-warning" type="submit" onclick="document.getElementById('bulkAlertForm').action='/alerts/ack'">
      Ack selected
    </button>

    <button class="btn btn-sm btn-outline-warning" type="submit" onclick="document.getElementById('bulkAlertForm').action='/alerts/unack'">
      Unack selected
    </button>

    <button class="btn btn-sm btn-outline-success" type="submit" onclick="document.getElementById('bulkAlertForm').action='/alerts/recheck'">
      Recheck selected
    </button>

    <input class="form-control form-control-sm" style="min-width:320px;"
           name="note" placeholder="Ack note (optional, max 255 chars)">
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-striped align-middle">
      <thead>
        <tr>
          <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
          <th>ID</th>
          <th>Device</th>
          <th>IP</th>
          <th>Data Center</th>
          <th>SNMP</th>
          <th>Last checked</th>
          <th>Ack</th>
          <th>Error</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($rows ?? []) as $r): ?>
        <tr>
          <td><input type="checkbox" class="rowChk" value="<?= (int)$r['id'] ?>"></td>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars((string)$r['device_name']) ?></td>
          <td><?= htmlspecialchars((string)$r['ip_address']) ?></td>
          <td><?= htmlspecialchars((string)($r['data_center_name'] ?? '-')) ?></td>
          <td><span class="badge text-bg-danger">FAIL</span></td>
          <td style="white-space:nowrap;"><?= htmlspecialchars((string)($r['snmp_last_checked_at'] ?? '-')) ?></td>
          <td style="white-space:nowrap;">
            <?php if (!empty($r['snmp_ack_at'])): ?>
              <span class="badge text-bg-warning" title="<?= htmlspecialchars((string)($r['snmp_ack_note'] ?? '')) ?>">ACK</span>
              <div class="small text-muted"><?= htmlspecialchars((string)$r['snmp_ack_at']) ?></div>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td style="max-width:520px;">
            <span title="<?= htmlspecialchars((string)($r['snmp_last_error'] ?? '')) ?>">
              <?= htmlspecialchars((string)($r['snmp_last_error'] ?? '')) ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</form>

<?php
$totalPages = (int)ceil($total / max(1, $perPage));
if ($totalPages > 1):
?>
<nav>
  <ul class="pagination pagination-sm">
    <?php for ($p=1; $p <= $totalPages; $p++): ?>
      <li class="page-item <?= $page === $p ? 'active' : '' ?>">
        <a class="page-link" href="/alerts?<?= htmlspecialchars($qsBase(['page' => $p])) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<script>
(function(){
  var selectAll = document.getElementById('selectAll');
  var bulkIds = document.getElementById('bulkIds');
  var form = document.getElementById('bulkAlertForm');

  function chks(){
    return Array.prototype.slice.call(document.querySelectorAll('.rowChk'));
  }

  if (selectAll) {
    selectAll.addEventListener('change', function(){
      chks().forEach(function(c){ c.checked = selectAll.checked; });
    });
  }

  if (form) {
    form.addEventListener('submit', function(e){
      bulkIds.innerHTML = '';
      var selected = chks().filter(function(c){ return c.checked; });

      if (selected.length === 0) {
        alert('No devices selected.');
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
