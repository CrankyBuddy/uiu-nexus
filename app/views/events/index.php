<?php $title = 'Events'; ?>
<div class="page-header">
  <h1 class="h4 title">Upcoming Events</h1>
  <div class="page-actions">
  <?php $user = \Nexus\Helpers\Auth::user(); if (!empty($user) && (($user['role'] ?? '') === 'admin')): ?>
    <a href="/events/create" class="btn btn-sm btn-dark">Create Event</a>
  <?php endif; ?>
  <a href="/announcements" class="btn btn-sm btn-outline-dark">Announcements</a>
  <a href="/notifications" class="btn btn-sm btn-outline-dark">Notifications</a>
  </div>
  </div>
<?php if (empty($events)): ?>
  <div class="alert alert-info">No upcoming events.</div>
<?php else: ?>
  <div class="list-group">
    <?php foreach ($events as $e): ?>
      <a class="list-group-item list-group-item-action event-item" href="/events/<?= (int)$e['event_id'] ?>">
        <div class="item-title">
          <span class="item-title-text fw-semibold"><?= htmlspecialchars($e['title']) ?></span>
          <span class="item-date text-muted small"><?= htmlspecialchars(date('M d, Y H:i', strtotime((string)$e['event_date']))) ?></span>
        </div>
        <div class="item-meta text-muted small">Type: <?= htmlspecialchars($e['event_type']) ?><?= $e['location'] ? ' • ' . htmlspecialchars((string)$e['location']) : '' ?></div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
