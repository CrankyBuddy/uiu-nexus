<?php
  $title = 'Forum — ' . htmlspecialchars($category['category_name'] ?? 'Category');
  ob_start();
  $pendingMine = 0; $pendingAll = 0; $rejectedMine = 0; $rejectedAll = 0; $isAdmin = false;
  try {
    $cfg = $GLOBALS['config'] ?? null;
    if ($cfg instanceof \Nexus\Core\Config) {
      $uid = (int)(\Nexus\Helpers\Auth::id() ?? 0);
      $pendingMine = \Nexus\Models\ForumPost::countPending($cfg, $uid);
      $rejectedMine = \Nexus\Models\ForumPost::countRejected($cfg, $uid);
      $isAdmin = ((\Nexus\Helpers\Auth::user()['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($cfg, $uid, 'manage.permissions');
      if ($isAdmin) { $pendingAll = \Nexus\Models\ForumPost::countPending($cfg, null); $rejectedAll = \Nexus\Models\ForumPost::countRejected($cfg, null); }
    }
  } catch (\Throwable $e) {}
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="m-0">Category: <?= htmlspecialchars($category['category_name'] ?? '') ?></h2>
  <div class="d-flex gap-2">
    <a class="btn btn-sm btn-dark" href="/forum/create">Create Post</a>
    <a class="btn btn-sm btn-outline-dark position-relative" href="/forum/pending">Pending
      <?php if ($pendingMine > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $pendingMine ?></span><?php endif; ?>
    </a>
    <a class="btn btn-sm btn-outline-dark position-relative" href="/forum/pending?tab=rejected">Rejected
      <?php if ($rejectedMine > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $rejectedMine ?></span><?php endif; ?>
    </a>
    <?php if ($isAdmin): ?>
    <?php endif; ?>
  </div>
  </div>
<?php if (empty($posts)): ?>
  <div class="alert alert-secondary">No questions yet.</div>
<?php else: ?>
  <?php foreach ($posts as $q): ?>
    <div class="card mb-2">
      <div class="card-body">
  <a class="h5 d-block mb-1" href="/forum/post/<?= (int)$q['post_id'] ?>"><?= htmlspecialchars($q['title'] ?? 'Untitled') ?></a>
  <div class="small text-muted">By <?= htmlspecialchars($q['author_name'] ?? ($q['author_email'] ?? 'user')) ?> on <?= htmlspecialchars((string)($q['created_at'] ?? '')) ?> • Up: <?= (int)$q['upvote_count'] ?> • Down: <?= (int)$q['downvote_count'] ?> • Views: <?= (int)$q['view_count'] ?></div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<div class="mt-3 d-flex gap-2">
  <?php $prev = max(1, (int)($page ?? 1) - 1); $next = (int)($page ?? 1) + 1; ?>
  <a class="btn btn-sm btn-outline-dark<?= (isset($page) && $page <= 1) ? ' disabled' : '' ?>" href="?page=<?= $prev ?>" tabindex="<?= (isset($page) && $page <= 1) ? '-1' : '0' ?>">Previous</a>
  <?php if (!empty($hasNext)): ?>
    <a class="btn btn-sm btn-outline-dark" href="?page=<?= $next ?>">Next</a>
  <?php endif; ?>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
