<?php
  $title = $title ?? 'Search';
  ob_start();
  $q = (string)($q ?? '');
?>
<h2 class="mb-3">Search</h2>
<form class="row g-2 mb-4" method="get" action="/search">
  <div class="col-md-8">
    <input class="form-control" type="text" name="q" placeholder="Search forum, jobs, events, announcements..." value="<?= htmlspecialchars($q) ?>">
  </div>
  <div class="col-md-4">
    <button class="btn btn-dark" type="submit">Search</button>
  </div>
</form>

<?php if ($q !== ''): ?>
  <div class="mb-4">
    <h5>Forum</h5>
    <ul class="list-group list-group-flush">
      <?php foreach (($results['forum'] ?? []) as $p): ?>
        <li class="list-group-item">
          <div class="fw-semibold"><a href="/forum/post/<?= (int)$p['post_id'] ?>"><?= htmlspecialchars((string)($p['title'] ?? 'Untitled')) ?></a></div>
          <div class="text-muted"><small><?= htmlspecialchars(mb_strimwidth(strip_tags((string)$p['content']), 0, 120, '…')) ?></small></div>
        </li>
      <?php endforeach; if (empty($results['forum'])): ?>
        <li class="list-group-item text-muted">No forum results</li>
      <?php endif; ?>
    </ul>
  </div>

  <div class="mb-4">
    <h5>Jobs</h5>
    <ul class="list-group list-group-flush">
      <?php foreach (($results['jobs'] ?? []) as $j): ?>
        <li class="list-group-item d-flex justify-content-between align-items-start">
          <div class="me-auto">
            <div class="fw-semibold"><a href="/jobs/<?= (int)$j['job_id'] ?>"><?= htmlspecialchars((string)$j['job_title']) ?></a></div>
            <div class="text-muted"><small><?= htmlspecialchars(mb_strimwidth(strip_tags((string)$j['job_description']), 0, 120, '…')) ?></small></div>
          </div>
          <?php if (!empty($j['is_premium'])): ?><span class="badge bg-warning text-dark">Premium</span><?php endif; ?>
        </li>
      <?php endforeach; if (empty($results['jobs'])): ?>
        <li class="list-group-item text-muted">No job results</li>
      <?php endif; ?>
    </ul>
  </div>

  <div class="mb-4">
    <h5>Events</h5>
    <ul class="list-group list-group-flush">
      <?php foreach (($results['events'] ?? []) as $e): ?>
        <li class="list-group-item">
          <div class="fw-semibold"><a href="/events/<?= (int)$e['event_id'] ?>"><?= htmlspecialchars((string)$e['title']) ?></a></div>
          <div class="text-muted"><small><?= htmlspecialchars(mb_strimwidth(strip_tags((string)$e['description']), 0, 120, '…')) ?></small></div>
        </li>
      <?php endforeach; if (empty($results['events'])): ?>
        <li class="list-group-item text-muted">No event results</li>
      <?php endif; ?>
    </ul>
  </div>

  <div class="mb-4">
    <h5>Announcements</h5>
    <ul class="list-group list-group-flush">
      <?php foreach (($results['announcements'] ?? []) as $a): ?>
        <li class="list-group-item">
          <div class="fw-semibold"><a href="/announcements"><?= htmlspecialchars((string)$a['title']) ?></a></div>
          <div class="text-muted"><small><?= htmlspecialchars(mb_strimwidth(strip_tags((string)$a['content']), 0, 120, '…')) ?></small></div>
        </li>
      <?php endforeach; if (empty($results['announcements'])): ?>
        <li class="list-group-item text-muted">No announcement results</li>
      <?php endif; ?>
    </ul>
  </div>

  <?php if (!empty($adminUsers)): ?>
    <div class="mb-4">
      <h5>Users (admin)</h5>
      <ul class="list-group list-group-flush">
        <?php foreach ($adminUsers as $u): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span>#<?= (int)$u['user_id'] ?> — <?= htmlspecialchars((string)$u['email']) ?> <small class="text-muted">(<?= htmlspecialchars((string)$u['role']) ?>)</small></span>
            <span><?= ((int)$u['is_active']) ? '<span class="badge bg-success">active</span>' : '<span class="badge bg-secondary">inactive</span>' ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
