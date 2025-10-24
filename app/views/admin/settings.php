<?php
  $title = $title ?? 'System Settings';
  ob_start();
  $csrf = \Nexus\Helpers\Csrf::token();
?>
<h2 class="mb-3">System Settings</h2>
<form class="row g-2 mb-3" method="get" action="/admin/settings">
  <div class="col-auto">
    <input class="form-control form-control-sm" type="text" name="q" placeholder="Search key/description" value="<?= htmlspecialchars((string)($q ?? '')) ?>">
  </div>
  <div class="col-auto">
    <select class="form-select form-select-sm" name="per_page">
      <?php $pp = (int)($per_page ?? 20); foreach ([10,20,50,100] as $opt): ?>
        <option value="<?= $opt ?>" <?= $pp===$opt?'selected':'' ?>><?= $opt ?>/page</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-sm btn-outline-dark" type="submit">Search</button>
  </div>
</form>
<div class="card mb-4">
  <div class="card-body">
    <h5 class="mb-3">Add / Update Setting</h5>
    <form method="post" action="/admin/settings/update" class="row g-2 align-items-end">
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
      <div class="col-md-3">
        <label class="form-label">Key</label>
        <input class="form-control form-control-sm" name="setting_key" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Value</label>
        <input class="form-control form-control-sm" name="setting_value" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Type</label>
        <select class="form-select form-select-sm" name="data_type">
          <option value="string">string</option>
          <option value="integer">integer</option>
          <option value="boolean">boolean</option>
          <option value="json">json</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Description</label>
        <input class="form-control form-control-sm" name="description">
      </div>
      <div class="col-12">
        <button class="btn btn-sm btn-dark" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<h5>Existing Settings</h5>
<table class="table table-sm align-middle">
  <thead>
    <tr><th>Key</th><th>Value</th><th>Type</th><th>Description</th><th>Updated</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach (($items ?? []) as $it): ?>
      <tr>
        <td><code><?= htmlspecialchars((string)$it['setting_key']) ?></code></td>
        <?php $isSensitive = (bool)preg_match('/(password|secret|token|key)/i', (string)$it['setting_key']); ?>
        <td>
          <small class="text-monospace d-block" style="max-width:420px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            <?= $isSensitive ? '••••••' : htmlspecialchars((string)$it['setting_value']) ?>
          </small>
          <?php if ($isSensitive): ?>
            <span class="badge rounded-pill bg-secondary">masked</span>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars((string)$it['data_type']) ?></td>
        <td><small><?= htmlspecialchars((string)($it['description'] ?? '')) ?></small></td>
        <td><small><?= htmlspecialchars((string)$it['updated_at']) ?></small></td>
        <td>
          <form method="post" action="/admin/settings/delete" onsubmit="return confirm('Delete setting?')">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="setting_key" value="<?= htmlspecialchars((string)$it['setting_key']) ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php if (($pages ?? 1) > 1): $p=(int)($page ?? 1); $pg=(int)$pages; $qv=urlencode((string)($q??'')); $pp=(int)($per_page??20); ?>
  <nav>
    <ul class="pagination pagination-sm">
      <li class="page-item <?= $p<=1?'disabled':'' ?>">
        <a class="page-link" href="/admin/settings?page=<?= max(1,$p-1) ?>&per_page=<?= $pp ?>&q=<?= $qv ?>">Prev</a>
      </li>
      <li class="page-item disabled"><span class="page-link">Page <?= $p ?> of <?= $pg ?></span></li>
      <li class="page-item <?= $p>=$pg?'disabled':'' ?>">
        <a class="page-link" href="/admin/settings?page=<?= min($pg,$p+1) ?>&per_page=<?= $pp ?>&q=<?= $qv ?>">Next</a>
      </li>
    </ul>
  </nav>
<?php endif; ?>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
