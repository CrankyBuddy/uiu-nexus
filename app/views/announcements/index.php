<?php $title = 'Announcements'; ?>
<div class="page-header">
  <h1 class="h4 title">Announcements</h1>
  <div class="page-actions">
    <?php $user = \Nexus\Helpers\Auth::user(); $isAdmin = (($user['role'] ?? '') === 'admin'); if ($isAdmin): ?>
      <a href="/announcements/create" class="btn btn-sm btn-dark">New Announcement</a>
      <?php $adminOnly = (bool)($adminOnly ?? false); ?>
      <?php if ($adminOnly): ?>
        <a href="/announcements" class="btn btn-sm btn-outline-dark">Show All</a>
      <?php else: ?>
        <a href="/announcements?admin_only=1" class="btn btn-sm btn-outline-dark">Admin-only</a>
      <?php endif; ?>
    <?php endif; ?>
    <a href="/events" class="btn btn-sm btn-outline-dark">Events</a>
    <a href="/notifications" class="btn btn-sm btn-outline-dark">Notifications</a>
  </div>
</div>
<?php if (empty($announcements)): ?>
  <div class="alert alert-info">No announcements.</div>
<?php else: ?>
  <div class="list-group">
    <?php foreach ($announcements as $a): ?>
      <div class="list-group-item announcement-item">
        <div class="item-title">
          <strong class="title-text"><?= htmlspecialchars($a['title']) ?></strong>
          <span class="ann-date text-muted small"><?= htmlspecialchars(date('M d, Y H:i', strtotime((string)$a['created_at']))) ?></span>
          <?php if ($isAdmin): ?>
      <?php
        $targets = [];
        if (isset($a['target_roles']) && $a['target_roles'] !== null && $a['target_roles'] !== '') {
          try {
            $rawTargets = json_decode((string)$a['target_roles'], true, 512, JSON_THROW_ON_ERROR) ?: [];
          } catch (\Throwable $e) { $rawTargets = []; }
          $labels = [
            'student' => 'Students',
            'alumni' => 'Alumni',
            'recruiter' => 'Recruiters',
            'admin' => 'Admins',
          ];
          foreach ($rawTargets as $rt) {
            $key = is_string($rt) ? strtolower($rt) : '';
            $targets[] = $labels[$key] ?? ucfirst($key);
          }
        }
      ?>
            <span class="badge bg-secondary ms-2 ann-badge" title="Target roles">Targets: <?= htmlspecialchars(implode(', ', $targets)) ?></span>
          <?php endif; ?>
        </div>
        <div class="item-content"><?= nl2br(htmlspecialchars((string)$a['content'])) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
