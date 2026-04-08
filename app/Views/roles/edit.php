<?php declare(strict_types=1); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">Edit Role</h3>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="/roles/edit?id=<?= (int)$r['id'] ?>">
  <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>

  <div class="mb-3">
    <label class="form-label">Name</label>
    <input class="form-control" name="name" value="<?= htmlspecialchars((string)$r['name']) ?>" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Description</label>
    <input class="form-control" name="description" value="<?= htmlspecialchars((string)($r['description'] ?? '')) ?>">
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn btn-outline-secondary" href="/roles">Cancel</a>
  </div>
</form>