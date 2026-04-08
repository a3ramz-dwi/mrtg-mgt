<?php declare(strict_types=1); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">Create User</h3>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="/users/create">
  <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>

  <div class="mb-3">
    <label class="form-label">Username</label>
    <input class="form-control" name="username" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Password</label>
    <input class="form-control" type="password" name="password" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Full name</label>
    <input class="form-control" name="full_name">
  </div>

  <div class="mb-3">
    <label class="form-label">Email</label>
    <input class="form-control" name="email">
  </div>

  <div class="mb-3 form-check">
    <input class="form-check-input" type="checkbox" name="is_active" checked>
    <label class="form-check-label">Active</label>
  </div>

  <div class="mb-3">
    <label class="form-label">Roles</label>
    <?php foreach (($roles ?? []) as $r): ?>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="role_ids[]" value="<?= (int)$r['id'] ?>">
        <label class="form-check-label">
          <?= htmlspecialchars((string)$r['name']) ?>
          <span class="text-muted small"><?= htmlspecialchars((string)($r['description'] ?? '')) ?></span>
        </label>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" type="submit">Create</button>
    <a class="btn btn-outline-secondary" href="/users">Cancel</a>
  </div>
</form>