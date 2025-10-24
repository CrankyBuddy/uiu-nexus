<?php /** @var array $requests */
  $title = 'My Mentorship Requests | UIU NEXUS';
  ob_start();
?>
  <h3>My Mentorship Requests</h3>
  <?php
    $balanceTxt = '';
    try {
      $viewer = \Nexus\Helpers\Auth::user();
      if ($viewer && ($viewer['role'] ?? '') === 'student') {
        $pdoX = \Nexus\Core\Database::pdo($GLOBALS['config']);
        $stW = $pdoX->prepare('SELECT balance FROM user_wallets WHERE user_id = :uid');
        $stW->execute([':uid' => (int)$viewer['user_id']]);
        $bal = (int)($stW->fetchColumn() ?: 0);
        $balanceTxt = 'Current balance: ' . $bal . ' coins';
      }
    } catch (\Throwable $e) {}
  ?>
  <?php if (!empty($balanceTxt)): ?>
    <p class="text-muted small mb-2"><?= htmlspecialchars($balanceTxt) ?></p>
  <?php endif; ?>
  <?php if (!$requests): ?>
    <div class="alert alert-info">No requests yet.</div>
  <?php else: ?>
    <table class="table table-striped">
  <thead><tr><th>Area</th><th>Bid</th><th>Status</th><th>Ends</th><th>Days Left</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['area_name'] ?? '') ?></td>
            <td><?= (int)$r['bid_amount'] ?><?= $r['is_free_request'] ? ' (free)' : '' ?></td>
            <td><?= htmlspecialchars($r['status']) ?></td>
            <td><?= htmlspecialchars($r['end_date'] ?? '') ?></td>
            <td>
              <?php
                $daysLeft = '';
                if (($r['status'] ?? '') === 'accepted' && !empty($r['end_date'])) {
                  $e = strtotime((string)$r['end_date']);
                  if ($e) {
                    $diff = floor(($e - time()) / 86400);
                    $daysLeft = $diff >= 0 ? $diff : 0;
                  }
                }
                echo $daysLeft === '' ? '<span class="text-muted">—</span>' : (int)$daysLeft;
              ?>
            </td>
            <td><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
            <td>
              <?php if (($r['status'] ?? '') === 'pending'): ?>
                <form method="post" action="/mentorship/request/<?= (int)$r['request_id'] ?>/boost" class="d-flex gap-2 align-items-center">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                  <input type="number" min="1" class="form-control form-control-sm" style="width:100px" name="boost_amount" placeholder="Coins">
                  <button class="btn btn-sm btn-outline-primary">Boost</button>
                </form>
                <form method="post" action="/mentorship/request/<?= (int)$r['request_id'] ?>/cancel" class="d-inline" onsubmit="return confirm('Request admin to cancel this mentorship?');">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                  <input type="hidden" name="reason" value="User requested to cancel (pending)">
                  <button class="btn btn-sm btn-outline-danger mt-1">Request Cancel</button>
                </form>
              <?php elseif (($r['status'] ?? '') === 'accepted'): ?>
                <form method="post" action="/mentorship/request/<?= (int)$r['request_id'] ?>/cancel" class="d-inline" onsubmit="return confirm('Request admin to cancel this accepted mentorship?');">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                  <input type="hidden" name="reason" value="User requested to cancel (accepted)">
                  <button class="btn btn-sm btn-outline-danger">Request Cancel</button>
                </form>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
