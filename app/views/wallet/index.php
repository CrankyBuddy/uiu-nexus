<?php
  $title = 'My Wallet | UIU NEXUS';
  ob_start();
?>
<div class="row">
  <div class="col-lg-7">
    <h2 class="mb-3">My Wallet</h2>
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted">Balance</div>
            <div class="h3 mb-0"><?= htmlspecialchars((string)($wallet['balance'] ?? 0)) ?> coins</div>
          </div>
          <span class="badge badge-nexus">Reputation: <?= htmlspecialchars((string)($wallet['reputation_score'] ?? 0)) ?></span>
        </div>
      </div>
    </div>

    <?php if (!empty($userBadges) && is_array($userBadges)): ?>
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="mb-3">My Badges</h5>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($userBadges as $b): ?>
            <?php
              $level = strtolower((string)($b['level'] ?? ''));
              $cls = match ($level) {
                'gold' => 'text-bg-warning',
                'silver' => 'text-bg-secondary',
                'bronze' => 'text-bg-info',
                default => 'text-bg-light',
              };
            ?>
            <span class="badge <?= $cls ?>"><?php echo htmlspecialchars((string)($b['badge_name'] ?? 'Badge')); ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (isset($freeTickets)): ?>
    <div class="card mb-3">
      <div class="card-body d-flex align-items-center justify-content-between">
        <div>
          <div class="text-muted">Free Mentorship Tickets</div>
          <div class="h5 mb-0"><?= (int)$freeTickets ?> tickets</div>
          <div class="text-muted small">Accumulates by +3 every 4 months (unused tickets carry over)</div>
        </div>
        <?php if (!empty($freeResetAt)): ?>
          <span class="text-muted small">Last refresh: <?= htmlspecialchars((string)$freeResetAt) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Recent Transactions</h5>
        <?php if (empty($transactions)): ?>
          <div class="text-muted">No transactions yet.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table">
            <thead><tr>
              <th>Type</th>
              <th style="white-space:nowrap;word-break:keep-all;hyphens:none;">Amount</th>
              <th>Description</th>
              <th>Date</th>
            </tr></thead>
            <tbody>
              <?php foreach ($transactions as $t): ?>
                <tr>
                  <td><?= htmlspecialchars($t['type_name']) ?></td>
                  <td class="<?= $t['is_earning'] ? 'text-success' : 'text-danger' ?>">
                    <?= $t['is_earning'] ? '+' : '-' ?><?= htmlspecialchars((string)$t['amount']) ?>
                  </td>
                  <td><?= htmlspecialchars($t['description'] ?? '') ?></td>
                  <td><?= htmlspecialchars($t['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-body">
        <h5 class="mb-3">Recent Reputation Changes</h5>
        <?php if (empty($reputationEvents)): ?>
          <div class="text-muted">No reputation activity yet.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table">
            <thead><tr>
              <th style="white-space:nowrap;word-break:keep-all;hyphens:none;">Change</th>
              <th>Description</th>
              <th>Date</th>
            </tr></thead>
            <tbody>
              <?php foreach ($reputationEvents as $event): ?>
                <?php
                  $delta = (int)($event['delta'] ?? 0);
                  $refType = (string)($event['reference_entity_type'] ?? '');
                  $refId = $event['reference_entity_id'] ?? null;
                  $source = trim((string)($event['source'] ?? ''));
                  $entityLabel = match ($refType) {
                    'forum_post' => 'post',
                    'forum_answer' => 'answer',
                    'forum_comment' => 'comment',
                    'mentorship_session' => 'mentorship session',
                    'mentorship_request' => 'mentorship request',
                    'mentorship_feedback' => 'mentorship feedback',
                    default => strtolower(str_replace('_', ' ', $refType)),
                  };
                  $refTag = ($refId !== null && $refId !== '') ? ' #' . (string)$refId : '';
                  $description = '';

                  switch ($source) {
                    case 'forum:vote:up':
                      $description = 'Reward for ' . ($refType === 'forum_post' ? 'post' : ($refType === 'forum_comment' ? 'comment' : 'answer')) . ' upvote';
                      break;
                    case 'forum:vote:down':
                      $description = 'Penalty from ' . ($refType === 'forum_post' ? 'post' : ($refType === 'forum_comment' ? 'comment' : 'answer')) . ' downvote';
                      break;
                    case 'forum:vote:switch_up':
                      $description = 'Downvote reversed to upvote on your ' . ($refType === 'forum_post' ? 'post' : ($refType === 'forum_comment' ? 'comment' : 'answer')) . $refTag;
                      break;
                    case 'forum:vote:switch_down':
                      $description = 'Upvote reversed to downvote on your ' . ($refType === 'forum_post' ? 'post' : ($refType === 'forum_comment' ? 'comment' : 'answer')) . $refTag;
                      break;
                    case 'forum:vote:voter:down':
                      $description = 'Reputation cost for downvoting this ' . ($refType === 'forum_post' ? 'post' : ($refType === 'forum_comment' ? 'comment' : 'answer'));
                      break;
                    case 'forum:vote:voter:switch_down':
                      $description = 'Reputation cost for switching to a downvote on this ' . ($refType === 'forum_post' ? 'post' : ($refType === 'forum_comment' ? 'comment' : 'answer'));
                      break;
                    case 'forum:best_answer:answerer':
                      $description = 'Your answer was marked as the best answer' . $refTag;
                      break;
                    case 'forum:best_answer:asker':
                      $description = 'You accepted a best answer for your question' . $refTag;
                      break;
                    case 'mentorship:request:accepted':
                      $description = 'Mentorship request accepted' . $refTag;
                      break;
                    case 'mentorship:session:completed':
                      $description = 'Mentorship session completed' . $refTag;
                      break;
                    case 'account:registration_bonus':
                      $description = 'Registration bonus';
                      break;
                  }

                  if ($description === '') {
                    // Voter cost legacy fallback
                    if ($refType === 'forum_vote' && $delta < 0) {
                      $description = 'Reputation cost for downvoting';
                    }
                    // Forum content fallbacks (mirror transaction phrasing)
                    elseif ($refType !== '' && strpos($refType, 'forum_') === 0) {
                      $noun = $refType === 'forum_post' ? 'post' : ($refType === 'forum_comment' ? 'comment' : 'answer');
                      if ($delta > 0) {
                        $description = 'Reward for ' . $noun . ' upvote';
                      } elseif ($delta < 0) {
                        $description = 'Penalty for ' . $noun . ' downvote';
                      }
                    } elseif ($entityLabel !== '') {
                      $description = ucwords($entityLabel . $refTag);
                    }
                  }

                  // Append friendly reference title for clarity if available
                  $refTitle = $event['ref_title'] ?? '';
                  if ($refTitle !== '') {
                    $description .= ' — "' . htmlspecialchars((string)$refTitle) . '"';
                  }

                  if ($description === '') {
                    if ($source !== '') {
                      $description = ucwords(str_replace([':', '_'], ' ', $source));
                    } else {
                      $description = $delta >= 0 ? 'Reputation increase' : 'Reputation decrease';
                    }
                  }
                ?>
                <tr>
                  <td class="<?= $delta >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $delta >= 0 ? '+' : '' ?><?= htmlspecialchars((string)$delta) ?>
                  </td>
                  <td><?= htmlspecialchars($description) ?></td>
                  <td><?= htmlspecialchars((string)($event['created_at'] ?? '')) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-5 mt-4 mt-lg-0">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="mb-3">Reputation, Badges & Coins – Policy Summary</h5>
        <h6 class="mt-3 mb-2">Reputation Rules</h6>
        <ul class="mb-2">
          <li>All new students/alumni start with <strong>10 reputation</strong>.</li>
          <li>Recruiters/staff don’t use reputation.</li>
          <li>Upvotes: <strong>+1</strong> (questions/discussions), <strong>+2</strong> (answers).</li>
          <li>Downvotes: <strong>−1</strong> (to post author); voters need <strong>50+ rep</strong> to downvote.</li>
          <li>Changing votes removes the old score.</li>
          <li>Best Answer: <strong>+5</strong> to answerer, <strong>+2</strong> to question author.</li>
          <li>Paid mentorships:
            <ul>
              <li>Accepting request: <strong>+2</strong> (student)</li>
              <li>Completing session: <strong>+10</strong> (mentor)</li>
            </ul>
          </li>
          <li>Minimum rep = 0 (no negatives).</li>
          <li>Downvoting costs <strong>−1</strong> to the voter if it remains a downvote.</li>
        </ul>
        <h6 class="mt-3 mb-2">Badge Rules</h6>
        <ul class="mb-2">
          <li>Each badge requires a specific reputation threshold.</li>
          <li>On any reputation change, system checks and auto-awards new badges (once per badge).</li>
          <li>Updated thresholds apply next time your rep changes.</li>
          <li>Only reputation changes affect badges — no other triggers.</li>
        </ul>
        <div class="mb-2">
          <strong>Student Badges:</strong>
          <ul class="mb-1">
            <li>Newcomer — 10 rep</li>
            <li>Active Learner — 50 rep</li>
            <li>Helpful Peer — 150 rep</li>
            <li>Problem Solver — 350 rep</li>
            <li>Rising Scholar — 700 rep</li>
            <li>Campus Leader — 1200 rep</li>
          </ul>
          <strong>Alumni Badges:</strong>
          <ul>
            <li>Supportive Alum — 25 rep</li>
            <li>Career Guide — 100 rep</li>
            <li>Trusted Mentor — 300 rep</li>
            <li>Senior Mentor — 600 rep</li>
            <li>Community Champion — 1000 rep</li>
            <li>Industry Expert — 1600 rep</li>
          </ul>
        </div>
        <h6 class="mt-3 mb-2">Coin Policy</h6>
        <ul class="mb-2">
          <li>Mentorship completion (mentor): <strong>+escrow amount</strong> for that mentorship (default 200 if not overridden).</li>
          <li>Mentorship refund (mentee): <strong>+escrow amount</strong> (only when an accepted paid mentorship is cancelled by admin).</li>
          <li>Forum upvote on your post/answer: <strong>+2 coins</strong>.</li>
          <li>Forum Best Answer: <strong>+10 coins</strong>.</li>
          <li>Daily check-in/quest: <strong>+5 coins</strong> (once per day).</li>
          <li>Signup/Onboarding bonus: <strong>+50 coins</strong> (once per user).</li>
          <li>Admin/System grant: variable (admin-defined on credit).</li>
        </ul>
        <div class="text-muted small mt-3">Note: Reputation can’t fall below 0. Only reputation changes affect badges.</div>
      </div>
    </div>
  </div>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
