<?php
  $title = 'Referrals — ' . htmlspecialchars($job['job_title'] ?? 'Job');
  ob_start();
  $csrf = \Nexus\Helpers\Csrf::token();
?>
<h2 class="mb-3">Referrals for <?= htmlspecialchars($job['job_title']) ?></h2>
<table class="table table-sm align-middle">
  <thead><tr><th>ID</th><th>Alumni</th><th>Student</th><th>Message</th><th>Status</th><th>Reward</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach (($referrals ?? []) as $r): ?>
      <tr>
        <td>#<?= (int)$r['referral_id'] ?></td>
        <td><?= (int)$r['alumni_id'] ?></td>
        <td><?= (int)$r['student_id'] ?></td>
        <td><?= nl2br(htmlspecialchars((string)$r['message'])) ?></td>
        <td><?= htmlspecialchars($r['status']) ?></td>
        <td><?= (int)$r['reward_coins'] ?></td>
        <td>
          <form method="post" action="" class="d-inline">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="referral_id" value="<?= (int)$r['referral_id'] ?>">
            <button class="btn btn-sm btn-outline-dark" name="action" value="accept" type="submit">Accept</button>
            <button class="btn btn-sm btn-dark" name="action" value="decline" type="submit">Decline</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
