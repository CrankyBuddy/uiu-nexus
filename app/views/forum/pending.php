<?php
  $tab = $tab ?? 'pending';
    $titleMap = ['pending' => 'Pending Posts', 'rejected' => 'Rejected Posts', 'approved' => 'Approved Posts'];
    $title = ($titleMap[$tab] ?? 'Pending Posts') . ' | Forum';
  ob_start();
  use Nexus\Helpers\Csrf;
  $lsApproved = $last_seen_approved ?? null;
  $lsRejected = $last_seen_rejected ?? null;
?>
<style>
  /* Scoped styles for Pending/Rejected tab buttons */
  #forum-pending-tabs a.btn.btn-outline-dark.active {
    background-color: #f56726 !important;
    border-color: #f56726 !important;
    color: #fff !important;
  }
  #forum-pending-tabs a.btn.btn-outline-dark.active:hover {
    background-color: #fff !important;
    color: #f56726 !important;
    border-color: #f56726 !important;
  }
  /* Unread item highlight */
  .nx-unread {
    border-left: 4px solid #dc3545; /* red */
    background: #fff7f7;
  }
  .nx-unread .card-body { background: transparent; }
</style>
<div class="row">
  <div class="col-lg-9">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div>
          <strong><?= htmlspecialchars($titleMap[$tab] ?? 'Pending Posts') ?></strong>
        <div class="small text-muted">Total: <?= (int)($total ?? 0) ?></div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <form method="get" class="d-flex gap-2 align-items-center">
          <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
          <label class="small m-0">Per page</label>
          <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ([10,20,50,100] as $opt): ?>
              <option value="<?= $opt ?>"<?= ($opt == ($per_page ?? 20)) ? ' selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <?php if (empty($isAdmin) && ($tab === 'approved' || $tab === 'rejected')): ?>
          <form method="post" action="/forum/seen" class="m-0">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <input type="hidden" name="type" value="<?= htmlspecialchars($tab) ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary">Mark as Read</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <?php if (empty($pending)): ?>
      <div class="alert alert-secondary">No <?= $tab === 'rejected' ? 'rejected' : 'pending' ?> posts.</div>
    <?php else: ?>
        <?php if (!empty($isAdmin) && $tab === 'pending'): ?>
      <form id="bulkForm" method="post" action="/forum/post/bulk" class="mb-2 d-flex gap-2 align-items-center">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
        <select name="action" class="form-select form-select-sm" style="width:200px">
          <option value="approve">Bulk Approve</option>
          <option value="reject">Bulk Reject</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
      </form>
      <?php endif; ?>
        <?php foreach ($pending as $p): ?>
          <?php
            $isUnread = false;
            if (empty($isAdmin)) {
              if ($tab === 'rejected') {
                $rejAt = isset($p['rejected_at']) && $p['rejected_at'] ? strtotime((string)$p['rejected_at']) : null;
                if (!$rejAt && isset($p['updated_at']) && $p['updated_at']) { $rejAt = strtotime((string)$p['updated_at']); }
                $seenTs = $lsRejected ? strtotime((string)$lsRejected) : null;
                // If never seen, treat all as unread; else newer than seen is unread
                $isUnread = $seenTs ? ($rejAt && $rejAt > $seenTs) : true;
              } elseif ($tab === 'approved') {
                $appAt = isset($p['approved_at']) && $p['approved_at'] ? strtotime((string)$p['approved_at']) : null;
                if (!$appAt && isset($p['updated_at']) && $p['updated_at']) { $appAt = strtotime((string)$p['updated_at']); }
                $seenTs = $lsApproved ? strtotime((string)$lsApproved) : null;
                $isUnread = $seenTs ? ($appAt && $appAt > $seenTs) : true;
              }
            }
          ?>
          <div class="card mb-2<?= $isUnread ? ' nx-unread' : '' ?>">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div style="flex:1">
                  <div class="h5 mb-1"><?= htmlspecialchars($p['title'] ?? '(no title)') ?></div>
                  <div class="small text-muted mb-2">By <?= htmlspecialchars($p['author_name'] ?? ($p['author_email'] ?? 'user')) ?> on <?= htmlspecialchars((string)($p['created_at'] ?? '')) ?> • Category: <?= htmlspecialchars($p['category_name'] ?? '') ?></div>
                  <div class="mb-2"><?= nl2br(htmlspecialchars($p['content'] ?? '')) ?></div>
                  <?php if (($tab === 'rejected') && !empty($p['reject_reason'])): ?>
                    <div class="text-danger small">Reason: <?= htmlspecialchars($p['reject_reason']) ?></div>
                  <?php endif; ?>
                  <a class="btn btn-sm btn-outline-dark" href="/forum/post/<?= (int)$p['post_id'] ?>">Preview</a>
                </div>
                <div style="min-width:260px" class="ms-3">
                    <?php if (!empty($isAdmin) && $tab === 'pending'): ?>
                    <div class="d-flex flex-column gap-2">
                      <div class="d-flex gap-2">
                        <input type="checkbox" name="ids[]" value="<?= (int)$p['post_id'] ?>" form="bulkForm">
                        <form method="post" action="/forum/post/<?= (int)$p['post_id'] ?>/approve">
                          <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                          <button type="submit" class="btn btn-sm btn-success">Approve</button>
                        </form>
                        <form method="post" action="/forum/post/<?= (int)$p['post_id'] ?>/reject" class="d-flex align-items-center gap-2">
                          <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                          <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason (optional)" style="width:120px">
                          <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                        </form>
                      </div>
                      <div class="small text-muted">ID: <?= (int)$p['post_id'] ?></div>
                      <input type="text" name="reason_<?= (int)$p['post_id'] ?>" class="form-control form-control-sm" placeholder="Bulk reject reason (optional)" form="bulkForm">
                    </div>
                  <?php else: ?>
                    <div class="small text-muted">ID: <?= (int)$p['post_id'] ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <nav aria-label="pagination" class="mt-2">
        <ul class="pagination pagination-sm">
          <li class="page-item<?= ($page <= 1) ? ' disabled' : '' ?>"><a class="page-link" href="?tab=<?= htmlspecialchars($tab) ?>&per_page=<?= (int)($per_page ?? 20) ?>&page=<?= max(1, $page - 1) ?>">Previous</a></li>
          <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $pages ?></span></li>
          <?php if ($hasNext): ?><li class="page-item"><a class="page-link" href="?tab=<?= htmlspecialchars($tab) ?>&per_page=<?= (int)($per_page ?? 20) ?>&page=<?= $page + 1 ?>">Next</a></li><?php endif; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
  <div class="col-lg-3">
    <div id="forum-pending-tabs" class="d-grid gap-2">
        <a class="btn btn-sm btn-outline-dark w-100<?= $tab === 'pending' ? ' active' : '' ?>" href="/forum/pending">Pending</a>
        <a class="btn btn-sm btn-outline-dark w-100<?= $tab === 'approved' ? ' active' : '' ?>" href="/forum/pending?tab=approved">Approved</a>
        <a class="btn btn-sm btn-outline-dark w-100<?= $tab === 'rejected' ? ' active' : '' ?>" href="/forum/pending?tab=rejected">Rejected</a>
      <a class="btn btn-sm btn-outline-dark w-100" href="/forum">Back to Forum</a>
    </div>
  </div>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
