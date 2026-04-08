<?php declare(strict_types=1); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">Create Role</h3>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="/roles/create">
  <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>

  <div class="mb-3">
    <label class="form-label">Name</label>
    <input class="form-control" name="name" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Description</label>
    <input class="form-control" name="description">
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" type="submit">Create</button>
    <a class="btn btn-outline-secondary" href="/roles">Cancel</a>
  </div>
</form>