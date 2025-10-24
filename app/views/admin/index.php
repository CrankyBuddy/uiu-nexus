<?php
  $title = $title ?? 'Admin';
  ob_start();
?>
<div class="row">
  <div class="col-12">
    <h2 class="mb-3">Admin Dashboard</h2>
    <div class="row g-3">
      <div class="col-md-4"><div class="card"><div class="card-body"><div class="h5">Users</div><div class="display-6"><?= (int)($stats['users'] ?? 0) ?></div></div></div></div>
      <div class="col-md-4"><div class="card"><div class="card-body"><div class="h5">Pending Reports</div><div class="display-6"><?= (int)($stats['reports_pending'] ?? 0) ?></div></div></div></div>
      <div class="col-md-4"><div class="card"><div class="card-body"><div class="h5">Audits Today</div><div class="display-6"><?= (int)($stats['audit_today'] ?? 0) ?></div></div></div></div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <div class="h5">Cancellation Requests</div>
            <div class="display-6"><?=$stats['cancellations_pending'] ?? 0?></div>
            <a href="/admin/cancellations" class="btn btn-sm btn-outline-primary mt-2">Review</a>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-4">
      <a class="btn btn-outline-dark btn-sm" href="/admin/users">Manage Users</a>
      <a class="btn btn-outline-dark btn-sm" href="/admin/permissions">Permissions</a>
      <a class="btn btn-outline-dark btn-sm" href="/admin/reports">Reports</a>
      <a class="btn btn-outline-dark btn-sm" href="/admin/audit-logs">Audit Logs</a>
      <a class="btn btn-outline-dark btn-sm" href="/admin/restrictions">Restrictions</a>
      <a class="btn btn-outline-dark btn-sm" href="/admin/exports">Exports</a>
      <a class="btn btn-outline-dark btn-sm" href="/admin/settings">Settings</a>
    </div>
  </div>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
