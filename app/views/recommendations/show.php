<?php /** @var array $rec */ /** @var array $names */ /** @var string $role */
  $title = 'Recommendation Detail | UIU NEXUS';
  ob_start();
?>
<h3>Recommendation #<?= (int)$rec['request_id'] ?></h3>
<div class="card mb-3">
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <h5>Student</h5>
        <div><?= htmlspecialchars(($names['student']['first_name'] ?? '') . ' ' . ($names['student']['last_name'] ?? '')) ?></div>
      </div>
      <div class="col-md-6">
        <h5>Mentor</h5>
        <div><?= htmlspecialchars(($names['alumni']['first_name'] ?? '') . ' ' . ($names['alumni']['last_name'] ?? '')) ?></div>
      </div>
    </div>
    <div class="mt-3">
      <strong>Status:</strong> <?= htmlspecialchars($rec['status']) ?>
    </div>
    <?php if (!empty($rec['message'])): ?>
      <div class="mt-2"><strong>Student message:</strong><br><?= nl2br(htmlspecialchars($rec['message'])) ?></div>
    <?php endif; ?>
    <?php if (!empty($rec['mentor_note'])): ?>
      <div class="mt-2"><strong>Mentor note:</strong><br><?= nl2br(htmlspecialchars($rec['mentor_note'])) ?></div>
    <?php endif; ?>
    <?php if (!empty($rec['mentor_snapshot'])): ?>
      <?php $snap = is_array($rec['mentor_snapshot']) ? $rec['mentor_snapshot'] : json_decode((string)$rec['mentor_snapshot'], true); ?>
      <?php if ($snap): ?>
        <div class="mt-3">
          <h5>Mentor Snapshot</h5>
          <ul>
            <li>Email: <?= htmlspecialchars($snap['email'] ?? '') ?></li>
            <li>Name: <?= htmlspecialchars(($snap['first_name'] ?? '') . ' ' . ($snap['last_name'] ?? '')) ?></li>
            <li>Company: <?= htmlspecialchars($snap['company'] ?? '') ?></li>
            <li>Title: <?= htmlspecialchars($snap['job_title'] ?? '') ?></li>
            <li>Years: <?= htmlspecialchars((string)($snap['years_of_experience'] ?? '')) ?></li>
            <li>Industry: <?= htmlspecialchars($snap['industry'] ?? '') ?></li>
            <li>LinkedIn: <?php if (!empty($snap['linkedin_url'])): ?><a href="<?= htmlspecialchars($snap['linkedin_url']) ?>" target="_blank">Profile</a><?php endif; ?></li>
            <li>Portfolio: <?php if (!empty($snap['portfolio_url'])): ?><a href="<?= htmlspecialchars($snap['portfolio_url']) ?>" target="_blank">Site</a><?php endif; ?></li>
          </ul>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php if ($role === 'alumni' && ($rec['status'] ?? '') === 'pending'): ?>
  <div class="card mb-3">
    <div class="card-body">
      <form method="post" action="/recommendations/<?= (int)$rec['request_id'] ?>/accept" class="mb-2">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
        <div class="mb-2">
          <label class="form-label">Mentor note (optional)</label>
          <textarea name="mentor_note" rows="3" class="form-control"></textarea>
        </div>
        <button class="btn btn-success">Accept</button>
      </form>
      <form method="post" action="/recommendations/<?= (int)$rec['request_id'] ?>/reject">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
        <button class="btn btn-outline-danger">Reject</button>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php if ($role === 'alumni' && ($rec['status'] ?? '') === 'accepted'): ?>
  <form method="post" action="/recommendations/<?= (int)$rec['request_id'] ?>/revoke">
    <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
    <button class="btn btn-warning">Revoke Recommendation</button>
  </form>
<?php endif; ?>

<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
