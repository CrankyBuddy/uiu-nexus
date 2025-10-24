<?php
  $title = $title ?? 'Permissions';
  ob_start();
  $csrf = \Nexus\Helpers\Csrf::token();
?>
<h2 class="mb-3">Permissions</h2>
<div class="row">
  <div class="col-md-6">
    <h5>All Permissions</h5>
    <table class="table table-sm">
      <thead><tr><th>ID</th><th>Key</th><th>Module</th><th>Description</th><th>Grant</th></tr></thead>
      <tbody>
        <?php foreach (($perms ?? []) as $p): ?>
          <tr>
            <td><?= (int)$p['permission_id'] ?></td>
            <td><?= htmlspecialchars($p['permission_key']) ?></td>
            <td><?= htmlspecialchars((string)$p['module']) ?></td>
            <td><?= htmlspecialchars((string)$p['description']) ?></td>
            <td>
              <form method="post" action="/admin/permissions/grant" class="d-flex gap-1">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="permission_id" value="<?= (int)$p['permission_id'] ?>">
                <select name="role" class="form-select form-select-sm" style="width:auto;">
                  <?php foreach (['student','alumni','recruiter','admin'] as $role): ?>
                    <option value="<?= $role ?>"><?= $role ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-outline-dark" type="submit">Grant</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="col-md-6">
    <h5>Role Permissions</h5>
    <table class="table table-sm">
      <thead><tr><th>Role</th><th>Permission</th><th>Revoke</th></tr></thead>
      <tbody>
        <?php foreach (($rolePerms ?? []) as $rp): ?>
          <tr>
            <td><?= htmlspecialchars($rp['role']) ?></td>
            <td><?= htmlspecialchars($rp['permission_key']) ?></td>
            <td>
              <form method="post" action="/admin/permissions/revoke">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="permission_id" value="<?= (int)$rp['permission_id'] ?>">
                <input type="hidden" name="role" value="<?= htmlspecialchars($rp['role']) ?>">
                <button class="btn btn-sm btn-dark" type="submit">Revoke</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
