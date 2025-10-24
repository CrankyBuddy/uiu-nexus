<?php
  $title = 'My Job Listings | UIU NEXUS';
  ob_start();
  // Base prefix for subfolder deployments
  $__scriptDir = str_replace('\\','/', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));
  $__BASE_PREFIX = ($__scriptDir && $__scriptDir !== '/') ? $__scriptDir : '';
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="m-0">My Job Listings</h2>
  <a class="btn btn-sm btn-dark" href="<?= htmlspecialchars($__BASE_PREFIX) ?>/jobs/create">New Listing</a>
  </div>
<?php if (empty($jobs)): ?>
  <div class="alert alert-info">You have no job listings yet.</div>
<?php else: ?>
  <div class="list-group">
    <?php foreach ($jobs as $j): ?>
      <div class="list-group-item">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <a class="me-3 h5 text-decoration-none" href="<?= htmlspecialchars($__BASE_PREFIX) ?>/jobs/listing/<?= (int)$j['job_id'] ?>/applications">
              <?= htmlspecialchars($j['job_title'] ?? 'Untitled') ?>
            </a>
            <div class="small text-muted">ID: <?= (int)$j['job_id'] ?></div>
          </div>
          <div class="d-flex gap-2">
            <form method="post" action="/jobs/<?= (int)$j['job_id'] ?>/toggle" class="m-0">
              <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
              <button class="btn btn-sm <?= (int)($j['is_active'] ?? 0) ? 'btn-outline-secondary' : 'btn-outline-success' ?>" type="submit">
                <?= (int)($j['is_active'] ?? 0) ? 'Deactivate' : 'Activate' ?>
              </button>
            </form>
            <form method="post" action="/jobs/<?= (int)$j['job_id'] ?>/delete" class="m-0" onsubmit="return confirm('Delete this job? This cannot be undone.');">
              <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
