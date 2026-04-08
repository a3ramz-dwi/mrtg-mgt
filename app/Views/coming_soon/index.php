<?php declare(strict_types=1); ?>
<h3 class="mb-2"><?= htmlspecialchars((string)($title ?? 'Coming soon')) ?></h3>

<div class="alert alert-info">
  Coming soon.
</div>

<div class="card">
  <div class="card-body">
    <div class="text-muted">
      <?= htmlspecialchars((string)($desc ?? 'This page is planned for a next iteration.')) ?>
    </div>

    <hr>

    <div class="small text-muted">
      If you want, tell me the exact requirements for this page and I’ll implement it next.
    </div>
  </div>
</div>
