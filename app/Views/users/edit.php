<?php declare(strict_types=1);

$userRoleNames = (array)($userRoleNames ?? []);
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">Edit User</h3>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="/users/edit?id=<?= (int)$u['id'] ?>">
  <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>

  <div class="mb-3">
    <label class="form-label">Username</label>
    <input class="form-control" name="username" value="<?= htmlspecialchars((string)$u['username']) ?>" required>
  </div>

  <div class="mb-3">
    <label class="form-label">New password (leave blank to keep)</label>
    <input class="form-control" type="password" name="password">
  </div>

  <div class="mb-3">
    <label class="form-label">Full name</label>
    <input class="form-control" name="full_name" value="<?= htmlspecialchars((string)($u['full_name'] ?? '')) ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Email</label>
    <input class="form-control" name="email" value="<?= htmlspecialchars((string)($u['email'] ?? '')) ?>">
  </div>

  <div class="mb-3 form-check">
    <input class="form-check-input" type="checkbox" name="is_active" <?= (int)$u['is_active'] ? 'checked' : '' ?>>
    <label class="form-check-label">Active</label>
  </div>

  <div class="mb-3">
    <label class="form-label">Roles</label>
    <?php foreach (($roles ?? []) as $r): ?>
      <?php $checked = in_array((string)$r['name'], $userRoleNames, true); ?>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="role_ids[]" value="<?= (int)$r['id'] ?>" <?= $checked ? 'checked' : '' ?>>
        <label class="form-check-label">
          <?= htmlspecialchars((string)$r['name']) ?>
          <span class="text-muted small"><?= htmlspecialchars((string)($r['description'] ?? '')) ?></span>
        </label>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn btn-outline-secondary" href="/users">Cancel</a>
  </div>
</form>