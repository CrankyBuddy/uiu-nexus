<?php
  /** @var array $listings */
  $title = 'Mentorship Listings | UIU NEXUS';
  ob_start();
  $viewer = \Nexus\Helpers\Auth::user();
  $viewerRole = (string)($viewer['role'] ?? '');
  $isAdmin = ($viewerRole === 'admin');
  $viewerAlumniId = 0;
  if ($viewerRole === 'alumni') {
    try {
      $pdo = \Nexus\Core\Database::pdo($GLOBALS['config']);
      $st = $pdo->prepare('SELECT alumni_id FROM alumni WHERE user_id = :u LIMIT 1');
      $st->execute([':u' => (int)($viewer['user_id'] ?? 0)]);
      $viewerAlumniId = (int)($st->fetchColumn() ?: 0);
    } catch (\Throwable $e) { $viewerAlumniId = 0; }
  }
?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Mentorship Listings</h3>
    <?php if ($viewerRole === 'alumni'): ?>
      <div class="btn-group">
        <a href="/mentorship/create" class="btn btn-primary">Offer Mentorship</a>
        <a href="/mentorship/my-listings" class="btn btn-outline-dark">My Listings</a>
      </div>
    <?php endif; ?>
  </div>
  <?php if (empty($listings)): ?>
    <div class="alert alert-info">No active listings yet.</div>
  <?php else: ?>
    <div class="list-group">
      <?php foreach ($listings as $l): ?>
        <?php $isOwner = ($viewerAlumniId && (int)$viewerAlumniId === (int)($l['alumni_id'] ?? 0)); ?>
        <div class="list-group-item">
          <div class="d-flex w-100 justify-content-between">
            <h5 class="mb-1">
              <a href="/mentorship/listing/<?= (int)$l['listing_id'] ?>" class="text-decoration-none">
                <?php echo htmlspecialchars($l['area_name'] ?? 'Mentorship'); ?>
              </a>
            </h5>
            <small>Min bid: <?= (int)$l['min_coin_bid'] ?> coins</small>
          </div>
          <p class="mb-1"><?php echo nl2br(htmlspecialchars(substr($l['description'] ?? '',0,160))); ?></p>
          <div class="d-flex justify-content-between align-items-center">
            <small>Mentor: <?= htmlspecialchars(($l['first_name'] ?? '') . ' ' . ($l['last_name'] ?? '')) ?> · Slots <?= (int)$l['current_slots'] ?>/<?= (int)$l['max_slots'] ?></small>
            <div class="d-flex gap-2">
              <?php if ($isOwner): ?>
                <a href="/mentorship/listing/<?= (int)$l['listing_id'] ?>/requests" class="btn btn-sm btn-outline-primary">View Requests</a>
                <a href="/mentorship/listing/<?= (int)$l['listing_id'] ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                <form method="post" action="/mentorship/listing/<?= (int)$l['listing_id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this listing? This cannot be undone.');">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              <?php elseif ($isAdmin): ?>
                <a href="/mentorship/listing/<?= (int)$l['listing_id'] ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                <form method="post" action="/mentorship/listing/<?= (int)$l['listing_id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this listing? This cannot be undone.');">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
