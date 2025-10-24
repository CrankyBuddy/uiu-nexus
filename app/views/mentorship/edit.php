<?php /** @var array $listing */ /** @var array $expertise */
  $title = 'Edit Mentorship Listing | UIU NEXUS';
  ob_start();
?>
  <h3>Edit Mentorship Listing</h3>
  <form method="post">
    <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
    <div class="mb-3">
      <label class="form-label">Expertise Area</label>
      <select class="form-select" name="expertise_id" required>
        <?php foreach ($expertise as $e): $sel = ((int)$e['expertise_id'] === (int)$listing['expertise_id']) ? 'selected' : ''; ?>
          <option value="<?= (int)$e['expertise_id'] ?>" <?= $sel ?>><?= htmlspecialchars($e['area_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea class="form-control" name="description" rows="4" required><?= htmlspecialchars($listing['description'] ?? '') ?></textarea>
    </div>
    <div class="row">
      <div class="col-md-3 mb-3">
        <label class="form-label">Min Coin Bid</label>
        <input type="number" min="0" class="form-control" name="min_coin_bid" value="<?= (int)$listing['min_coin_bid'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">Max Slots</label>
        <input type="number" min="1" class="form-control" name="max_slots" value="<?= (int)$listing['max_slots'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">Session Duration (minutes)</label>
        <input type="number" min="15" step="15" class="form-control" name="session_duration" value="<?= (int)$listing['session_duration'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">Active</label>
        <select class="form-select" name="is_active">
          <option value="1" <?= ((int)($listing['is_active'] ?? 1) === 1 ? 'selected' : '') ?>>Active</option>
          <option value="0" <?= ((int)($listing['is_active'] ?? 1) === 0 ? 'selected' : '') ?>>Inactive</option>
        </select>
      </div>
    </div>
    <fieldset class="border rounded p-3 mb-3">
      <legend class="w-auto px-2 small text-muted">Optional Slot 1 Filters</legend>
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">Min CGPA</label>
          <input type="number" step="0.01" min="0" max="4" class="form-control" name="min_cgpa" value="<?= htmlspecialchars((string)($listing['min_cgpa'] ?? '')) ?>">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Min Projects</label>
          <input type="number" min="0" class="form-control" name="min_projects" value="<?= htmlspecialchars((string)($listing['min_projects'] ?? '')) ?>">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Min Wallet Coins</label>
          <input type="number" min="0" class="form-control" name="min_wallet_coins" value="<?= htmlspecialchars((string)($listing['min_wallet_coins'] ?? '')) ?>">
        </div>
        
      </div>
    </fieldset>
    <button type="submit" class="btn btn-success">Save Changes</button>
    <a href="/mentorship/listing/<?= (int)($listing['listing_id'] ?? 0) ?>" class="btn btn-secondary ms-2">Cancel</a>
  </form>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>