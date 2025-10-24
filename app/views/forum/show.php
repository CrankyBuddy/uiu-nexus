<?php
use Nexus\Helpers\Csrf;

$title = htmlspecialchars(($post['title'] ?? 'Question') . ' | Forum');
$currentVote = $currentVote ?? null;
$answerVotes = $answerVotes ?? [];
ob_start();
?>
<div class="row">
  <div class="col-lg-9">
    <div class="card mb-3">
      <div class="card-body">
  <h2 class="mb-2"><?= htmlspecialchars($post['title'] ?? 'Question') ?></h2>
  <div class="small text-muted mb-2">By <?= htmlspecialchars($post['author_name'] ?? ($post['author_email'] ?? ('user #' . (string)($post['author_id'] ?? '')))) ?> on <?= htmlspecialchars((string)($post['created_at'] ?? '')) ?>
    <?php if (!empty($post['author_id'])): ?>
      <a class="btn btn-sm btn-outline-secondary ms-2" href="/u/<?= (int)$post['author_id'] ?>">View Profile</a>
    <?php endif; ?>
  </div>
  <?php $isApproved = (int)($post['is_approved'] ?? 1) === 1; ?>
  <?php if (!$isApproved): ?>
    <div class="alert alert-warning py-2">This post is awaiting moderation and is only visible to you and moderators.</div>
  <?php elseif (($post['moderation_status'] ?? null) === 'rejected'): ?>
    <div class="alert alert-danger py-2">This post was rejected by moderators<?= !empty($post['reject_reason']) ? (': ' . htmlspecialchars($post['reject_reason'])) : '' ?>.</div>
  <?php endif; ?>
  <div class="mb-3"><?= nl2br(htmlspecialchars($post['content'] ?? '')) ?></div>
  <?php $viewerId = (int)(\Nexus\Helpers\Auth::id() ?? 0); ?>
  <?php $hasUpvoted = $currentVote === 'upvote'; $hasDownvoted = $currentVote === 'downvote'; ?>
        <?php if ($viewerId === 0): ?>
        <div class="alert alert-info py-2">Log in to upvote or downvote.</div>
        <?php elseif ($viewerId !== (int)($post['author_id'] ?? 0)): ?>
        <div class="vote-controls d-flex align-items-center gap-2">
          <form method="post" action="/forum/post/<?= (int)$post['post_id'] ?>/vote" class="vote-form" data-post-id="<?= (int)$post['post_id'] ?>">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <input type="hidden" name="vote" value="up">
            <button class="btn btn-sm <?= $hasUpvoted ? 'btn-success vote-btn vote-btn--active vote-btn--active-up' : 'btn-outline-success vote-btn' ?>" type="submit" data-vote="up" aria-pressed="<?= $hasUpvoted ? 'true' : 'false' ?>">
              <span class="vote-label"><?= $hasUpvoted ? 'Upvoted' : 'Upvote' ?></span>
              (<span class="count-up" data-post-id="<?= (int)$post['post_id'] ?>"><?= (int)$post['upvote_count'] ?></span>)
            </button>
          </form>
          <form method="post" action="/forum/post/<?= (int)$post['post_id'] ?>/vote" class="vote-form" data-post-id="<?= (int)$post['post_id'] ?>">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <input type="hidden" name="vote" value="down">
            <button class="btn btn-sm <?= $hasDownvoted ? 'btn-danger vote-btn vote-btn--active vote-btn--active-down' : 'btn-outline-danger vote-btn' ?>" type="submit" data-vote="down" aria-pressed="<?= $hasDownvoted ? 'true' : 'false' ?>">
              <span class="vote-label"><?= $hasDownvoted ? 'Downvoted' : 'Downvote' ?></span>
              (<span class="count-down" data-post-id="<?= (int)$post['post_id'] ?>"><?= (int)$post['downvote_count'] ?></span>)
            </button>
          </form>
        </div>
        <?php else: ?>
        <div class="alert alert-info py-2">You can’t vote on your own post.</div>
        <?php endif; ?>
        <?php if (((\Nexus\Helpers\Auth::user()['role'] ?? '') === 'admin' || \Nexus\Helpers\Gate::has($GLOBALS['config'], (int)(\Nexus\Helpers\Auth::id() ?? 0), 'manage.permissions'))): ?>
          <?php if (($post['moderation_status'] ?? 'pending') !== 'approved'): ?>
          <div class="mt-2 d-flex gap-2 align-items-center">
            <form method="post" action="/forum/post/<?= (int)$post['post_id'] ?>/approve">
              <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
              <button type="submit" class="btn btn-sm btn-success">Approve</button>
            </form>
            <form method="post" action="/forum/post/<?= (int)$post['post_id'] ?>/reject" class="d-flex align-items-center gap-2">
              <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
              <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason (optional)" style="width:220px">
              <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
            </form>
          </div>
          <?php endif; ?>
        <?php endif; ?>
        <div class="mt-2 d-flex gap-2">
          <?php $viewerIsAdmin = ((\Nexus\Helpers\Auth::user()['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($GLOBALS['config'], (int)(\Nexus\Helpers\Auth::id() ?? 0), 'manage.permissions'); ?>
          <?php if (!$viewerIsAdmin): ?>
          <a class="btn btn-sm btn-outline-warning" href="/report?target_type=post&target_id=<?= (int)$post['post_id'] ?>">Report</a>
          <?php endif; ?>
          <?php if ($viewerIsAdmin): ?>
          <form method="post" action="/forum/post/<?= (int)$post['post_id'] ?>/delete" onsubmit="return confirm('Delete this post? This cannot be undone.');">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
          <?php endif; ?>
          <?php
            $canDelete = false; $reported = false;
            try {
              $uid = (int)(\Nexus\Helpers\Auth::id() ?? 0);
              $isAdmin = ((\Nexus\Helpers\Auth::user()['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($GLOBALS['config'], $uid, 'manage.permissions');
              $canDelete = $isAdmin || ((int)($post['author_id'] ?? 0) === $uid);
              // Lightweight reported check
              $pdo = \Nexus\Core\Database::pdo($GLOBALS['config']);
              $st = $pdo->prepare("SELECT 1 FROM reports WHERE target_type='post' AND target_id = :p LIMIT 1");
              $st->execute([':p' => (int)($post['post_id'] ?? 0)]);
              $reported = (bool)$st->fetchColumn();
            } catch (\Throwable $e) {}
          ?>
          <?php if ($canDelete && !$reported && !$viewerIsAdmin): ?>
          <form method="post" action="/forum/post/<?= (int)$post['post_id'] ?>/delete" onsubmit="return confirm('Delete this post? This cannot be undone.');">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">Answers (<?= count($answers ?? []) ?>)</div>
      <div class="card-body">
        <?php if (empty($answers)): ?>
          <div class="text-muted">No answers yet.</div>
        <?php else: ?>
          <?php foreach ($answers as $a): ?>
            <?php $isBest = (int)($a['is_best_answer'] ?? 0) === 1; ?>
            <div class="mb-3 p-2 border rounded forum-answer <?= $isBest ? 'best' : '' ?>" data-answer-id="<?= (int)$a['post_id'] ?>">
              <div class="small text-muted mb-1">By <?= htmlspecialchars($a['author_email'] ?? 'user') ?> on <?= htmlspecialchars((string)($a['created_at'] ?? '')) ?></div>
              <div><?= nl2br(htmlspecialchars($a['content'] ?? '')) ?></div>
              <div class="small text-muted mt-1">Up: <span class="count-up" data-post-id="<?= (int)$a['post_id'] ?>"><?= (int)$a['upvote_count'] ?></span> • Down: <span class="count-down" data-post-id="<?= (int)$a['post_id'] ?>"><?= (int)$a['downvote_count'] ?></span>
                <?php if ($isBest): ?>
                  <span class="badge bg-success ms-2">Marked best by author</span>
                <?php endif; ?>
              </div>
              <?php $answerVote = $answerVotes[(int)$a['post_id']] ?? null; $ansUp = $answerVote === 'upvote'; $ansDown = $answerVote === 'downvote'; ?>
              <div class="vote-controls d-flex align-items-center gap-2 mt-2" data-answer-id="<?= (int)$a['post_id'] ?>">
                <?php if ($viewerId === 0): ?>
                <div class="alert alert-info py-2 mb-0">Log in to upvote or downvote.</div>
                <?php elseif ($viewerId !== (int)($a['author_id'] ?? 0)): ?>
                <form method="post" action="/forum/post/<?= (int)$a['post_id'] ?>/vote" class="vote-form" data-post-id="<?= (int)$a['post_id'] ?>">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                  <input type="hidden" name="vote" value="up">
                    <button class="btn btn-sm <?= $ansUp ? 'btn-success vote-btn vote-btn--active vote-btn--active-up' : 'btn-outline-success vote-btn' ?>" type="submit" data-vote="up" aria-pressed="<?= $ansUp ? 'true' : 'false' ?>">
                    <span class="vote-label"><?= $ansUp ? 'Upvoted' : 'Upvote' ?></span>
                    (<span class="count-up" data-post-id="<?= (int)$a['post_id'] ?>"><?= (int)$a['upvote_count'] ?></span>)
                  </button>
                </form>
                <form method="post" action="/forum/post/<?= (int)$a['post_id'] ?>/vote" class="vote-form" data-post-id="<?= (int)$a['post_id'] ?>">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                  <input type="hidden" name="vote" value="down">
                    <button class="btn btn-sm <?= $ansDown ? 'btn-danger vote-btn vote-btn--active vote-btn--active-down' : 'btn-outline-danger vote-btn' ?>" type="submit" data-vote="down" aria-pressed="<?= $ansDown ? 'true' : 'false' ?>">
                    <span class="vote-label"><?= $ansDown ? 'Downvoted' : 'Downvote' ?></span>
                    (<span class="count-down" data-post-id="<?= (int)$a['post_id'] ?>"><?= (int)$a['downvote_count'] ?></span>)
                  </button>
                </form>
                <?php else: ?>
                <div class="alert alert-info py-2 mb-0">You can’t vote on your own answer.</div>
                <?php endif; ?>
              </div>
              <?php if ((int)($post['author_id'] ?? 0) === (int)(\Nexus\Helpers\Auth::user()['user_id'] ?? 0) && (int)($a['is_best_answer'] ?? 0) !== 1): ?>
              <form method="post" action="/forum/answer/<?= (int)$a['post_id'] ?>/best" class="mt-2">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                <button class="btn btn-sm btn-outline-success" type="submit">Mark as Best</button>
              </form>
              <?php endif; ?>
              <?php if (!$viewerIsAdmin): ?>
              <div class="mt-2">
                <a class="btn btn-sm btn-outline-warning" href="/report?target_type=post&target_id=<?= (int)$a['post_id'] ?>">Report</a>
              </div>
              <?php endif; ?>
              <?php if ($viewerIsAdmin): ?>
              <form method="post" action="/forum/post/<?= (int)$a['post_id'] ?>/delete" class="mt-2" onsubmit="return confirm('Delete this answer? This cannot be undone.');">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Your Answer</div>
      <div class="card-body">
        <form method="post" action="/forum/post/<?= (int)$post['post_id'] ?>/answer">
          <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
          <div class="mb-3">
            <textarea required class="form-control" name="content" rows="5" placeholder="Write your answer..."></textarea>
          </div>
          <button class="btn btn-dark" type="submit">Add Your Reply</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-3">
    <a class="btn btn-sm btn-outline-dark w-100 mb-2" href="/forum">Back to Forum</a>
  </div>
</div>
<script>
  (function(){
    const BASE = window.__BASE__ || '';
    // Intercept vote forms for AJAX update
    function withBase(url){
      if (!url) return url;
      if (/^https?:\/\//i.test(url)) return url;
      const B = BASE || '';
      // Avoid double-prefix if server already rendered absolute path including BASE
      if (B && url.startsWith(B + '/')) return url;
      return (B ? B : '') + url;
    }
    document.addEventListener('submit', async function(e){
      const f = e.target;
      if (!(f instanceof HTMLFormElement)) return;
      const action = f.getAttribute('action') || '';
      if (!/\/forum\/post\/.+\/vote$/.test(action)) return;
      e.preventDefault();
      try {
        const fd = new FormData(f);
  const res = await fetch(withBase(action), { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
        if (!res.ok) return location.reload();
        const data = await res.json();
        if (!data || !data.ok) {
          if (data && data.error) {
            alert(data.error);
            return;
          }
          return location.reload();
        }
        // Update the nearest buttons that contain counts for this post_id
        // Find all forms targeting this post id
        const wrappers = document.querySelectorAll('form[action*="/forum/post/' + data.post_id + '/vote"]');
        const currentState = data.current_vote || null;
        wrappers.forEach((form) => {
          const btn = form.querySelector('button.vote-btn') || form.querySelector('button');
          const choice = (form.querySelector('input[name="vote"]')?.value || '').toLowerCase();
          const label = btn ? btn.querySelector('.vote-label') : null;
          if (btn) {
            btn.classList.remove('vote-btn--active', 'vote-btn--active-up', 'vote-btn--active-down', 'btn-success', 'btn-outline-success', 'btn-danger', 'btn-outline-danger');
            btn.setAttribute('aria-pressed', 'false');
            if (choice === 'up') {
              const span = btn.querySelector('.count-up');
              if (span) span.textContent = String(data.up);
              else btn.textContent = `Upvote (${data.up})`;
              if (label) label.textContent = currentState === 'upvote' ? 'Upvoted' : 'Upvote';
              if (currentState === 'upvote') {
                btn.classList.add('btn-success', 'vote-btn--active', 'vote-btn--active-up');
                btn.setAttribute('aria-pressed', 'true');
              } else {
                btn.classList.add('btn-outline-success');
              }
            } else if (choice === 'down') {
              const span = btn.querySelector('.count-down');
              if (span) span.textContent = String(data.down);
              else btn.textContent = `Downvote (${data.down})`;
              if (label) label.textContent = currentState === 'downvote' ? 'Downvoted' : 'Downvote';
              if (currentState === 'downvote') {
                btn.classList.add('btn-danger', 'vote-btn--active', 'vote-btn--active-down');
                btn.setAttribute('aria-pressed', 'true');
              } else {
                btn.classList.add('btn-outline-danger');
              }
            } else {
              // Unknown choice; restore outline styles based on original type
              if (form.querySelector('input[name="vote"]')?.value === 'up') {
                btn.classList.add('btn-outline-success');
              } else {
                btn.classList.add('btn-outline-danger');
              }
            }
          }
        });
        // Also update any passive count spans in the meta line
        document.querySelectorAll('.count-up[data-post-id="' + data.post_id + '"]').forEach(el => el.textContent = String(data.up));
        document.querySelectorAll('.count-down[data-post-id="' + data.post_id + '"]').forEach(el => el.textContent = String(data.down));
      } catch (_) {
        location.reload();
      }
    });

    // Intercept best answer form for AJAX update
    document.addEventListener('submit', async function(e){
      const f = e.target;
      if (!(f instanceof HTMLFormElement)) return;
      const action = f.getAttribute('action') || '';
      if (!/\/forum\/answer\/.+\/best$/.test(action)) return;
      e.preventDefault();
      try {
        const fd = new FormData(f);
  const res = await fetch(withBase(action), { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
        if (!res.ok) return location.reload();
        const data = await res.json();
        if (!data || !data.ok) return;
        // Remove previous best highlight and badge, if any
        if (data.prev_best_id) {
          const wrap = document.querySelector(`.forum-answer[data-answer-id="${data.prev_best_id}"]`);
          if (wrap) {
            wrap.classList.remove('best');
            const badge = wrap.querySelector('.badge.bg-success');
            if (badge) badge.remove();
            // Re-show the mark-as-best form for the previous best (if current user is author)
            const mab = wrap.querySelector('form[action$="/best"]');
            if (mab) mab.classList.remove('d-none');
          }
        }
        // Add best highlight and badge on the new best
        const curWrap = document.querySelector(`.forum-answer[data-answer-id="${data.best_id}"]`);
        if (curWrap) {
          curWrap.classList.add('best');
          // Add a Best badge if not present
          if (!curWrap.querySelector('.badge.bg-success')) {
            const meta = curWrap.querySelector('.small.text-muted.mt-1');
            if (meta) {
              const span = document.createElement('span');
              span.className = 'badge bg-success ms-2';
              span.textContent = 'Marked best by author';
              meta.appendChild(span);
            }
          }
          // Hide the mark-as-best button on the new best
          const mab = curWrap.querySelector('form[action$="/best"]');
          if (mab) mab.classList.add('d-none');
        }
      } catch (_) {
        location.reload();
      }
    });
  })();
</script>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
