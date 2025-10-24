<?php
  $title = 'My Applications | UIU NEXUS';
  ob_start();
?>
<h2 class="mb-3">My Applications</h2>
<?php if (empty($applications)): ?>
  <div class="alert alert-info">You haven't applied to any jobs yet.</div>
<?php else: ?>
  <table class="table table-hover">
    <thead><tr><th>Job</th><th>Category</th><th>Type</th><th>Status</th><th>Applied</th></tr></thead>
    <tbody>
      <?php foreach ($applications as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['job_title'] ?? '') ?></td>
          <td><?= htmlspecialchars($a['category_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($a['type_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($a['status'] ?? 'applied') ?></td>
          <td><?= htmlspecialchars($a['applied_at'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
