<?php
  $title = $title ?? 'Exports';
  ob_start();
  $csrf = \Nexus\Helpers\Csrf::token();
?>
<h2 class="mb-3">Exports</h2>
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h5>Export Applications by Job</h5>
      <form method="post" action="/admin/exports">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="type" value="applications">
        <div class="mb-2"><label class="form-label">Job ID</label><input class="form-control form-control-sm" type="number" name="job_id" required></div>
        <button class="btn btn-sm btn-dark" type="submit">Export CSV</button>
      </form>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h5>Export Reports</h5>
      <form method="post" action="/admin/exports">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="type" value="reports">
        <button class="btn btn-sm btn-dark" type="submit">Export CSV</button>
      </form>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h5>Export Audit Logs</h5>
      <form method="post" action="/admin/exports">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="type" value="audit">
        <button class="btn btn-sm btn-dark" type="submit">Export CSV</button>
      </form>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card"><div class="card-body">
      <h5>Export Users</h5>
      <form method="post" action="/admin/exports">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="type" value="users">
        <button class="btn btn-sm btn-dark" type="submit">Export CSV</button>
      </form>
    </div></div>
  </div>
</div>

<h5>Past Exports</h5>
<table class="table table-sm align-middle">
  <thead><tr><th>ID</th><th>Type</th><th>Filters</th><th>Format</th><th>URL</th><th>Count</th><th>Created</th></tr></thead>
  <tbody>
    <?php foreach (($exports ?? []) as $e): ?>
      <tr>
        <td><?= (int)$e['export_id'] ?></td>
        <td><?= htmlspecialchars($e['export_type']) ?></td>
        <td><small><?= htmlspecialchars((string)$e['filters']) ?></small></td>
        <td><?= htmlspecialchars($e['file_format']) ?></td>
        <td><a href="<?= htmlspecialchars((string)$e['file_url']) ?>" target="_blank">Download</a></td>
        <td><?= (int)$e['record_count'] ?></td>
        <td><?= htmlspecialchars((string)$e['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
