<?php declare(strict_types=1);
$dc = $dc ?? null;
$cat = (string)($dc['category'] ?? 'Primary');
$cats = ['Primary','Secondary','Disaster recovery','edge','colocation','cloud','hybrid'];
?>
<h3 class="mb-3"><?= $mode === 'edit' ? 'Edit Data Center' : 'Add Data Center' ?></h3>

<form method="post" action="<?= $mode === 'edit' ? '/data-centers/edit?id='.(int)$dc['id'] : '/data-centers/create' ?>">
  <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Name</label>
      <input class="form-control" name="name" required value="<?= htmlspecialchars((string)($dc['name'] ?? '')) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Category</label>
      <select class="form-select" name="category">
        <?php foreach ($cats as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Location</label>
      <input class="form-control" name="location" value="<?= htmlspecialchars((string)($dc['location'] ?? '')) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Description</label>
      <textarea class="form-control" rows="3" name="description"><?= htmlspecialchars((string)($dc['description'] ?? '')) ?></textarea>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit"><?= $mode === 'edit' ? 'Update' : 'Save' ?></button>
    <a class="btn btn-outline-secondary" href="/data-centers">Back</a>
  </div>
</form>
