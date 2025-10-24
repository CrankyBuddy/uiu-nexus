<?php
  $title = $title ?? 'Manage Users';
  ob_start();
?>
<h2 class="mb-3">Users</h2>
<table class="table table-sm align-middle">
  <thead><tr><th>ID</th><th>Email</th><th>Role</th><th>Verified</th><th>Active</th><th>Last Login</th><th>Wallet</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach (($users ?? []) as $u): ?>
    <tr>
      <td><?= (int)$u['user_id'] ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td>
        <form method="post" action="/admin/users/change-role" class="d-flex gap-2">
          <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
          <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
          <select name="role" class="form-select form-select-sm" style="width:auto;">
            <?php foreach (['student','alumni','recruiter','admin'] as $role): ?>
              <option value="<?= $role ?>" <?= $u['role']===$role?'selected':'' ?>><?= $role ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-outline-dark" type="submit">Change</button>
        </form>
      </td>
      <td><?= ((int)$u['is_verified']) ? 'Yes' : 'No' ?></td>
      <td>
        <form method="post" action="/admin/users/toggle">
          <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
          <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
          <button class="btn btn-sm <?= ((int)$u['is_active'])? 'btn-outline-dark':'btn-dark' ?>" type="submit"><?= ((int)$u['is_active'])? 'Deactivate':'Activate' ?></button>
        </form>
      </td>
      <td><?= htmlspecialchars((string)($u['last_login_at'] ?? '')) ?></td>
      <td style="min-width:260px;">
        <div class="small text-muted">Balance: <?= (int)($u['wallet_balance'] ?? 0) ?> coins</div>
        <?php if (in_array($u['role'], ['student','alumni'], true)): ?>
          <form method="post" action="/admin/users/wallet-adjust" class="d-flex flex-wrap gap-1 align-items-center mt-1">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
            <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
            <input type="number" name="amount" min="1" class="form-control form-control-sm" style="width:90px" placeholder="Amount" required>
            <select name="direction" class="form-select form-select-sm" style="width:auto;">
              <option value="credit">Add</option>
              <option value="debit">Deduct</option>
            </select>
            <input type="text" name="note" class="form-control form-control-sm" style="width:150px" placeholder="Note (optional)">
            <button class="btn btn-sm btn-outline-dark" type="submit">Update</button>
          </form>
        <?php else: ?>
          <span class="text-muted small">Wallet not available</span>
        <?php endif; ?>
      </td>
      <td class="d-flex flex-wrap gap-1">
        <a class="btn btn-sm btn-outline-secondary" href="mailto:<?= htmlspecialchars($u['email']) ?>">Email</a>
        <a class="btn btn-sm btn-outline-dark" href="/profile?user_id=<?= (int)$u['user_id'] ?>">View Profile</a>
        <a class="btn btn-sm btn-dark" href="/profile/edit?user_id=<?= (int)$u['user_id'] ?>">Edit Profile</a>
        <form method="post" action="/admin/users/suspend" class="d-flex gap-1 align-items-center">
          <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
          <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
          <select name="scope" class="form-select form-select-sm" style="width:auto">
            <option value="platform">Suspend Platform</option>
            <option value="social">Suspend Social</option>
          </select>
          <input type="datetime-local" name="until" class="form-control form-control-sm" style="width:auto" placeholder="Until (optional)">
          <input type="text" name="reason" class="form-control form-control-sm" style="width:160px" placeholder="Reason (optional)">
          <button class="btn btn-sm btn-outline-danger" type="submit">Apply</button>
        </form>
        <form method="post" action="/admin/users/lift" class="d-flex gap-1 align-items-center">
          <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
          <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
          <select name="scope" class="form-select form-select-sm" style="width:auto">
            <option value="platform">Lift Platform</option>
            <option value="social">Lift Social</option>
          </select>
          <button class="btn btn-sm btn-outline-success" type="submit">Lift</button>
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
