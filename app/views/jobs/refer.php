<?php
  $title = 'Refer a Student — ' . htmlspecialchars($job['job_title'] ?? 'Job');
  ob_start();
?>
<h2 class="mb-3">Refer a Student to <?= htmlspecialchars($job['job_title']) ?></h2>
<div class="card"><div class="card-body">
  <form method="post" action="">
    <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
    <div class="mb-2">
      <label class="form-label">Student ID</label>
      <input class="form-control" type="number" name="student_id" required>
    </div>
    <div class="mb-2">
      <label class="form-label">Message</label>
      <textarea class="form-control" name="message" rows="3" placeholder="Why I'm referring this student..."></textarea>
    </div>
    <button class="btn btn-dark" type="submit">Submit Referral</button>
  </form>
</div></div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
