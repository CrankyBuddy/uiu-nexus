<?php
use Nexus\Helpers\Csrf;
?>
<h2>Field Locks</h2>
<p class="text-muted">Recent field locks across users.</p>
<div class="table-responsive">
  <table class="table table-sm table-striped">
    <thead>
      <tr>
        <th>User</th>
        <th>Field</th>
        <th>Locked By</th>
        <th>Locked At</th>
        <th>Until</th>
        <th>Reason</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($items ?? []) as $row): ?>
        <tr>
          <td><a href="/admin/users/<?= htmlspecialchars((string)($row['user_id'] ?? '')) ?>/locks"><?= htmlspecialchars((string)($row['user_email'] ?? '')) ?></a></td>
          <td><code><?= htmlspecialchars((string)($row['field_key'] ?? '')) ?></code></td>
          <td><?= htmlspecialchars((string)($row['admin_email'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($row['locked_at'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($row['locked_until'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($row['reason'] ?? '')) ?></td>
          <td>
            <form method="post" action="/admin/locks/remove" onsubmit="return confirm('Remove this lock?')">
              <input type="hidden" name="_token" value="<?= Csrf::token() ?>">
              <input type="hidden" name="lock_id" value="<?= (int)($row['id'] ?? 0) ?>">
              <input type="hidden" name="user_id" value="<?= (int)($row['user_id'] ?? 0) ?>">
              <input type="hidden" name="return_to" value="/admin/locks">
              <button class="btn btn-sm btn-outline-danger">Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($items ?? [])): ?>
    <div class="alert alert-info">No locks found.</div>
  <?php endif; ?>
  <a class="btn btn-secondary" href="/admin/users">Back to Users</a>
  <a class="btn btn-outline-primary" href="/admin">Admin Home</a>
</div>
