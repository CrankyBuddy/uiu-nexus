<?php
  $title = 'Create Reference';
  ob_start();
  $role = \Nexus\Helpers\Auth::user()['role'] ?? '';
?>
<h2 class="mb-3">Create a Reference</h2>
<form method="post" action="/references/create">
  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
  <div class="mb-3">
    <label class="form-label">Student ID</label>
    <input required type="number" name="student_id" class="form-control" placeholder="Student ID">
  </div>
  <?php if ($role === 'admin'): ?>
  <div class="mb-3">
    <label class="form-label">Alumni ID</label>
    <input required type="number" name="alumni_id" class="form-control" placeholder="Alumni ID">
  </div>
  <?php endif; ?>
  <div class="mb-3">
    <label class="form-label">Reference Text (optional)</label>
    <textarea name="reference_text" class="form-control" rows="4" placeholder="Write a brief reference..."></textarea>
  </div>
  <button class="btn btn-dark" type="submit">Save Reference</button>
  <a href="/references/mine" class="btn btn-outline-secondary ms-2">Cancel</a>
  
</form>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
