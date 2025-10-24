<?php $title = 'Create Event'; ?>
<div class="mb-3">
  <a href="/events" class="btn btn-sm btn-outline-dark">Back</a>
</div>
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
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="4"></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Type</label>
    <select name="event_type" class="form-select">
      <option value="career_fair">Career Fair</option>
      <option value="hackathon">Hackathon</option>
      <option value="workshop" selected>Workshop</option>
      <option value="networking">Networking</option>
      <option value="seminar">Seminar</option>
    </select>
  </div>
  <div class="mb-3">
    <label class="form-label">Date & Time</label>
    <input type="datetime-local" name="event_date" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Location</label>
    <input name="location" class="form-control" placeholder="e.g., UIU Auditorium or Zoom">
  </div>
  <div class="mb-3">
    <label class="form-label">Venue details</label>
    <textarea name="venue_details" class="form-control" rows="2" placeholder="Building, room, or meeting link"></textarea>
  </div>
  <div class="mb-3">
    <label class="form-label">Max participants (optional)</label>
    <input type="number" min="1" name="max_participants" class="form-control">
  </div>
  <button class="btn btn-dark" type="submit">Create Event</button>
  </form>
