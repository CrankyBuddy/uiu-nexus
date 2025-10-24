<?php
  $title = $title ?? 'Reports';
  ob_start();
  $csrf = \Nexus\Helpers\Csrf::token();
?>
<h2 class="mb-3">Reports</h2>
<form method="get" class="row row-cols-lg-auto g-2 align-items-center mb-3">
  <div class="col-12">
    <label for="status" class="form-label me-2">Status</label>
    <?php $currentStatus = $filter_status ?? ''; ?>
    <select id="status" name="status" class="form-select form-select-sm">
      <option value="">All</option>
      <?php foreach (['pending','investigating','resolved','dismissed'] as $st): ?>
        <option value="<?= $st ?>" <?= $currentStatus === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12">
    <label for="sort" class="form-label me-2">Sort</label>
    <?php $sort = $sort ?? 'created_desc'; ?>
    <select id="sort" name="sort" class="form-select form-select-sm">
      <option value="created_desc" <?= $sort==='created_desc'?'selected':'' ?>>Newest first</option>
      <option value="created_asc" <?= $sort==='created_asc'?'selected':'' ?>>Oldest first</option>
      <option value="status_asc" <?= $sort==='status_asc'?'selected':'' ?>>Status A→Z</option>
      <option value="status_desc" <?= $sort==='status_desc'?'selected':'' ?>>Status Z→A</option>
    </select>
  </div>
  <div class="col-12">
    <button class="btn btn-sm btn-outline-dark" type="submit">Apply</button>
    <a class="btn btn-sm btn-link" href="/admin/reports">Reset</a>
  </div>
</form>
<table class="table table-sm align-middle">
  <thead><tr><th>ID</th><th>Target</th><th>Reason</th><th>Status</th><th>Assigned</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach (($reports ?? []) as $r): ?>
      <tr>
        <td>#<?= (int)$r['report_id'] ?></td>
        <td><?= htmlspecialchars($r['target_type']) ?>: <?= (int)$r['target_id'] ?><br><small>by <?= htmlspecialchars((string)$r['reporter_email']) ?></small></td>
        <td><?= nl2br(htmlspecialchars((string)$r['reason'])) ?></td>
        <td><?= htmlspecialchars($r['status']) ?></td>
        <td><?= $r['assigned_to'] ? (int)$r['assigned_to'] : '-' ?></td>
        <td class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-dark" href="/admin/reports/<?= (int)$r['report_id'] ?>">View</a>
          <form method="post" action="/admin/reports/update" class="d-flex gap-2">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
            <select name="status" class="form-select form-select-sm" style="width:auto;">
              <?php foreach (['pending','investigating','resolved','dismissed'] as $st): ?>
                <option value="<?= $st ?>" <?= $r['status']===$st?'selected':'' ?>><?= $st ?></option>
              <?php endforeach; ?>
            </select>
            <select name="assigned_to" class="form-select form-select-sm" style="width:auto;">
              <option value="">Unassigned</option>
              <?php foreach (($admins ?? []) as $a): ?>
                <option value="<?= (int)$a['user_id'] ?>" <?= (isset($r['assigned_to']) && (int)$r['assigned_to']===(int)$a['user_id'])?'selected':'' ?>><?= htmlspecialchars($a['email']) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-dark" type="submit">Update</button>
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
