<?php
  $title = 'Schedule Interview';
  ob_start();
?>
<h2 class="mb-3">Schedule Interview</h2>
<div class="card"><div class="card-body">
  <div class="mb-2"><strong>Application:</strong> #<?= (int)$application['application_id'] ?> — <?= htmlspecialchars($application['job_title']) ?></div>
  <form method="post" action="">
    <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
    <div class="mb-2">
      <label class="form-label">Date & Time</label>
      <input class="form-control" type="datetime-local" name="scheduled_date" required>
    </div>
    <div class="mb-2">
      <label class="form-label">Duration (minutes)</label>
      <input class="form-control" type="number" name="duration_minutes" value="30" min="15" step="15">
    </div>
    <div class="mb-2">
      <label class="form-label">Meeting Link (optional)</label>
      <input class="form-control" type="url" name="meeting_link" placeholder="https://...">
    </div>
    <div class="mb-2">
      <label class="form-label">Interviewer Name (optional)</label>
      <input class="form-control" type="text" name="interviewer_name" placeholder="Jane Doe">
    </div>
    <button class="btn btn-dark" type="submit">Schedule</button>
  </form>
</div></div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
