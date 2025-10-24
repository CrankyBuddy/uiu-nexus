<?php
  $title = $title ?? 'Report Detail';
  ob_start();
  $csrf = \Nexus\Helpers\Csrf::token();
  $r = $report ?? [];
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="mb-0">Report #<?= (int)($r['report_id'] ?? 0) ?></h2>
  <a class="btn btn-sm btn-outline-dark" href="/admin/reports">Back</a>
</div>
<div class="card mb-3">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-4">
        <div><strong>Target:</strong> <?= htmlspecialchars((string)($r['target_type'] ?? '')) ?> #<?= (int)($r['target_id'] ?? 0) ?></div>
        <div><strong>Reported by:</strong> <?= htmlspecialchars((string)($r['reporter_email'] ?? '')) ?></div>
        <?php if (!empty($r['target_author_email'])): ?>
          <div><strong>Target Author Email:</strong> <?= htmlspecialchars((string)$r['target_author_email']) ?></div>
        <?php endif; ?>
        <?php if (!empty($r['target_author_id'])): ?>
          <div><strong>Target Author ID:</strong> <?= (int)$r['target_author_id'] ?></div>
        <?php endif; ?>
        <div><strong>Status:</strong> <span class="badge bg-secondary"><?= htmlspecialchars((string)($r['status'] ?? '')) ?></span></div>
        <div><strong>Created:</strong> <?= htmlspecialchars((string)($r['created_at'] ?? '')) ?></div>
        <?php if (!empty($r['resolved_at'])): ?>
          <div><strong>Resolved:</strong> <?= htmlspecialchars((string)$r['resolved_at']) ?></div>
        <?php endif; ?>
        <div class="mt-2"><strong>Reason:</strong><br><?= nl2br(htmlspecialchars((string)($r['reason'] ?? ''))) ?></div>
      </div>
      <div class="col-md-8">
        <form class="d-flex gap-2" method="post" action="/admin/reports/update">
          <input type="hidden" name="_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="report_id" value="<?= (int)($r['report_id'] ?? 0) ?>">
          <label class="form-label m-0 align-self-center">Status</label>
          <select name="status" class="form-select form-select-sm" style="width:auto;">
            <?php foreach (["pending","investigating","resolved","dismissed"] as $st): ?>
              <option value="<?= $st ?>" <?= (($r['status'] ?? '')===$st)?'selected':'' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
          <label class="form-label m-0 align-self-center">Assigned to</label>
          <select name="assigned_to" class="form-select form-select-sm" style="width:auto;">
            <option value="">Unassigned</option>
            <?php foreach (($admins ?? []) as $a): ?>
              <option value="<?= (int)$a['user_id'] ?>" <?= (isset($r['assigned_to']) && (int)$r['assigned_to']===(int)$a['user_id'])?'selected':'' ?>><?= htmlspecialchars($a['email']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-dark" type="submit">Update</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($evidence)): ?>
  <div class="card mb-3">
    <div class="card-header">Evidence</div>
    <div class="card-body">
      <?php if (($evidence['type'] ?? '') === 'message'): ?>
        <?php $m = $evidence['message'] ?? []; ?>
        <div class="mb-2"><strong>Message:</strong> #<?= (int)($m['message_id'] ?? 0) ?> from user #<?= (int)($m['sender_id'] ?? 0) ?> at <?= htmlspecialchars((string)($m['created_at'] ?? '')) ?></div>
        <div class="p-2 border rounded bg-light" style="white-space:pre-wrap;"><?= htmlspecialchars((string)($m['text'] ?? '')) ?></div>
        <?php $atts = $evidence['attachments'] ?? []; if (!empty($atts)): ?>
          <div class="mt-2"><strong>Attachments:</strong>
            <div class="d-flex flex-wrap gap-2 mt-2">
              <?php foreach ($atts as $a): $url = $a['snapshot_url'] ?? $a['original_url'] ?? null; if(!$url) continue; ?>
                <a class="btn btn-sm btn-outline-dark" target="_blank" href="<?= htmlspecialchars((string)$url) ?>"><?= htmlspecialchars((string)($a['file_name'] ?? 'file')) ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="text-muted">No structured evidence available.</div>
      <?php endif; ?>
      <?php if (!empty($evidence['attachments_admin'])): ?>
        <div class="mt-3"><strong>Admin Attachments:</strong>
          <div class="d-flex flex-wrap gap-2 mt-2">
            <?php foreach ($evidence['attachments_admin'] as $aa): ?>
              <a class="btn btn-sm btn-outline-dark" target="_blank" href="<?= htmlspecialchars((string)($aa['snapshot_url'] ?? '#')) ?>"><?= htmlspecialchars((string)($aa['file_name'] ?? 'file')) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      <?php if (!empty($evidence['attachments_reporter'])): ?>
        <div class="mt-3"><strong>Reporter Attachments:</strong>
          <div class="d-flex flex-wrap gap-2 mt-2">
            <?php foreach ($evidence['attachments_reporter'] as $ra): ?>
              <a class="btn btn-sm btn-outline-dark" target="_blank" href="<?= htmlspecialchars((string)($ra['snapshot_url'] ?? '#')) ?>"><?= htmlspecialchars((string)($ra['file_name'] ?? 'file')) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-header">Files</div>
  <div class="card-body">
    <?php if (empty($files)): ?>
      <div class="text-muted">No files uploaded.</div>
    <?php else: ?>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($files as $f): ?>
          <a class="btn btn-sm btn-outline-dark" target="_blank" href="<?= htmlspecialchars((string)$f) ?>"><?= htmlspecialchars(basename($f)) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
