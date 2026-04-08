<?php declare(strict_types=1); ?>
<div class="border-end bg-white" style="width: 280px; min-height: 100vh;">
  <div class="p-3 border-bottom">
    <div class="fw-bold"><?= htmlspecialchars($_ENV['APP_NAME'] ?? 'MRTG GUI') ?></div>
    <div class="text-muted small"><?= htmlspecialchars($_ENV['APP_URL'] ?? '') ?></div>
  </div>

  <div class="p-2">
    <div class="text-uppercase text-muted small px-2 mt-2 mb-1">Overview</div>
    <a class="btn btn-sm w-100 text-start" href="/">Dashboard</a>

    <div class="text-uppercase text-muted small px-2 mt-3 mb-1">Infrastructure</div>
    <a class="btn btn-sm w-100 text-start" href="/devices">Devices Management</a>
    <a class="btn btn-sm w-100 text-start" href="/interfaces">Interface Management</a>
    <a class="btn btn-sm w-100 text-start" href="/data-centers">Data Center</a>
    <a class="btn btn-sm w-100 text-start" href="/snmp-profiles">SNMP Profiles</a>

    <div class="text-uppercase text-muted small px-2 mt-3 mb-1">System</div>
    <a class="btn btn-sm w-100 text-start" href="/event-timeline">Event Timeline</a>
    <a class="btn btn-sm w-100 text-start" href="/alerts">Alerts</a>
    <a class="btn btn-sm w-100 text-start" href="/diagnostics">Diagnostics</a>
    <a class="btn btn-sm w-100 text-start" href="/history">History</a>
    <a class="btn btn-sm w-100 text-start" href="/settings">Settings</a>

    <div class="text-uppercase text-muted small px-2 mt-3 mb-1">Users</div>
    <a class="btn btn-sm w-100 text-start" href="/users">Users</a>
    <a class="btn btn-sm w-100 text-start" href="/roles">Roles</a>

    <hr>
    <form method="post" action="/logout" class="px-2">
      <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
      <button class="btn btn-outline-danger btn-sm w-100" type="submit">Logout</button>
    </form>
  </div>
</div>
