<?php /** @var array $listing */
  $title = 'Request Mentorship | UIU NEXUS';
  ob_start();
?>
  <h3>Request Mentorship</h3>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if (!empty($eligibility_error)): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($eligibility_error) ?></div>
  <?php endif; ?>
  <form method="post">
  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
    <div class="mb-3">
      <label class="form-label">Your Bid (min <?= (int)$listing['min_coin_bid'] ?>)</label>
      <input type="number" min="<?= (int)$listing['min_coin_bid'] ?>" class="form-control" name="bid_amount" value="<?= (int)($bid ?? $listing['min_coin_bid']) ?>">
    </div>
    <div class="mb-3 form-check">
      <?php $remaining = (int)($quota ?? 0); $disabled = $remaining <= 0 ? 'disabled' : ''; ?>
      <input type="checkbox" class="form-check-input" id="is_free" name="is_free_request" value="1" <?= $disabled ?>>
  <label class="form-check-label" for="is_free">Use free mentorship application ticket (remaining: <?= $remaining ?>; 3 given for free every 4 months)</label>
    </div>
    <div class="mb-3">
      <label class="form-label">Motivation Letter (optional)</label>
      <textarea class="form-control" name="message" rows="4" placeholder="Share your goals, context, or what you hope to learn."><?= htmlspecialchars($message ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-success" <?= !empty($eligibility_error) ? 'disabled' : '' ?>>Submit Request</button>
  </form>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
