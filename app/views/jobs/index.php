<?php
  $title = 'Jobs | UIU NEXUS';
  ob_start();
  // Base prefix for subfolder deployments
  $__scriptDir = str_replace('\\','/', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));
  $__BASE_PREFIX = ($__scriptDir && $__scriptDir !== '/') ? $__scriptDir : '';
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="m-0">Job Listings</h2>
  <?php if ((\Nexus\Helpers\Auth::user()['role'] ?? '') === 'recruiter'): ?>
    <a class="btn btn-sm btn-dark" href="<?= htmlspecialchars($__BASE_PREFIX) ?>/jobs/create">Post a Job</a>
  <?php endif; ?>
</div>

<?php if (empty($jobs)): ?>
  <div class="alert alert-secondary">No jobs yet.</div>
<?php else: ?>
  <?php foreach ($jobs as $j): ?>
    <div class="card mb-2">
      <div class="card-body">
        <a class="h5 d-block mb-1" href="<?= htmlspecialchars($__BASE_PREFIX) ?>/jobs/<?= (int)$j['job_id'] ?>"><?= htmlspecialchars($j['job_title'] ?? 'Untitled') ?></a>
        <div class="small text-muted">Category: <?= htmlspecialchars($j['category_name'] ?? '') ?> • Type: <?= htmlspecialchars($j['type_name'] ?? '') ?> • Location: <?= htmlspecialchars($j['location_name'] ?? '') ?></div>
        <?php
          $role = (\Nexus\Helpers\Auth::user()['role'] ?? '');
          $userId = (int)(\Nexus\Helpers\Auth::user()['user_id'] ?? 0);
          $ownsJob = false;
          if ($role === 'recruiter' && $userId) {
            try {
              $rec = \Nexus\Models\Recruiter::findByUserId($GLOBALS['config'], $userId);
              $ownsJob = ((int)($j['recruiter_id'] ?? 0) === (int)($rec['recruiter_id'] ?? -1));
            } catch (\Throwable $e) { $ownsJob = false; }
          }
        ?>
        <div class="mt-2 d-flex flex-wrap gap-2">
          <?php if ($role === 'recruiter'): ?>
            <a class="btn btn-sm btn-outline-dark" href="<?= htmlspecialchars($__BASE_PREFIX) ?>/jobs/listing/<?= (int)$j['job_id'] ?>/applications">Review Applicants</a>
          <?php endif; ?>
          <?php if ($role === 'admin' || $ownsJob): ?>
            <form method="post" action="<?= htmlspecialchars($__BASE_PREFIX) ?>/jobs/<?= (int)$j['job_id'] ?>/delete" onsubmit="return confirm('Delete this job? This cannot be undone.');">
              <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
