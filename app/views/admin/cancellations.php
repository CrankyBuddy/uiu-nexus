<?php /** @var array $items */
  $title = 'Pending Mentorship Cancellations | UIU NEXUS';
  ob_start();
?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Pending Mentorship Cancellations</h3>
    <a href="/admin" class="btn btn-sm btn-outline-secondary">Back to Admin</a>
  </div>
  <?php if (!$items): ?>
    <div class="alert alert-info">No pending cancellation requests.</div>
  <?php else: ?>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>#</th>
          <th>Request ID</th>
          <th>Requested By</th>
          <th>Role</th>
          <th>Reason</th>
          <th>Requested At</th>
          <th>Ends</th>
          <th>Days Left</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i): ?>
          <tr>
            <td><?= (int)$i['cancellation_id'] ?></td>
            <td><?= (int)$i['request_id'] ?></td>
            <td><?= htmlspecialchars($i['requester_email'] ?? '') ?></td>
            <td><?= htmlspecialchars($i['requested_by_role'] ?? '') ?></td>
            <td><?= htmlspecialchars($i['reason'] ?? '') ?></td>
            <td><?= htmlspecialchars($i['created_at'] ?? '') ?></td>
            <td><?= htmlspecialchars($i['end_date'] ?? '') ?></td>
            <td>
              <?php
                $daysLeft = '';
                if (!empty($i['end_date'])) {
                  $e = strtotime((string)$i['end_date']);
                  if ($e) {
                    $diff = floor(($e - time()) / 86400);
                    $daysLeft = $diff >= 0 ? $diff : 0;
                  }
                }
                echo $daysLeft === '' ? '<span class="text-muted">—</span>' : (int)$daysLeft;
              ?>
            </td>
            <td>
              <a class="btn btn-sm btn-outline-secondary" href="/mentorship/listing/<?= (int)($i['listing_id'] ?? 0) ?>/requests">View Listing</a>
              <form method="post" action="/admin/mentorship/cancellations/<?= (int)$i['cancellation_id'] ?>/approve" class="d-inline" onsubmit="return confirm('Approve cancellation? This will cancel the mentorship and issue refunds if applicable.');">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                <button class="btn btn-sm btn-success">Approve</button>
              </form>
              <form method="post" action="/admin/mentorship/cancellations/<?= (int)$i['cancellation_id'] ?>/reject" class="d-inline" onsubmit="return confirm('Reject this cancellation request?');">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                <button class="btn btn-sm btn-outline-danger">Reject</button>
              </form>
              <form method="post" action="/admin/mentorship/request/<?= (int)$i['request_id'] ?>/cancel" class="d-inline" onsubmit="return confirm('Cancel directly (bypass request)?');">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                <button class="btn btn-sm btn-outline-warning">Direct Cancel</button>
              </form>
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
