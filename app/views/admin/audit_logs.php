<?php
  $title = $title ?? 'Audit Logs';
  ob_start();
?>
<h2 class="mb-3">Audit Logs</h2>
<table class="table table-sm">
  <thead><tr><th>At</th><th>User</th><th>Action</th><th>Entity</th><th>Old</th><th>New</th><th>IP</th></tr></thead>
  <tbody>
    <?php foreach (($logs ?? []) as $l): ?>
      <tr>
        <td><?= htmlspecialchars((string)$l['created_at']) ?></td>
        <td><?= htmlspecialchars((string)($l['email'] ?? 'system')) ?></td>
        <td><?= htmlspecialchars((string)$l['action']) ?></td>
        <td><?= htmlspecialchars((string)$l['entity_type']) ?>#<?= htmlspecialchars((string)($l['entity_id'] ?? '')) ?></td>
        <td><small><?= htmlspecialchars((string)$l['old_values']) ?></small></td>
        <td><small><?= htmlspecialchars((string)$l['new_values']) ?></small></td>
        <td><small><?= htmlspecialchars((string)($l['ip_address'] ?? '')) ?></small></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
