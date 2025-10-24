<?php /** @var array $requests */ /** @var array $listing */
  $title = 'Mentorship Listing Requests | UIU NEXUS';
  ob_start();
?>
  <h3>Requests for Listing #<?= (int)$listing['listing_id'] ?> <?= !empty($isAdmin) ? '<span class="badge bg-secondary">Admin View</span>' : '' ?></h3>
  <?php if (!$requests): ?>
    <div class="alert alert-info">No requests yet.</div>
  <?php else: ?>
    <table class="table table-hover">
  <thead><tr><th>Student</th><th>CGPA</th><th>Bid</th><th>Status</th><th>Ends</th><th>Days Left</th><th>Motivation</th><th>Reserved</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
          <tr>
            <td><?= htmlspecialchars(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?></td>
            <td>
              <?php
                $viewerId = (int)(\Nexus\Helpers\Auth::id() ?? 0);
                $viewerRole = (string)(\Nexus\Helpers\Auth::user()['role'] ?? '');
                $subjectUserId = (int)($r['student_user_id'] ?? 0);
                $ctx = [
                  'mentorshipAccepted' => ($r['status'] ?? '') === 'accepted',
                  'listingHasMinCgpa' => !empty($listing['min_cgpa'])
                ];
                $canSeeCgpa = \Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $viewerId, $viewerRole, $subjectUserId, 'cgpa', $ctx);
                echo $canSeeCgpa ? htmlspecialchars((string)($r['student_cgpa'] ?? '')) : '<span class="text-muted">Hidden</span>';
              ?>
            </td>
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
            <td>
              <?php
                $msg = trim((string)($r['message'] ?? ''));
                if ($msg === '') {
                  echo '<span class="text-muted">—</span>';
                } else {
                  $preview = mb_strlen($msg) > 60 ? (mb_substr($msg, 0, 60) . '…') : $msg;
                  $cid = 'mot_' . (int)$r['request_id'];
                  echo '<div class="small">' . htmlspecialchars($preview) . '</div>';
                  echo '<a class="small" data-bs-toggle="collapse" href="#' . htmlspecialchars($cid) . '" role="button" aria-expanded="false" aria-controls="' . htmlspecialchars($cid) . '">View Motivation Letter</a>';
                  echo '<div class="collapse mt-1" id="' . htmlspecialchars($cid) . '"><div class="card card-body"><pre class="mb-0" style="white-space:pre-wrap; word-break:break-word;">' . htmlspecialchars($msg) . '</pre></div></div>';
                }
              ?>
            </td>
            <td>
              <?php if (!empty($r['reserved_until']) && strtotime((string)$r['reserved_until']) > time()): ?>
                <span class="badge bg-warning text-dark">Reserved until <?= htmlspecialchars(date('Y-m-d H:i', strtotime((string)$r['reserved_until']))) ?></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($r['status'] === 'pending'): ?>
                <form method="post" action="/mentorship/request/<?= (int)$r['request_id'] ?>/accept" class="d-inline">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                  <button class="btn btn-sm btn-success">Accept</button>
                </form>
                <form method="post" action="/mentorship/request/<?= (int)$r['request_id'] ?>/decline" class="d-inline">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                  <button class="btn btn-sm btn-outline-danger">Decline</button>
                </form>
                <?php
                  $reserved = !empty($r['reserved_until']) && strtotime((string)$r['reserved_until']) > time();
                ?>
                <?php if (!$reserved): ?>
                  <form method="post" action="/mentorship/request/<?= (int)$r['request_id'] ?>/reserve" class="d-inline">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                    <button class="btn btn-sm btn-warning">Reserve</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="/mentorship/request/<?= (int)$r['request_id'] ?>/release" class="d-inline">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                    <button class="btn btn-sm btn-outline-secondary">Release</button>
                  </form>
                  <form method="post" action="/mentorship/request/<?= (int)$r['request_id'] ?>/extend" class="d-inline">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                    <button class="btn btn-sm btn-outline-warning">Extend</button>
                  </form>
                <?php endif; ?>
              <?php elseif ($r['status'] === 'accepted'): ?>
                <form method="post" action="/mentorship/request/<?= (int)$r['request_id'] ?>/chat" class="d-inline">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                  <button class="btn btn-sm btn-primary">Chat</button>
                </form>
                <form method="post" action="/mentorship/request/<?= (int)$r['request_id'] ?>/cancel" class="d-inline ms-1" onsubmit="return confirm('Request admin to cancel this mentorship?');">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                  <input type="hidden" name="reason" value="Mentor requested to cancel (accepted)">
                  <button class="btn btn-sm btn-outline-danger">Request Cancel</button>
                </form>
                <?php if (!empty($isAdmin)): ?>
                <form method="post" action="/admin/mentorship/request/<?= (int)$r['request_id'] ?>/cancel" class="d-inline ms-1" onsubmit="return confirm('Admin: cancel this mentorship now? This will issue refunds if applicable.');">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                  <button class="btn btn-sm btn-danger">Admin Cancel</button>
                </form>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-muted">No actions</span>
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
