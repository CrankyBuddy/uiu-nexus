<?php
use Nexus\Helpers\Csrf;
?>
<h2>Manage Locks: <?= htmlspecialchars((string)($subject['email'] ?? 'User')) ?></h2>

<div class="card mb-3">
  <div class="card-header">Add Lock</div>
  <div class="card-body">
    <form method="post" action="/admin/locks/add">
      <input type="hidden" name="_token" value="<?= Csrf::token() ?>">
      <input type="hidden" name="user_id" value="<?= (int)($subject['user_id'] ?? 0) ?>">
      <input type="hidden" name="return_to" value="/admin/users/<?= (int)($subject['user_id'] ?? 0) ?>/locks">
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label">Field Key</label>
          <input class="form-control" name="field_key" placeholder="e.g., cgpa, phone, resume" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Locked Until (optional)</label>
          <input class="form-control" type="datetime-local" name="locked_until">
        </div>
        <div class="col-md-4">
          <label class="form-label">Reason (optional)</label>
          <input class="form-control" name="reason" placeholder="Why locking this field?">
        </div>
      </div>
      <div class="mt-3">
        <button class="btn btn-primary">Add Lock</button>
        <a class="btn btn-secondary" href="/admin/users">Back</a>
        <a class="btn btn-outline-primary" href="/admin/locks">All Locks</a>
      </div>
    </form>
  </div>
  <div class="card-footer text-muted">Accepted keys: first_name, last_name, bio, portfolio_url, linkedin_url, phone, address, region, resume, cgpa, university_id, hr_contact_email, hr_contact_phone, etc.</div>
  </div>

<div class="table-responsive">
  <table class="table table-sm table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Field</th>
        <th>Locked At</th>
        <th>Until</th>
        <th>Reason</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($locks ?? []) as $row): ?>
        <tr>
          <td>#<?= (int)($row['id'] ?? 0) ?></td>
          <td><code><?= htmlspecialchars((string)($row['field_key'] ?? '')) ?></code></td>
          <td><?= htmlspecialchars((string)($row['locked_at'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($row['locked_until'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)($row['reason'] ?? '')) ?></td>
          <td>
            <form method="post" action="/admin/locks/remove" onsubmit="return confirm('Remove this lock?')">
              <input type="hidden" name="_token" value="<?= Csrf::token() ?>">
              <input type="hidden" name="lock_id" value="<?= (int)($row['id'] ?? 0) ?>">
              <input type="hidden" name="user_id" value="<?= (int)($subject['user_id'] ?? 0) ?>">
              <input type="hidden" name="return_to" value="/admin/users/<?= (int)($subject['user_id'] ?? 0) ?>/locks">
              <button class="btn btn-sm btn-outline-danger">Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($locks ?? [])): ?>
    <div class="alert alert-info">No locks for this user.</div>
  <?php endif; ?>
</div>
