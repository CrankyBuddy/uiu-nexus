<?php
  $title = 'Job Applications | UIU NEXUS';
  ob_start();
?>
<h2 class="mb-3">Applications for Job #<?= (int)$job['job_id'] ?></h2>
<form method="get" class="row g-2 mb-3">
  <div class="col-md-3">
    <select name="status" class="form-select">
      <option value="">All statuses</option>
      <?php foreach (['applied','under_review','shortlisted','interview','accepted','rejected'] as $st): ?>
        <option value="<?= $st ?>" <?= !empty($filterStatus)&&$filterStatus===$st?'selected':'' ?>><?= $st ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-5"><input name="q" class="form-control" placeholder="Search name or email" value="<?= htmlspecialchars((string)($filterQuery ?? '')) ?>"></div>
  <div class="col-md-2"><button class="btn btn-dark w-100" type="submit">Filter</button></div>
  <div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="?">Reset</a></div>
  </form>
<?php if (empty($applications)): ?>
  <div class="alert alert-info">No applications yet.</div>
<?php else: ?>
  <table class="table table-striped">
    <thead><tr><th>Applicant</th><th>Cover Letter</th><th>Status</th><th>Applied At</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($applications as $a): ?>
        <tr>
          <td><?= htmlspecialchars(trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')) ?: 'Student #' . (int)($a['student_id'] ?? 0)) ?></td>
          <td><?= nl2br(htmlspecialchars($a['cover_letter'] ?? '')) ?></td>
          <td><?= htmlspecialchars($a['status'] ?? 'applied') ?></td>
          <td><?= htmlspecialchars($a['applied_at'] ?? '') ?></td>
          <td><a class="btn btn-sm btn-outline-dark" href="/applications/<?= (int)$a['application_id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
