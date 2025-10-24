<?php
  $title = 'Report Content | UIU NEXUS';
  ob_start();
  use Nexus\Helpers\Csrf;
?>
<div class="row">
  <div class="col-lg-8">
    <h2 class="mb-3">Report Content</h2>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars((string)$error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?= htmlspecialchars((string)$success) ?></div>
    <?php endif; ?>
  <form method="post" action="/report" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
      <input type="hidden" name="target_type" value="<?= htmlspecialchars((string)($target_type ?? '')) ?>">
      <input type="hidden" name="target_id" value="<?= (int)($target_id ?? 0) ?>">
      <?php if (($target_type ?? '') === 'message' && !empty($target_preview)): $m = $target_preview; ?>
        <div class="mb-3">
          <label class="form-label">You are reporting this message</label>
          <div class="border rounded p-2" style="background:#fafafa;">
            <div class="small text-muted mb-1">From <?= htmlspecialchars(((($m['sender_first_name'] ?? '') . ' ' . ($m['sender_last_name'] ?? '')) ?: ($m['sender_email'] ?? ''))) ?> • <?= htmlspecialchars((string)($m['created_at'] ?? '')) ?></div>
            <div style="white-space:pre-wrap;"><?= htmlspecialchars((string)($m['message_text'] ?? '')) ?></div>
            <?php $atts = $m['attachments'] ?? []; if (!empty($atts)): ?>
              <div class="mt-2" style="display:flex; gap:.5rem; flex-wrap:wrap;">
                <?php foreach ($atts as $a): $url = (string)($a['file_url'] ?? ''); $name = (string)($a['file_name'] ?? 'file'); $mime = (string)($a['mime_type'] ?? ''); ?>
                  <?php if (strpos($mime,'image/') === 0): ?>
                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener"><img src="<?= htmlspecialchars($url) ?>" alt="<?= htmlspecialchars($name) ?>" style="max-width:120px;border-radius:6px;"></a>
                  <?php else: ?>
                    <a class="btn btn-sm btn-outline-dark" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">Download <?= htmlspecialchars($name) ?></a>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="includeEvidence" name="include_evidence" value="1" checked>
          <label class="form-check-label" for="includeEvidence">Attach a snapshot of this message and its files to the report</label>
        </div>
      <?php endif; ?>
      <div class="mb-3">
        <label class="form-label">Reason</label>
        <textarea class="form-control" name="reason" rows="5" placeholder="Describe the issue..." required></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Attachments (optional)</label>
        <input class="form-control" type="file" name="attachments[]" multiple>
        <div class="form-text">Up to ~25 MB per file. Allowed: images (JPG, PNG, GIF, WebP), PDF, DOC, DOCX, TXT.</div>
      </div>
      <button class="btn btn-dark" type="submit">Submit Report</button>
      <a class="btn btn-outline-dark" href="/">Cancel</a>
    </form>
  </div>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
