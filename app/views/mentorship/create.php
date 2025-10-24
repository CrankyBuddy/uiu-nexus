<?php /** @var array $expertise */
  $title = 'Offer Mentorship | UIU NEXUS';
  ob_start();
?>
  <h3>Offer Mentorship</h3>
  <form method="post">
  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
    <div class="mb-3">
      <label class="form-label">Expertise Area</label>
      <select class="form-select" name="expertise_id" required>
        <option value="">Select...</option>
        <?php foreach ($expertise as $e): ?>
          <option value="<?= (int)$e['expertise_id'] ?>"><?= htmlspecialchars($e['area_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea class="form-control" name="description" rows="4" required></textarea>
    </div>
    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Min Coin Bid</label>
        <input type="number" min="0" class="form-control" name="min_coin_bid" value="50">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Max Slots</label>
        <input type="number" min="1" class="form-control" name="max_slots" value="5">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Session Duration (minutes)</label>
        <input type="number" min="15" step="15" class="form-control" name="session_duration" value="60">
      </div>
    </div>
    <fieldset class="border rounded p-3 mb-3">
      <legend class="w-auto px-2 small text-muted">Optional Slot 1 Filters</legend>
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">Min CGPA</label>
          <input type="number" step="0.01" min="0" max="4" class="form-control" name="min_cgpa" placeholder="e.g., 3.00">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Min Projects</label>
          <input type="number" min="0" class="form-control" name="min_projects" placeholder="e.g., 2">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Min Wallet Coins</label>
          <input type="number" min="0" class="form-control" name="min_wallet_coins" placeholder="e.g., 50">
        </div>
        
      </div>
      <div class="form-text">These filters apply to the first slot selection for fairness; other slots remain open per your discretion.</div>
    </fieldset>
    <button type="submit" class="btn btn-success">Create Listing</button>
  </form>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
