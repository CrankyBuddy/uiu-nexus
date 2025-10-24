<?php /** @var array $requests */
  $title = 'My Recommendations | UIU NEXUS';
  ob_start();
?>
<h3>My Recommendation Requests</h3>
<p><a class="btn btn-primary" href="/recommendations/create">Request a Recommendation</a></p>
<?php if (!$requests): ?>
  <div class="alert alert-info">You have not requested any recommendations yet.</div>
<?php else: ?>
  <table class="table table-striped">
  <thead><tr><th>Mentor</th><th>Status</th><th>Requested</th></tr></thead>
    <tbody>
      <?php foreach ($requests as $r): ?>
        <tr>
          <td><a href="/recommendations/<?= (int)$r['request_id'] ?>"><?= htmlspecialchars(($r['alumni_first_name'] ?? '') . ' ' . ($r['alumni_last_name'] ?? '')) ?></a></td>
          <td><?= htmlspecialchars($r['status']) ?></td>
          <td><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
