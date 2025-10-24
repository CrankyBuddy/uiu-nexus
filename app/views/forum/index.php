<?php
  $title = 'Forum | UIU NEXUS';
  ob_start();
  $pendingMine = 0; $pendingAll = 0; $rejectedMine = 0; $rejectedAll = 0; $approvedNew = 0; $isAdmin = false;
  try {
    $cfg = $GLOBALS['config'] ?? null;
    if ($cfg instanceof \Nexus\Core\Config) {
      $uid = (int)(\Nexus\Helpers\Auth::id() ?? 0);
      $pendingMine = \Nexus\Models\ForumPost::countPending($cfg, $uid);
      $isAdmin = ((\Nexus\Helpers\Auth::user()['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($cfg, $uid, 'manage.permissions');
      // Freshly rejected since last seen (non-admin); admins never need rejected alerts
      if ($isAdmin) {
        $rejectedMine = 0;
      } else {
        $since = '';
        if (!empty($_SESSION['forum_seen_rejected_' . $uid])) {
          $since = (string)$_SESSION['forum_seen_rejected_' . $uid];
        } else {
          $lastSeen = \Nexus\Models\SystemSetting::get($cfg, 'forum_seen_rejected_' . $uid);
          if ($lastSeen && isset($lastSeen['setting_value'])) { $since = trim((string)$lastSeen['setting_value']); }
        }
        $pdo = \Nexus\Core\Database::pdo($cfg);
        $sinceParam = ($since !== '') ? $since : '1970-01-01 00:00:00';
        $st = $pdo->prepare("SELECT COUNT(*) FROM forum_posts WHERE author_id = :u AND moderation_status = 'rejected' AND COALESCE(rejected_at, updated_at) > :t");
        $st->execute([':u' => $uid, ':t' => $sinceParam]);
        $rejectedMine = (int)($st->fetchColumn() ?: 0);
      }
      // Freshly approved since last seen (non-admin badge on Approved button)
      if (!$isAdmin) {
        $sinceA = '';
        if (!empty($_SESSION['forum_seen_approved_' . $uid])) {
          $sinceA = (string)$_SESSION['forum_seen_approved_' . $uid];
        } else {
          $lastSeenA = \Nexus\Models\SystemSetting::get($cfg, 'forum_seen_approved_' . $uid);
          if ($lastSeenA && isset($lastSeenA['setting_value'])) { $sinceA = trim((string)$lastSeenA['setting_value']); }
        }
        $pdo = $pdo ?? \Nexus\Core\Database::pdo($cfg);
        $sinceParamA = ($sinceA !== '') ? $sinceA : '1970-01-01 00:00:00';
        $stA = $pdo->prepare("SELECT COUNT(*) FROM forum_posts WHERE author_id = :u AND (moderation_status = 'approved' OR (moderation_status IS NULL AND is_approved = 1)) AND COALESCE(approved_at, updated_at) > :t");
        $stA->execute([':u' => $uid, ':t' => $sinceParamA]);
        $approvedNew = (int)($stA->fetchColumn() ?: 0);
      }
  if ($isAdmin) { $pendingAll = \Nexus\Models\ForumPost::countPending($cfg, null); $rejectedAll = \Nexus\Models\ForumPost::countRejected($cfg, null); }
    }
  } catch (\Throwable $e) {}
?>
<div class="row">
  <div class="col-lg-8">
  <h2 class="mb-3">Recent Posts</h2>
    <?php if (empty($recent)): ?>
      <div class="alert alert-secondary">No questions yet.</div>
    <?php else: ?>
      <?php foreach ($recent as $q): ?>
        <div class="card mb-2">
          <div class="card-body">
            <a class="h5 d-block mb-1" href="/forum/post/<?= (int)$q['post_id'] ?>"><?= htmlspecialchars($q['title'] ?? 'Untitled') ?></a>
            <div class="small text-muted">By <?= htmlspecialchars($q['author_name'] ?? ($q['author_email'] ?? 'user')) ?> on <?= htmlspecialchars((string)($q['created_at'] ?? '')) ?> • Category: <?= htmlspecialchars($q['category_name'] ?? '') ?> • Up: <?= (int)$q['upvote_count'] ?> • Down: <?= (int)$q['downvote_count'] ?> • Views: <?= (int)$q['view_count'] ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="/forum/create" class="btn btn-sm btn-dark">Create Post</a>
        <a href="/forum/pending" class="btn btn-sm btn-outline-dark position-relative">Pending
          <?php if ($isAdmin && (int)$pendingAll > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= (int)$pendingAll ?></span>
          <?php endif; ?>
        </a>
        <a href="/forum/pending?tab=approved" class="btn btn-sm btn-outline-dark position-relative">Approved
          <?php if (!$isAdmin && $approvedNew > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= (int)$approvedNew ?></span><?php endif; ?>
        </a>
        <a href="/forum/pending?tab=rejected" class="btn btn-sm btn-outline-dark position-relative">Rejected
          <?php if ($rejectedMine > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $rejectedMine ?></span><?php endif; ?>
        </a>
        <?php if ($isAdmin): ?>
        <?php endif; ?>
    </div>
    <h5 class="mb-2">Categories</h5>
    <div class="list-group">
      <?php foreach ($categories as $c): ?>
        <a class="list-group-item list-group-item-action" href="/forum/category/<?= (int)$c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
