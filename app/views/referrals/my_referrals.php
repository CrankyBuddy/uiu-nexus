<?php
  $title = 'My Referrals';
  ob_start();
?>
<h2 class="mb-3">My Referrals</h2>
<table class="table table-sm">
  <thead><tr><th>ID</th><th>Job</th><th>Status</th><th>Reward</th><th>Created</th></tr></thead>
  <tbody>
    <?php foreach (($referrals ?? []) as $r): ?>
      <tr>
        <td>#<?= (int)$r['referral_id'] ?></td>
        <td><?= htmlspecialchars((string)$r['job_title']) ?></td>
        <td><?= htmlspecialchars($r['status']) ?></td>
        <td><?= (int)$r['reward_coins'] ?></td>
        <td><?= htmlspecialchars((string)$r['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
