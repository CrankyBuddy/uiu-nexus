<?php $title = 'New Announcement'; ?>
<div class="mb-3"><a href="/announcements" class="btn btn-sm btn-outline-dark">Back</a></div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post">
  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
  <div class="mb-3">
    <label class="form-label">Title</label>
    <input name="title" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Content</label>
    <textarea name="content" class="form-control" rows="5" required></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Target Roles</label>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="target_roles[]" value="student" id="r-student">
      <label class="form-check-label" for="r-student">Students</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="target_roles[]" value="alumni" id="r-alumni">
      <label class="form-check-label" for="r-alumni">Alumni</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="target_roles[]" value="recruiter" id="r-recruiter">
      <label class="form-check-label" for="r-recruiter">Recruiters</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="target_roles[]" value="admin" id="r-admin">
      <label class="form-check-label" for="r-admin">Admins</label>
    </div>
  </div>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_published" id="p-now" checked>
    <label class="form-check-label" for="p-now">Publish now</label>
  </div>
  <button class="btn btn-dark" type="submit">Create Announcement</button>
</form>
