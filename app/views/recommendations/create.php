<?php
  $title = 'Request a Recommendation | UIU NEXUS';
  ob_start();
?>
<h3>Request a Recommendation</h3>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post">
  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
  <div class="mb-3">
    <label class="form-label">Mentor Alumni ID</label>
    <input type="number" min="1" name="alumni_id" class="form-control" placeholder="Enter Alumni ID" required>
    <div class="form-text">For MVP, enter the Alumni ID. A searchable picker can be added later.</div>
  </div>
  <div class="mb-3">
    <label class="form-label">Message (optional)</label>
    <textarea name="message" rows="3" class="form-control" placeholder="Add context for the mentor"></textarea>
  </div>
  <button class="btn btn-success" type="submit">Send Request</button>
</form>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
