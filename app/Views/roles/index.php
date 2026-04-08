<?php declare(strict_types=1); ?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="mb-0">Roles</h3>
  <a class="btn btn-primary" href="/roles/create">Add Role</a>
</div>

<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Description</th>
        <th>Created</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars((string)$r['name']) ?></td>
        <td><?= htmlspecialchars((string)($r['description'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)$r['created_at']) ?></td>
        <td class="d-flex gap-1">
          <a class="btn btn-sm btn-outline-primary" href="/roles/edit?id=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" action="/roles/delete?id=<?= (int)$r['id'] ?>" onsubmit="return confirm('Delete this role?')">
            <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>