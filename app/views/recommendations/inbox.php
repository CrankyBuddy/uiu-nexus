<?php /** @var array $requests */
  $title = 'Mentor Recommendation Inbox | UIU NEXUS';
  ob_start();
?>
<h3>Recommendation Requests</h3>
<?php if (!$requests): ?>
  <div class="alert alert-info">No recommendation requests.</div>
<?php else: ?>
  <table class="table table-hover">
    <thead><tr><th>Student</th><th>Status</th><th>Message</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($requests as $r): ?>
        <tr>
          <td><a href="/recommendations/<?= (int)$r['request_id'] ?>"><?= htmlspecialchars(($r['student_first_name'] ?? '') . ' ' . ($r['student_last_name'] ?? '')) ?></a></td>
          <td><?= htmlspecialchars($r['status']) ?></td>
          <td><?= nl2br(htmlspecialchars($r['message'] ?? '')) ?></td>
          <td>
            <?php if ($r['status'] === 'pending'): ?>
              <form method="post" action="/recommendations/<?= (int)$r['request_id'] ?>/accept" class="d-inline">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                <input type="hidden" name="mentor_note" value="">
                <button class="btn btn-sm btn-success">Accept</button>
              </form>
              <form method="post" action="/recommendations/<?= (int)$r['request_id'] ?>/reject" class="d-inline">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                <button class="btn btn-sm btn-outline-danger">Reject</button>
              </form>
            <?php elseif ($r['status'] === 'accepted'): ?>
              <form method="post" action="/recommendations/<?= (int)$r['request_id'] ?>/revoke" class="d-inline">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                <button class="btn btn-sm btn-warning">Revoke</button>
              </form>
            <?php else: ?>
              <span class="text-muted">No actions</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
