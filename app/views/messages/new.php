<?php
  $title = 'Start a Conversation';
?>
<div class="page-header">
  <h1 class="h4 title">Start a Conversation</h1>
  <div class="page-actions">
    <a class="btn btn-sm btn-outline-dark" href="/messages">Back to Inbox</a>
  </div>
</div>
<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="get" action="/messages/new">
      <div class="col-md-6">
        <input class="form-control" type="text" name="q" value="<?= htmlspecialchars((string)($q ?? '')) ?>" placeholder="Search by name or email">
      </div>
        <div class="col-md-3 d-flex align-items-center gap-2 flex-wrap">
          <?php $roleFilter = (string)($role ?? ''); $counts = $counts ?? ['all'=>null,'alumni'=>null,'student'=>null]; ?>
          <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($roleFilter) ?>">
          <button class="btn btn-sm <?= $roleFilter===''?'btn-dark':'btn-outline-dark' ?>" type="button" onclick="document.getElementById('roleInput').value=''; this.form.submit();">All<?= isset($counts['all']) && $counts['all']!==null ? ' ('.(int)$counts['all'].')' : '' ?></button>
          <button class="btn btn-sm <?= $roleFilter==='alumni'?'btn-dark':'btn-outline-dark' ?>" type="button" onclick="document.getElementById('roleInput').value='alumni'; this.form.submit();">Alumni<?= isset($counts['alumni']) && $counts['alumni']!==null ? ' ('.(int)$counts['alumni'].')' : '' ?></button>
          <button class="btn btn-sm <?= $roleFilter==='student'?'btn-dark':'btn-outline-dark' ?>" type="button" onclick="document.getElementById('roleInput').value='student'; this.form.submit();">Students<?= isset($counts['student']) && $counts['student']!==null ? ' ('.(int)$counts['student'].')' : '' ?></button>
        </div>
      <div class="col-md-3">
        <select class="form-select" name="per_page">
          <?php foreach ([10,20,30,50] as $pp): ?>
            <option value="<?= $pp ?>" <?= ((int)($per_page ?? 20) === $pp) ? 'selected' : '' ?>>Show <?= $pp ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-dark w-100" type="submit">Apply</button>
      </div>
    </form>
  </div>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-warning"><?= htmlspecialchars((string)$error) ?></div>
<?php endif; ?>
<div class="list-group">
  <?php if (empty($eligible)): ?>
    <div class="list-group-item">
      <div class="fw-semibold">No contacts available to start a new chat.</div>
      <div class="text-muted small mt-1">You don’t have any eligible users yet. Try searching a name/email, adjust filters, or build connections through mentorships, jobs, or forum.</div>
      <a class="btn btn-sm btn-outline-dark mt-2" href="/messages">Back to Inbox</a>
    </div>
  <?php else: ?>
    <?php foreach ($eligible as $u): ?>
      <div class="list-group-item d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <?php $pic = $u['profile_picture_url'] ?? null; ?>
            <div class="me-3">
              <?php if (!empty($pic)): ?>
                <img class="nx-avatar" src="<?= htmlspecialchars($pic) ?>" alt="avatar">
              <?php else: ?>
                <div class="nx-avatar nx-avatar-initials">👤</div>
              <?php endif; ?>
            </div>
            <div>
              <div class="fw-semibold"><?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?: htmlspecialchars($u['email']) ?></div>
              <div class="text-muted small">Role: <?= htmlspecialchars($u['role']) ?> • <?= htmlspecialchars($u['email']) ?></div>
            </div>
          </div>
        <form method="post" action="/messages/new" class="m-0">
          <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
          <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
          <button class="btn btn-sm btn-dark" type="submit">Start Message</button>
        </form>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
