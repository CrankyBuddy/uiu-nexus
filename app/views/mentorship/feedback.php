<?php
  /** @var int $sessionId */
  $title = 'Mentorship Feedback | UIU NEXUS';
  ob_start();
?>
  <h3>Leave Feedback</h3>
  <form method="post">
  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
    <div class="mb-3">
      <label class="form-label">Rating (1–5)</label>
      <input type="number" min="1" max="5" class="form-control" name="rating" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Feedback</label>
      <textarea class="form-control" name="feedback" rows="4"></textarea>
    </div>
    <button class="btn btn-primary">Submit</button>
  </form>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
