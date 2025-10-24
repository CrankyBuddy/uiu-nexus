<?php /** @var array $request */ /** @var array $listing */
  $title = 'Schedule Mentorship Session | UIU NEXUS';
  ob_start();
?>
  <h3>Schedule Session</h3>
  <p>Listing #<?= (int)$listing['listing_id'] ?> · Request #<?= (int)$request['request_id'] ?> · Duration <?= (int)$listing['session_duration'] ?>m</p>
  <?php
    $viewerId = (int)(\Nexus\Helpers\Auth::id() ?? 0);
    $viewerRole = (string)(\Nexus\Helpers\Auth::user()['role'] ?? '');
    $subjectUserId = (int)($request['student_user_id'] ?? 0);
    $canSeeCgpa = \Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $viewerId, $viewerRole, $subjectUserId, 'cgpa', ['mentorshipAccepted' => true]);
  ?>
  <p class="text-muted">Student CGPA: <?= $canSeeCgpa ? htmlspecialchars((string)($request['student_cgpa'] ?? '')) : '<span class="text-muted">Hidden</span>' ?></p>
  <form method="post">
  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Date</label>
        <input type="date" class="form-control" name="session_date" required>
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Time</label>
        <input type="time" class="form-control" name="session_time" required>
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Meeting Link</label>
        <input type="url" class="form-control" name="meeting_link" placeholder="https://...">
      </div>
    </div>
    <button type="submit" class="btn btn-success">Create Session</button>
  </form>
  <p class="text-muted mt-3">Tip: The student will see the session details in their requests list.</p>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
