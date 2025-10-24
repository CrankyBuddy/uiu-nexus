<?php
  $title = $title ?? 'Restriction History';
  ob_start();
?>
<h2 class="mb-3">Restriction History</h2>
<?php if (!empty($user)): ?>
  <div class="mb-3">
    <strong>User:</strong> <?= htmlspecialchars($user['email'] ?? '') ?>
    <span class="badge bg-light text-dark ms-2">Role: <?= htmlspecialchars($user['role'] ?? '') ?></span>
    <a class="btn btn-sm btn-outline-dark ms-2" href="/people">Back to People</a>
  </div>
<?php endif; ?>
<div class="mb-3">
  <span class="badge bg-dark">Bans: <?= (int)($summary['ban'] ?? 0) ?></span>
  <span class="badge bg-secondary ms-1">Suspensions: <?= (int)($summary['suspend'] ?? 0) ?></span>
  <span class="badge bg-info text-dark ms-1">Lifts: <?= (int)($summary['lift'] ?? 0) ?></span>
</div>
<table class="table table-sm align-middle">
  <thead>
    <tr>
      <th>When</th>
      <th>Type</th>
      <th>Scope</th>
      <th>Until</th>
      <th>Reason</th>
      <th>By</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach (($events ?? []) as $ev): ?>
      <tr>
        <td><?= htmlspecialchars((string)($ev['created_at'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($ev['event_type'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($ev['feature_key'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($ev['restricted_until'] ?? '')) ?></td>
        <td><?= nl2br(htmlspecialchars((string)($ev['reason'] ?? ''))) ?></td>
        <td><?= (int)($ev['acted_by'] ?? 0) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
