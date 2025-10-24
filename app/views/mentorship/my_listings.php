<?php /** @var array $listings */
  $title = 'My Mentorship Listings | UIU NEXUS';
  ob_start();
?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>My Mentorship Listings</h3>
    <a href="/mentorship/create" class="btn btn-primary">New Listing</a>
  </div>
  <?php if (!$listings): ?>
    <div class="alert alert-info">You have no listings yet.</div>
  <?php else: ?>
    <div class="list-group">
      <?php foreach ($listings as $l): ?>
        <div class="list-group-item">
          <div class="d-flex w-100 justify-content-between align-items-start">
            <div>
              <h5 class="mb-1"><?= htmlspecialchars($l['area_name'] ?? 'Mentorship') ?></h5>
              <p class="mb-1"><?= nl2br(htmlspecialchars(substr($l['description'] ?? '',0,160))) ?></p>
              <small>Slots <?= (int)$l['current_slots'] ?>/<?= (int)$l['max_slots'] ?> · Min bid <?= (int)$l['min_coin_bid'] ?> · <?= ((int)($l['is_active'] ?? 1) === 1 ? 'Active' : 'Inactive') ?></small>
            </div>
            <div class="ms-2 text-nowrap">
              <a href="/mentorship/listing/<?= (int)$l['listing_id'] ?>/requests" class="btn btn-sm btn-outline-secondary">Requests</a>
              <a href="/mentorship/listing/<?= (int)$l['listing_id'] ?>/edit" class="btn btn-sm btn-primary">Edit</a>
              <form method="post" action="/mentorship/listing/<?= (int)$l['listing_id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this listing? This cannot be undone.');">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                <button class="btn btn-sm btn-danger">Delete</button>
              </form>
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
