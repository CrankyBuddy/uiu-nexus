<?php
  $title = 'Application #' . (int)$application['application_id'];
  ob_start();
?>
<h2 class="mb-3">Application for <?= htmlspecialchars($application['job_title']) ?></h2>
<div class="row">
  <div class="col-md-7">
    <div class="card mb-3"><div class="card-body">
      <div><strong>Applicant:</strong> <?= htmlspecialchars(($application['first_name'] ?? '').' '.($application['last_name'] ?? '')) ?> (<?= htmlspecialchars((string)$application['student_email']) ?>)</div>
      <div><strong>Status:</strong> <?= htmlspecialchars($application['status']) ?></div>
      <div class="mt-2"><strong>Cover letter:</strong><br><?= nl2br(htmlspecialchars((string)$application['cover_letter'])) ?></div>
    </div></div>

    <?php if (!empty($answers)): ?>
    <div class="card mb-3"><div class="card-body">
      <h5 class="mb-2">Questionnaire</h5>
      <ul class="list-unstyled">
        <?php foreach ($answers as $a): ?>
          <li class="mb-2"><strong><?= htmlspecialchars((string)$a['question_text']) ?>:</strong><br><?= nl2br(htmlspecialchars((string)$a['answer_text'])) ?></li>
        <?php endforeach; ?>
      </ul>
    </div></div>
    <?php endif; ?>

    <?php if (!empty($references)): ?>
    <div class="card mb-3"><div class="card-body">
      <h5 class="mb-2">Attached References</h5>
      <ul class="list-unstyled">
        <?php foreach ($references as $r): ?>
          <li class="mb-2">
            <div><strong>Mentor:</strong> <a href="/u/<?= (int)($r['alumni_user_id'] ?? 0) ?>" target="_blank"><?= htmlspecialchars(trim(($r['alumni_first_name'] ?? '').' '.($r['alumni_last_name'] ?? ''))) ?></a></div>
            <?php $cv = $mentorDocs[$r['reference_id']] ?? null; if ($cv && !empty($cv['file_url'])): ?>
              <div><strong>Mentor CV:</strong> <a href="<?= htmlspecialchars((string)$cv['file_url']) ?>" target="_blank">Download</a></div>
            <?php endif; ?>
            <?php if (!empty($r['reference_text'])): ?>
              <div class="text-muted small mt-1">"<?= nl2br(htmlspecialchars((string)$r['reference_text'])) ?>"</div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div></div>
    <?php endif; ?>

    <div class="card mb-3"><div class="card-body">
      <h5 class="mb-2">Interviews</h5>
      <?php if (empty($interviews)): ?>
        <div class="text-muted">No interviews scheduled.</div>
      <?php else: ?>
        <ul class="list-unstyled">
          <?php foreach ($interviews as $iv): ?>
            <li>On <?= htmlspecialchars((string)$iv['scheduled_date']) ?> for <?= (int)$iv['duration_minutes'] ?> mins <?= $iv['meeting_link']? '— <a href="'.htmlspecialchars($iv['meeting_link']).'" target="_blank">link</a>':'' ?> <?= $iv['interviewer_name']? '— '.htmlspecialchars($iv['interviewer_name']):'' ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <a class="btn btn-sm btn-outline-dark" href="/applications/<?= (int)$application['application_id'] ?>/schedule">Schedule Interview</a>
    </div></div>
  </div>
  <div class="col-md-5">
    <?php
      $viewerId = (int)(\Nexus\Helpers\Auth::id() ?? 0);
      $viewerRole = (string)(\Nexus\Helpers\Auth::user()['role'] ?? '');
      $subjectId = (int)($applicantUser['user_id'] ?? 0);
      $ctx = $visibilityContext ?? [];
      $canSeeCgpa = \Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $viewerId, $viewerRole, $subjectId, 'cgpa', $ctx);
      $canSeePhone = \Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $viewerId, $viewerRole, $subjectId, 'phone', $ctx);
  $canSeeResume = \Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $viewerId, $viewerRole, $subjectId, 'cv', $ctx);
      $canSeeLinkedin = \Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $viewerId, $viewerRole, $subjectId, 'linkedin', $ctx);
    ?>
    <div class="card mb-3"><div class="card-body">
      <h5 class="mb-2">Applicant Profile</h5>
      <?php if (!empty($applicantProfile)): ?>
        <div><strong>Name:</strong> <?= htmlspecialchars(trim(($applicantProfile['first_name'] ?? '') . ' ' . ($applicantProfile['last_name'] ?? ''))) ?></div>
        <div><strong>CGPA:</strong> <?= $canSeeCgpa ? htmlspecialchars((string)($applicantStudent['cgpa'] ?? '')) : '<span class="text-muted">Hidden</span>' ?></div>
        <div><strong>Phone:</strong> <?= $canSeePhone ? htmlspecialchars((string)($applicantProfile['phone'] ?? '')) : '<span class="text-muted">Hidden</span>' ?></div>
        <?php $cvDoc = \Nexus\Models\UserDocument::getByUserAndType($GLOBALS['config'], (int)$subjectId, 'cv'); $cvUrl = $cvDoc['file_url'] ?? ($applicantProfile['resume_url'] ?? null); ?>
        <?php if (!empty($cvUrl)): ?>
          <div><strong>CV:</strong> <?= $canSeeResume ? ('<a href="'.htmlspecialchars((string)$cvUrl).'" target="_blank">Download</a>') : '<span class="text-muted">Hidden</span>' ?></div>
        <?php endif; ?>
        <?php if (!empty($applicantProfile['linkedin_url'])): ?>
          <div><strong>LinkedIn:</strong> <?= $canSeeLinkedin ? ('<a href="'.htmlspecialchars((string)$applicantProfile['linkedin_url']).'" target="_blank">Profile</a>') : '<span class="text-muted">Hidden</span>' ?></div>
        <?php endif; ?>
        <?php if (!empty($applicantSkills)): ?>
          <div class="mt-2"><strong>Skills:</strong> 
            <?php foreach ($applicantSkills as $sn): ?>
              <span class="badge rounded-pill text-bg-secondary me-1"><?= htmlspecialchars((string)$sn) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="text-muted">No profile data.</div>
      <?php endif; ?>
    </div></div>
    <div class="card mb-3"><div class="card-body">
      <h5 class="mb-2">Update Status</h5>
      <form method="post" action="/applications/<?= (int)$application['application_id'] ?>/status" class="d-flex gap-2">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
        <select name="status" class="form-select form-select-sm" style="width:auto;">
          <?php foreach (['applied','under_review','shortlisted','interview','accepted','rejected'] as $st): ?>
            <option value="<?= $st ?>" <?= $application['status']===$st?'selected':'' ?>><?= $st ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-dark" type="submit">Save</button>
      </form>
    </div></div>

    <div class="card"><div class="card-body">
      <h5 class="mb-2">Internal Notes</h5>
      <?php if (empty($notes)): ?>
        <div class="text-muted">No notes yet.</div>
      <?php else: ?>
        <ul class="list-unstyled">
          <?php foreach ($notes as $n): ?>
            <li><small class="text-muted"><?= htmlspecialchars((string)$n['created_at']) ?> by <?= htmlspecialchars((string)$n['author_email']) ?></small><br><?= nl2br(htmlspecialchars((string)$n['note_text'])) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <form method="post" action="/applications/<?= (int)$application['application_id'] ?>/notes" class="mt-2">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
        <textarea class="form-control" name="note_text" rows="3" placeholder="Add a note..."></textarea>
        <button class="btn btn-sm btn-outline-dark mt-2" type="submit">Add Note</button>
      </form>
    </div></div>
  </div>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
