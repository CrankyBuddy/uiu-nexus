<?php $title = htmlspecialchars($event['title'] ?? 'Event'); ?>
<div class="mb-3">
  <a href="/events" class="btn btn-sm btn-outline-dark">Back to Events</a>
  <a href="/announcements" class="btn btn-sm btn-outline-dark">Announcements</a>
  <a href="/notifications" class="btn btn-sm btn-outline-dark">Notifications</a>
  </div>
<div class="card">
  <div class="card-body">
    <h1 class="h4 mb-1"><?= htmlspecialchars($event['title']) ?></h1>
    <div class="text-muted mb-2"><?= htmlspecialchars(date('M d, Y H:i', strtotime((string)$event['event_date']))) ?> • <?= htmlspecialchars($event['event_type']) ?></div>
    <?php if (!empty($event['location'])): ?>
      <div><strong>Location:</strong> <?= htmlspecialchars((string)$event['location']) ?></div>
    <?php endif; ?>
    <?php if (!empty($event['venue_details'])): ?>
      <div class="text-muted small"><?= nl2br(htmlspecialchars((string)$event['venue_details'])) ?></div>
    <?php endif; ?>
    <p class="mt-3"><?= nl2br(htmlspecialchars((string)$event['description'])) ?></p>
    <div class="mt-3">
      <?php if (!empty($event['max_participants'])): ?>
        <span class="badge text-bg-light">Capacity: <?= (int)$regCount ?> / <?= (int)$event['max_participants'] ?></span>
      <?php endif; ?>
      <?php if (!$registered): ?>
        <form method="post" action="/events/<?= (int)$event['event_id'] ?>/register" class="d-inline">
          <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
          <button class="btn btn-sm btn-dark" type="submit">Register</button>
        </form>
      <?php else: ?>
        <span class="badge bg-success">You're registered</span>
      <?php endif; ?>
    </div>
  </div>
</div>
<!-- Report button removed as requested -->
