<?php
  $title = htmlspecialchars(($job['job_title'] ?? 'Job') . ' | Jobs');
  ob_start();
  use Nexus\Helpers\Csrf;
  // Base prefix for subfolder deployments
  $__scriptDir = str_replace('\\','/', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));
  $__BASE_PREFIX = ($__scriptDir && $__scriptDir !== '/') ? $__scriptDir : '';
?>
<div class="row">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-body">
        <?php foreach (\Nexus\Helpers\Flash::consume() as $f): ?>
          <div class="alert alert-<?= htmlspecialchars($f['type']) ?>"><?= htmlspecialchars($f['message']) ?></div>
        <?php endforeach; ?>
        <h2 class="mb-2"><?= htmlspecialchars($job['job_title'] ?? 'Job') ?></h2>
        <div class="small text-muted mb-2">Category: <?= htmlspecialchars($job['category_name'] ?? '') ?> • Type: <?= htmlspecialchars($job['type_name'] ?? '') ?> • Location: <?= htmlspecialchars($job['location_name'] ?? '') ?></div>
        <div><?= nl2br(htmlspecialchars($job['job_description'] ?? '')) ?></div>
        <div class="mt-3 small">
          <span class="badge <?= (int)($job['is_active'] ?? 0) ? 'bg-success' : 'bg-secondary' ?>">Active: <?= (int)($job['is_active'] ?? 0) ? 'Yes' : 'No' ?></span>
          <span class="badge <?= (int)($job['is_approved'] ?? 0) ? 'bg-success' : 'bg-secondary' ?> ms-2">Approved: <?= (int)($job['is_approved'] ?? 0) ? 'Yes' : 'No' ?></span>
        </div>
        <?php $role = \Nexus\Helpers\Auth::user()['role'] ?? ''; ?>
        <?php if ($role === 'admin'): ?>
          <form method="post" action="<?= htmlspecialchars($__BASE_PREFIX) ?>/jobs/<?= (int)$job['job_id'] ?>/moderate" class="mt-2 d-inline-block">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <input type="hidden" name="action" value="approve">
            <button class="btn btn-sm btn-outline-success" type="submit">Approve</button>
          </form>
          <form method="post" action="<?= htmlspecialchars($__BASE_PREFIX) ?>/jobs/<?= (int)$job['job_id'] ?>/moderate" class="mt-2 d-inline-block">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <input type="hidden" name="action" value="decline">
            <button class="btn btn-sm btn-outline-danger" type="submit">Decline</button>
          </form>
          <form method="post" action="<?= htmlspecialchars($__BASE_PREFIX) ?>/jobs/<?= (int)$job['job_id'] ?>/delete" class="mt-2 d-inline-block" onsubmit="return confirm('Delete this job? This cannot be undone.');">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
          </form>
        <?php endif; ?>
        <?php $viewerIsAdmin = ((\Nexus\Helpers\Auth::user()['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($GLOBALS['config'], (int)(\Nexus\Helpers\Auth::id() ?? 0), 'manage.permissions'); ?>
        <?php if (!$viewerIsAdmin): ?>
        <div class="mt-2">
          <a class="btn btn-sm btn-outline-warning" href="<?= htmlspecialchars($__BASE_PREFIX) ?>/report?target_type=job&target_id=<?= (int)$job['job_id'] ?>">Report</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ((\Nexus\Helpers\Auth::user()['role'] ?? '') === 'student'): ?>
    <div class="card">
      <div class="card-header">Apply</div>
      <div class="card-body">
  <form method="post" action="<?= htmlspecialchars($__BASE_PREFIX) ?>/jobs/<?= (int)$job['job_id'] ?>/apply">
          <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
          <div class="mb-3">
            <textarea class="form-control" name="cover_letter" rows="5" placeholder="Write your cover letter... "></textarea>
          </div>
          <?php if (!empty($questions)): ?>
            <h6 class="mt-3">Additional Questions</h6>
            <?php foreach ($questions as $q): ?>
              <div class="mb-3">
                <label class="form-label"><?= htmlspecialchars((string)$q['question_text']) ?><?= !empty($q['is_required'])? ' *':'' ?></label>
                <?php $name = 'q_' . (int)$q['question_id']; ?>
                <?php if (($q['question_type'] ?? 'text') === 'textarea'): ?>
                  <textarea name="<?= htmlspecialchars($name) ?>" class="form-control" rows="3"></textarea>
                <?php else: ?>
                  <input name="<?= htmlspecialchars($name) ?>" class="form-control" type="<?= htmlspecialchars($q['question_type'] ?? 'text') ?>">
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <?php if (!empty($availableRefs)): ?>
            <div class="mb-3">
              <label class="form-label">Select up to 2 references (mentors who can vouch for you)</label>
              <select class="form-select" name="reference_ids[]" multiple size="<?= min(6, count($availableRefs)) ?>">
                <?php foreach ($availableRefs as $r): if (($r['status'] ?? '') !== 'active') continue; ?>
                  <option value="<?= (int)$r['reference_id'] ?>"><?= htmlspecialchars(trim(($r['alumni_first_name'] ?? '').' '.($r['alumni_last_name'] ?? ''))) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Max 2 will be attached.</div>
            </div>
          <?php endif; ?>
          <button class="btn btn-dark" type="submit">Submit Application</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
  <a href="<?= htmlspecialchars($__BASE_PREFIX) ?>/jobs" class="btn btn-sm btn-outline-dark w-100 mb-2">Back to Jobs</a>
  </div>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
