<?php $title = 'Notifications'; ?>
<div class="page-header">
  <h1 class="h4 title">Notifications</h1>
  <div class="page-actions">
    <?php
      $hasUnread = false;
      foreach (($notifications ?? []) as $__n) { if (!((int)$__n['is_read'])) { $hasUnread = true; break; } }
    ?>
    <?php if ($hasUnread): ?>
      <form method="post" action="/notifications/mark-all-read" class="d-inline">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
        <button class="btn btn-sm btn-dark" type="submit">Mark all read</button>
      </form>
    <?php endif; ?>
    <a href="/events" class="btn btn-sm btn-outline-dark">Events</a>
    <a href="/announcements" class="btn btn-sm btn-outline-dark">Announcements</a>
  </div>
  </div>
<?php if (empty($notifications)): ?>
  <div class="alert alert-info">You're all caught up.</div>
<?php else: ?>
  <div class="list-group">
    <?php foreach ($notifications as $n): ?>
      <?php $isUnread = !((int)$n['is_read']); ?>
      <div class="list-group-item notification-item<?= $isUnread ? ' is-unread' : '' ?>">
        <div class="notif-body">
          <div class="notif-title<?= $isUnread ? '' : ' text-muted' ?>"><?= htmlspecialchars($n['title']) ?></div>
          <div class="notif-date small">
            <?= htmlspecialchars(date('M d, Y H:i', strtotime((string)$n['created_at']))) ?>
          </div>
          <div class="notif-message"><?= nl2br(htmlspecialchars((string)$n['message'])) ?></div>
          <?php if (!empty($n['action_url'])): ?>
            <a class="btn btn-sm btn-outline-dark mt-2" href="<?= htmlspecialchars((string)$n['action_url']) ?>">Open</a>
          <?php endif; ?>
        </div>
        <div class="notif-actions">
          <?php if ($isUnread): ?>
            <form method="post" action="/notifications/<?= (int)$n['notification_id'] ?>/read" class="m-0">
              <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
              <button class="btn btn-sm btn-dark" type="submit">Mark read</button>
            </form>
          <?php else: ?>
            <span class="badge text-bg-light">Read</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
