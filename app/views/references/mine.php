<?php
  $title = 'My References';
  ob_start();
?>
<h2 class="mb-3">My References</h2>
<div class="mb-2"><a class="btn btn-sm btn-dark" href="/references/create">Add Reference</a></div>
<?php if (empty($references)): ?>
  <div class="alert alert-info">No references yet.</div>
<?php else: ?>
  <ul class="list-group">
    <?php foreach ($references as $r): ?>
      <li class="list-group-item d-flex justify-content-between align-items-start">
        <div>
          <div><strong>Mentor:</strong> <?= htmlspecialchars(trim(($r['alumni_first_name'] ?? '').' '.($r['alumni_last_name'] ?? ''))) ?></div>
          <div class="small">Status: <span class="badge <?= ($r['status']??'')==='active'?'text-bg-success':'text-bg-secondary' ?>"><?= htmlspecialchars((string)($r['status'] ?? '')) ?></span></div>
          <?php if (!empty($r['reference_text'])): ?>
            <div class="small text-muted">"<?= nl2br(htmlspecialchars((string)$r['reference_text'])) ?>"</div>
          <?php endif; ?>
        </div>
        <div class="ms-3">
          <form method="post" action="/references/<?= (int)$r['reference_id'] ?>/revoke" class="d-inline">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
            <button class="btn btn-sm btn-outline-warning" type="submit">Revoke</button>
          </form>
          <form method="post" action="/references/<?= (int)$r['reference_id'] ?>/delete" class="d-inline ms-1">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
          </form>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
