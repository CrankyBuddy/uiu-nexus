<?php /** @var array $listing */ /** @var array $slots */
  $title = 'Mentorship Listing | UIU NEXUS';
  ob_start();
  $viewer = \Nexus\Helpers\Auth::user();
  $canApply = true;
  $why = [];
  if (($viewer['role'] ?? '') === 'student') {
    // Fetch minimal student info for constraint checks
    try {
      $pdoX = \Nexus\Core\Database::pdo($GLOBALS['config']);
      $st = $pdoX->prepare('SELECT s.student_id, s.cgpa, s.user_id FROM students s JOIN users u ON u.user_id = s.user_id WHERE u.user_id = :uid LIMIT 1');
      $st->execute([':uid' => (int)($viewer['user_id'] ?? 0)]);
      $stu = $st->fetch(PDO::FETCH_ASSOC) ?: null;
      if ($stu) {
        // One-time per listing rule
        try {
          $stAny = $pdoX->prepare('SELECT COUNT(*) FROM mentorship_requests WHERE student_id = :sid AND listing_id = :lid');
          $stAny->execute([':sid' => (int)$stu['student_id'], ':lid' => (int)$listing['listing_id']]);
          if ((int)($stAny->fetchColumn() ?: 0) > 0) { $canApply = false; $why[] = 'You have already applied to this offer.'; }
        } catch (\Throwable $e) {}
        // Mentor-pair cooldown: active or within +1 month after end
        try {
          $stPair = $pdoX->prepare('SELECT COUNT(*) FROM mentorship_requests r JOIN mentorship_listings l ON l.listing_id = r.listing_id WHERE r.student_id = :sid AND l.alumni_id = :aid AND r.status = "accepted" AND (r.end_date IS NULL OR r.end_date >= CURRENT_DATE() OR DATE_ADD(r.end_date, INTERVAL 1 MONTH) > CURRENT_DATE())');
          $stPair->execute([':sid' => (int)$stu['student_id'], ':aid' => (int)$listing['alumni_id']]);
          if ((int)($stPair->fetchColumn() ?: 0) > 0) { $canApply = false; $why[] = 'You can apply to this mentor again one month after your last mentorship window ends.'; }
        } catch (\Throwable $e) {}
        if (!empty($listing['min_cgpa'])) {
          $cg = (float)($stu['cgpa'] ?? 0);
          if ($cg <= 0 || $cg + 1e-6 < (float)$listing['min_cgpa']) { $canApply = false; $why[] = 'Minimum CGPA not met.'; }
        }
        if (!empty($listing['min_projects'])) {
          $pc = (int)($pdoX->query('SELECT COUNT(*) FROM student_projects sp WHERE sp.user_id = ' . (int)$stu['user_id'])->fetchColumn() ?: 0);
          if ($pc < (int)$listing['min_projects']) { $canApply = false; $why[] = 'Minimum projects not met.'; }
        }
        if (!empty($listing['min_wallet_coins'])) {
          $wb = (int)($pdoX->query('SELECT balance FROM user_wallets WHERE user_id = ' . (int)$stu['user_id'])->fetchColumn() ?: 0);
          if ($wb < (int)$listing['min_wallet_coins']) { $canApply = false; $why[] = 'Minimum wallet coins not met.'; }
        }
      }
    } catch (\Throwable $e) { /* ignore and default to can apply */ }
  }
?>
  <h3><?= htmlspecialchars($listing['description'] ? substr($listing['description'],0,60) : 'Mentorship Listing') ?></h3>
  <p><strong>Min bid:</strong> <?= (int)$listing['min_coin_bid'] ?> coins · <strong>Slots:</strong> <?= (int)$listing['current_slots'] ?>/<?= (int)$listing['max_slots'] ?> · <strong>Duration:</strong> <?= (int)$listing['session_duration'] ?>m</p>
  <h5>Available times</h5>
  <?php if (!$slots): ?>
    <p class="text-muted">Mentor didn't specify fixed slots.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($slots as $s): ?>
        <li>Day <?= (int)$s['day_of_week'] ?>: <?= htmlspecialchars($s['start_time']) ?> - <?= htmlspecialchars($s['end_time']) ?> <?= htmlspecialchars($s['timezone'] ?? '') ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <?php if (!empty($why)): ?>
    <div class="alert alert-warning">You can't request this listing: <?= htmlspecialchars(implode(' ', $why)) ?></div>
  <?php endif; ?>
  <?php $viewerRole = (string)($viewer['role'] ?? ''); ?>
  <?php if ($viewerRole === 'student'): ?>
    <a href="/mentorship/listing/<?= (int)$listing['listing_id'] ?>/request" class="btn btn-primary <?= $canApply ? '' : 'disabled' ?>" <?= $canApply ? '' : 'aria-disabled="true" tabindex="-1"' ?>>Request Mentorship</a>
  <?php endif; ?>
  <?php if ($viewerRole === 'alumni'): ?>
    <?php
      // If the viewer is the offerer, show quick actions here
      try {
        $pdoV = \Nexus\Core\Database::pdo($GLOBALS['config']);
        $al = $pdoV->prepare('SELECT alumni_id FROM alumni WHERE user_id = :u');
        $al->execute([':u' => (int)($viewer['user_id'] ?? 0)]);
        $viewerAlumniId = (int)($al->fetchColumn() ?: 0);
        $isOwner = $viewerAlumniId && $viewerAlumniId === (int)$listing['alumni_id'];
      } catch (\Throwable $e) { $isOwner = false; }
    ?>
    <?php if (!empty($isOwner)): ?>
      <div class="mt-3 d-flex gap-2">
        <a href="/mentorship/listing/<?= (int)$listing['listing_id'] ?>/requests" class="btn btn-outline-primary">View Requests</a>
        <a href="/mentorship/listing/<?= (int)$listing['listing_id'] ?>/edit" class="btn btn-outline-secondary">Edit</a>
        <form method="post" action="/mentorship/listing/<?= (int)$listing['listing_id'] ?>/delete" onsubmit="return confirm('Delete this listing? This cannot be undone.');">
          <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
          <button class="btn btn-outline-danger" type="submit">Delete</button>
        </form>
      </div>
    <?php endif; ?>
  <?php endif; ?>
  <?php if ($viewerRole === 'admin'): ?>
    <div class="mt-3 d-flex gap-2">
      <a href="/mentorship/listing/<?= (int)$listing['listing_id'] ?>/requests" class="btn btn-outline-primary">View Requests</a>
      <a href="/mentorship/listing/<?= (int)$listing['listing_id'] ?>/edit" class="btn btn-outline-secondary">Edit</a>
      <form method="post" action="/admin/mentorship/listing/<?= (int)$listing['listing_id'] ?>/force-delete" onsubmit="return confirm('Admin: Force delete this listing? All active/pending requests will be cancelled and users notified.');">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
        <button class="btn btn-danger">Force Delete (Admin)</button>
      </form>
    </div>
  <?php endif; ?>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
