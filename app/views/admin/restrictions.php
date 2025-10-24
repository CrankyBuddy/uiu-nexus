<?php
  $title = $title ?? 'Restrictions';
  ob_start();
  $csrf = \Nexus\Helpers\Csrf::token();
?>
<h2 class="mb-3">User Feature Restrictions</h2>

<div class="card mb-3">
  <div class="card-body">
    <form method="post" action="/admin/restrictions/add" class="row g-2 align-items-end">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
      <div class="col-md-2"><label class="form-label">User ID</label><input class="form-control form-control-sm" type="number" name="user_id" required></div>
      <div class="col-md-3"><label class="form-label">Feature Key</label><input class="form-control form-control-sm" type="text" name="feature_key" placeholder="e.g., forum.post"></div>
      <div class="col-md-3"><label class="form-label">Restricted Until</label><input class="form-control form-control-sm" type="datetime-local" name="restricted_until"></div>
      <div class="col-md-3"><label class="form-label">Reason</label><input class="form-control form-control-sm" type="text" name="reason"></div>
      <div class="col-md-1"><button class="btn btn-sm btn-outline-dark" type="submit">Add</button></div>
    </form>
  </div>
  </div>

<table class="table table-sm align-middle">
  <thead><tr><th>ID</th><th>User</th><th>Feature</th><th>Until</th><th>Reason</th><th>By</th><th>Created</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach (($items ?? []) as $it): ?>
      <tr>
        <td><?= (int)$it['restriction_id'] ?></td>
        <td><?= (int)$it['user_id'] ?> — <?= htmlspecialchars((string)($it['email'] ?? '')) ?></td>
        <td><?= htmlspecialchars($it['feature_key']) ?></td>
        <td><?= htmlspecialchars((string)($it['restricted_until'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($it['reason'] ?? '')) ?></td>
        <td><?= (int)$it['restricted_by'] ?></td>
        <td><?= htmlspecialchars((string)$it['created_at']) ?></td>
        <td>
          <form method="post" action="/admin/restrictions/remove">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="restriction_id" value="<?= (int)$it['restriction_id'] ?>">
            <button class="btn btn-sm btn-dark" type="submit">Remove</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
