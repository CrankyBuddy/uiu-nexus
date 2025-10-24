<?php $title = 'Inbox'; ?>
<div class="page-header">
  <h1 class="h4 title">Messages</h1>
  <div class="page-actions">
    <a href="/messages/new" class="btn btn-sm btn-dark">New</a>
  </div>
  </div>
<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="get" action="/messages">
      <div class="col-md-8">
        <input class="form-control" type="text" name="q" placeholder="Search conversations" value="<?= htmlspecialchars((string)($q ?? '')) ?>">
      </div>
      <div class="col-md-3">
        <select class="form-select" name="per_page">
          <?php foreach ([10,20,30,50] as $pp): ?>
            <option value="<?= $pp ?>" <?= ((int)($per_page ?? 20) === $pp) ? 'selected' : '' ?>>Show <?= $pp ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <button class="btn btn-dark w-100" type="submit">Go</button>
      </div>
    </form>
  </div>
</div>
<?php if (empty($conversations)): ?>
  <div class="alert alert-info">No conversations yet.</div>
<?php else: ?>
  <div class="list-group">
    <?php foreach ($conversations as $c): ?>
  <?php $hasUnread = ((int)($c['unread_count'] ?? 0)) > 0; ?>
  <a class="list-group-item list-group-item-action<?= $hasUnread ? ' is-unread' : '' ?>" href="/messages/<?= (int)$c['conversation_id'] ?>">
        <div class="d-flex align-items-start">
          <?php $avatar = $c['avatar_url'] ?? null; ?>
          <div class="me-3">
            <?php if (!empty($avatar)): ?>
              <img class="nx-avatar" src="<?= htmlspecialchars($avatar) ?>" alt="avatar">
            <?php else: ?>
              <div class="nx-avatar nx-avatar-initials" aria-hidden="true">👤</div>
            <?php endif; ?>
          </div>
          <div class="flex-grow-1">
            <div class="item-title d-flex justify-content-between">
              <div>
                <strong><?= htmlspecialchars($c['display_title'] ?? ($c['title'] ?? 'Direct Message')) ?></strong>
                <?php if ($hasUnread): ?>
                  <span class="badge bg-danger ms-2"><?= (int)$c['unread_count'] ?></span>
                <?php endif; ?>
              </div>
              <span class="text-muted small"><?= htmlspecialchars((string)$c['updated_at']) ?></span>
            </div>
            <?php if (!empty($c['last_message'])): ?>
              <div class="text-muted small mt-1"><?= htmlspecialchars((string)$c['last_message']) ?></div>
            <?php endif; ?>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
