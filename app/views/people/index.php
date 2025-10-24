<?php
  $title = 'People | UIU NEXUS';
  ob_start();
  $me = \Nexus\Helpers\Auth::user();
  // Prefer controller-provided isAdmin; fallback here if missing
  $isAdmin = ($isAdmin ?? ((($me['role'] ?? '') === 'admin')));
?>
<div class="row">
  <div class="col-lg-10 mx-auto">
    <h2 class="mb-3">People Directory</h2>
    <form method="get" class="row g-2 mb-3">
      <div class="col-md-4">
        <input class="form-control" type="text" name="q" placeholder="Name or email" value="<?= htmlspecialchars($q ?? '') ?>">
      </div>
      <div class="col-md-3">
        <select class="form-select" name="role">
          <?php $r = $role ?? ''; ?>
          <option value="">All roles</option>
          <option value="student" <?= $r==='student'?'selected':'' ?>>Student</option>
          <option value="alumni" <?= $r==='alumni'?'selected':'' ?>>Alumni</option>
          <option value="recruiter" <?= $r==='recruiter'?'selected':'' ?>>Recruiter</option>
          <option value="admin" <?= $r==='admin'?'selected':'' ?>>Admin</option>
        </select>
      </div>
      <div class="col-md-3">
        <input class="form-control" type="text" name="skill" placeholder="Skills (comma-separated, partial)" value="<?= htmlspecialchars($skill ?? '') ?>">
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-dark" type="submit">Filter</button>
      </div>
    </form>

    <div class="mb-3 text-muted">Showing <?= (int)$total ?> result(s)</div>

    <?php foreach (($people ?? []) as $p): ?>
      <?php $qs = http_build_query(['q'=>$q,'role'=>$role,'skill'=>$skill,'page'=>$page]); ?>
      <div class="card mb-2">
        <div class="card-body d-flex align-items-center gap-3">
          <?php $pic = $p['profile_picture_url'] ?? null; ?>
          <?php if (!empty($pic)): ?>
            <img class="nx-avatar" src="<?= htmlspecialchars($pic) ?>" alt="avatar">
          <?php else: ?>
            <div class="nx-avatar nx-avatar-initials" aria-hidden="true">👤</div>
          <?php endif; ?>
          <div class="flex-fill">
            <div class="d-flex justify-content-between">
              <div>
                <strong><?= htmlspecialchars(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')) ?></strong>
                <span class="ms-2 badge bg-light text-dark"><?= htmlspecialchars($p['role'] ?? '') ?></span>
                <div class="text-muted small">
                  <?php if ($isAdmin): ?>
                    <?= htmlspecialchars($p['email'] ?? '') ?>
                  <?php else: ?>
                    <?php
                      $viewerId = (int)(\Nexus\Helpers\Auth::id() ?? 0);
                      $viewerRole = (string)($me['role'] ?? '');
                      $subjectId = (int)($p['user_id'] ?? 0);
                      $canSeeEmail = \Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $viewerId, $viewerRole, $subjectId, 'email');
                    ?>
                    <?= $canSeeEmail ? htmlspecialchars($p['email'] ?? '') : '<span class="text-muted">Hidden</span>' ?>
                  <?php endif; ?>
                </div>
              </div>
              <div class="text-end d-flex gap-2 align-items-start">
                <?php if (!empty($p['user_id'])): ?>
                  <a class="btn btn-sm btn-outline-dark" href="/u/<?= (int)$p['user_id'] ?>">View Profile</a>
                  <a class="btn btn-sm btn-outline-dark" href="/messages/new?user_id=<?= (int)$p['user_id'] ?>">Message</a>
                <?php else: ?>
                  <a class="btn btn-sm btn-outline-dark" href="/messages/new">Message</a>
                <?php endif; ?>
                <?php if (!$isAdmin): ?>
                  <a class="btn btn-sm btn-outline-warning" href="/report?target_type=user&target_id=<?= (int)$p['user_id'] ?>">Report</a>
                <?php endif; ?>
                <?php if ($isAdmin && in_array(($p['role'] ?? ''), ['student','alumni'], true)): ?>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Coins</button>
                    <div class="dropdown-menu p-3" style="min-width: 260px;">
                      <div class="small text-muted mb-2">Current balance: <?= (int)($p['wallet_balance'] ?? 0) ?> coins</div>
                      <form method="post" action="/admin/users/wallet-adjust" class="vstack gap-2">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>" />
                        <input type="hidden" name="user_id" value="<?= (int)$p['user_id'] ?>" />
                        <input type="hidden" name="return_to" value="/people<?= $qs ? ('?' . htmlspecialchars($qs)) : '' ?>" />
                        <div class="d-flex gap-2">
                          <input type="number" name="amount" min="1" class="form-control form-control-sm" placeholder="Amount" required>
                          <select name="direction" class="form-select form-select-sm" style="width:auto;">
                            <option value="credit">Add</option>
                            <option value="debit">Deduct</option>
                          </select>
                        </div>
                        <input type="text" name="note" class="form-control form-control-sm" placeholder="Note (optional)">
                        <button class="btn btn-sm btn-dark" type="submit">Apply</button>
                      </form>
                    </div>
                  </div>
                <?php endif; ?>
                <?php if ($isAdmin && (($p['role'] ?? '') !== 'admin')): ?>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Suspend</button>
                    <div class="dropdown-menu p-3" style="min-width: 280px;">
                      <form method="post" action="/admin/users/suspend" class="vstack gap-2">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>" />
                        <input type="hidden" name="user_id" value="<?= (int)$p['user_id'] ?>" />
                        <input type="hidden" name="return_to" value="/people<?= $qs ? ('?' . htmlspecialchars($qs)) : '' ?>" />
                        <label class="form-label small">Scope</label>
                        <select class="form-select form-select-sm" name="scope">
                          <option value="platform">Platform (all)</option>
                          <option value="social">Forum & social</option>
                          <option value="chat">Chat only</option>
                          <option value="mentorship">Mentorship</option>
                        </select>
                        <label class="form-label small mt-1">Until (optional)</label>
                        <input type="datetime-local" class="form-control form-control-sm" name="until" />
                        <input type="text" class="form-control form-control-sm" name="reason" placeholder="Reason (optional)" />
                        <button class="btn btn-sm btn-danger" type="submit">Apply</button>
                      </form>
                      <div class="dropdown-divider"></div>
                      <div class="small text-muted mb-1">Ban (permanent)</div>
                      <form method="post" action="/admin/users/suspend" class="vstack gap-2">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>" />
                        <input type="hidden" name="user_id" value="<?= (int)$p['user_id'] ?>" />
                        <input type="hidden" name="return_to" value="/people<?= $qs ? ('?' . htmlspecialchars($qs)) : '' ?>" />
                        <input type="hidden" name="ban" value="1" />
                        <label class="form-label small">Scope</label>
                        <select class="form-select form-select-sm" name="scope">
                          <option value="platform">Platform (all)</option>
                          <option value="social">Forum & social</option>
                          <option value="chat">Chat only</option>
                          <option value="mentorship">Mentorship</option>
                        </select>
                        <input type="text" class="form-control form-control-sm" name="reason" placeholder="Reason (optional)" />
                        <button class="btn btn-sm btn-danger" type="submit">Ban</button>
                      </form>
                      <div class="dropdown-divider"></div>
                      <form method="post" action="/admin/users/lift" class="vstack gap-2">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>" />
                        <input type="hidden" name="user_id" value="<?= (int)$p['user_id'] ?>" />
                        <input type="hidden" name="return_to" value="/people<?= $qs ? ('?' . htmlspecialchars($qs)) : '' ?>" />
                        <label class="form-label small">Unban scope</label>
                        <select class="form-select form-select-sm" name="scope">
                          <option value="platform">Platform</option>
                          <option value="social">Forum & social</option>
                          <option value="chat">Chat</option>
                          <option value="mentorship">Mentorship</option>
                        </select>
                        <button class="btn btn-sm btn-secondary" type="submit">Lift</button>
                      </form>
                    </div>
                  </div>
                  <a class="btn btn-sm btn-outline-secondary" href="/admin/users/<?= (int)$p['user_id'] ?>/restrictions">History</a>
                <?php endif; ?>
              </div>
            </div>
            <?php if (!empty($p['bio'])): ?>
              <div class="mt-2 small"><?= nl2br(htmlspecialchars((string)$p['bio'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($p['skills'])): ?>
              <div class="mt-2">
                <?php foreach ($p['skills'] as $sn): ?>
                  <span class="badge rounded-pill text-bg-secondary me-1"><?= htmlspecialchars((string)$sn) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($p['badges'])): ?>
              <div class="mt-2">
                <?php foreach ($p['badges'] as $b): ?>
                  <?php
                    $level = strtolower((string)($b['level'] ?? ''));
                    $cls = match ($level) {
                      'gold' => 'text-bg-warning',
                      'silver' => 'text-bg-secondary',
                      'bronze' => 'text-bg-info',
                      default => 'text-bg-light',
                    };
                  ?>
                  <span class="badge <?= $cls ?> me-1"><?= htmlspecialchars((string)($b['badge_name'] ?? 'Badge')) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <?php if ($isAdmin && in_array(($p['role'] ?? ''), ['student','alumni'], true)): ?>
              <div class="mt-2 text-muted small">Coins: <?= (int)($p['wallet_balance'] ?? 0) ?> | Earned: <?= (int)($p['wallet_total_earned'] ?? 0) ?> | Spent: <?= (int)($p['wallet_total_spent'] ?? 0) ?></div>
            <?php endif; ?>
      <?php if (($isAdmin ?? false) && (($p['role'] ?? '') !== 'admin')):
                $uid = (int)($p['user_id'] ?? 0);
                $rmap = $restrictions[$uid] ?? [];
            ?>
              <div class="mt-2">
                <?php if (!empty($rmap)): ?><span class="badge bg-danger-subtle text-danger border border-danger-subtle me-1">Restricted</span><?php endif; ?>
                <?php if (!empty($rmap['platform'])): ?><span class="badge text-bg-danger me-1">Platform</span><?php endif; ?>
                <?php if (!empty($rmap['social'])): ?><span class="badge text-bg-warning me-1">Social</span><?php endif; ?>
                <?php if (!empty($rmap['chat'])): ?><span class="badge text-bg-warning me-1">Chat</span><?php endif; ?>
                <?php if (!empty($rmap['mentorship'])): ?><span class="badge text-bg-warning me-1">Mentorship</span><?php endif; ?>
                <?php $rc = $restrictionCounts[$uid] ?? ['ban'=>0,'suspend'=>0,'lift'=>0]; ?>
                <span class="badge bg-light text-dark border ms-2">Bans: <?= (int)$rc['ban'] ?> | Suspensions: <?= (int)$rc['suspend'] ?> | Lifts: <?= (int)$rc['lift'] ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if (($totalPages ?? 1) > 1): ?>
      <nav class="mt-3">
        <ul class="pagination">
          <?php $cur = (int)($page ?? 1); for ($i=1; $i<=$totalPages; $i++): ?>
            <?php $qs = http_build_query(['q'=>$q,'role'=>$role,'skill'=>$skill,'page'=>$i]); ?>
            <li class="page-item <?= $i===$cur ? 'active' : '' ?>">
              <a class="page-link" href="?<?= htmlspecialchars($qs) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
  
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
