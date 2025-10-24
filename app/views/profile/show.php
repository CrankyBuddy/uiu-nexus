<?php
  $title = 'My Profile | UIU NEXUS';
  ob_start();
?>
<div class="row">
  <div class="col-lg-8">
    <h2 class="mb-3">Profile</h2>
    <div class="card mb-3">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3">
          <?php $pic = $profile['profile_picture_url'] ?? null; ?>
          <?php if (!empty($pic)): ?>
            <img class="nx-avatar nx-avatar-lg" src="<?= htmlspecialchars($pic) ?>" alt="avatar">
          <?php else: ?>
            <div class="nx-avatar nx-avatar-lg nx-avatar-initials" aria-hidden="true">👤</div>
          <?php endif; ?>
          <div>
            <h4 class="mb-0"><?php echo htmlspecialchars(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')); ?></h4>
            <div class="text-muted"><?php echo htmlspecialchars($user['role'] ?? ''); ?></div>
          <?php $roleView = (string)($user['role'] ?? ''); ?>
          <?php if (in_array($roleView, ['student','alumni'], true)): ?>
            <?php
              $wallet = \Nexus\Models\UserWallet::getByUserId($GLOBALS['config'], (int)($user['user_id'] ?? 0));
              $badges = \Nexus\Models\UserBadge::listByUser($GLOBALS['config'], (int)($user['user_id'] ?? 0));
            ?>
            <div class="mt-3 d-flex flex-wrap gap-3">
              <div><strong>Coins:</strong> <?= htmlspecialchars((string)($wallet['balance'] ?? 0)) ?></div>
              <div><strong>Badges:</strong>
                <?php if (!empty($badges)): ?>
                  <?php foreach ($badges as $b): ?>
                    <span class="badge text-bg-warning me-1"><?php echo htmlspecialchars($b['badge_name'] ?? 'Badge'); ?></span>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span class="text-muted">None</span>
                <?php endif; ?>
              </div>
              <div><strong>Reputation:</strong> <?= htmlspecialchars((string)($wallet['reputation_score'] ?? 0)) ?></div>
            </div>
          <?php endif; ?>
          <?php
            $viewerIdHdr = (int)(\Nexus\Helpers\Auth::id() ?? 0);
            $viewerRoleHdr = (string)(\Nexus\Helpers\Auth::user()['role'] ?? '');
            $canAdminHdr = ($viewerRoleHdr === 'admin') || \Nexus\Helpers\Gate::has($GLOBALS['config'], $viewerIdHdr, 'manage.permissions');
            $subjectIdHdr = (int)($user['user_id'] ?? 0);
            $basePathHdr = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $basePathHdr = ($basePathHdr === '' || $basePathHdr === '/') ? '' : $basePathHdr;
          ?>
          <?php if ($viewerIdHdr === $subjectIdHdr || $canAdminHdr): ?>
            <?php $editHref = $basePathHdr . '/profile/edit' . (($canAdminHdr && $viewerIdHdr !== $subjectIdHdr) ? ('?user_id=' . (int)($user['user_id'] ?? 0)) : ''); ?>
            <a class="btn btn-sm btn-dark" href="<?= $editHref ?>">Edit Profile</a>
          <?php elseif (!$canAdminHdr): ?>
            <a class="btn btn-sm btn-outline-warning" href="<?= $basePathHdr ?>/report?target_type=user&target_id=<?= (int)($user['user_id'] ?? 0) ?>">Report User</a>
          <?php endif; ?>
        </div>
        <p class="mt-3 mb-0"><?php echo nl2br(htmlspecialchars($profile['bio'] ?? '')); ?></p>
        <div class="mt-2 text-muted small">
          <?php $subjectRole = (string)($user['role'] ?? ''); ?>
          <?php $basePath = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); $basePath = ($basePath === '' || $basePath === '/') ? '' : $basePath; ?>
          <?php $vId = (int)(\Nexus\Helpers\Auth::id() ?? 0); $vRole = (string)(\Nexus\Helpers\Auth::user()['role'] ?? ''); $sId = (int)($user['user_id'] ?? 0); ?>
          <?php $rawFlags = \Nexus\Models\ProfileVisibility::getFlagsForUser($GLOBALS['config'], $sId); $phoneFlag = (bool)($rawFlags['phone'] ?? false); $emailFlag = (bool)($rawFlags['email'] ?? false); ?>
          <?php if ($subjectRole !== 'admin'): ?>
            <?php if (!empty($profile['linkedin_url'])): ?>
              <?php if (\Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $vId, $vRole, $sId, 'linkedin')): ?>
                <div>LinkedIn: <a href="<?= htmlspecialchars($profile['linkedin_url']) ?>" target="_blank">Profile</a></div>
              <?php else: ?>
                <div>LinkedIn: <span class="text-muted">Hidden</span></div>
              <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($profile['portfolio_url'])): ?>
              <div>Portfolio: <a href="<?= htmlspecialchars($profile['portfolio_url']) ?>" target="_blank">Link</a></div>
            <?php endif; ?>
            <?php
              $cvDoc = \Nexus\Models\UserDocument::getByUserAndType($GLOBALS['config'], (int)($user['user_id'] ?? 0), 'cv');
              $cvUrl = $cvDoc['file_url'] ?? ($profile['resume_url'] ?? null);
            ?>
            <?php if (!empty($cvUrl)): ?>
              <?php if (\Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $vId, $vRole, $sId, 'cv')): ?>
                <div>CV: <a href="<?= htmlspecialchars($cvUrl) ?>" target="_blank">Download</a></div>
              <?php else: ?>
                <div>CV: <span class="text-muted">Hidden</span></div>
              <?php endif; ?>
            <?php endif; ?>
          <?php endif; ?>
          <?php $vId = (int)(\Nexus\Helpers\Auth::id() ?? 0); $vRole = (string)(\Nexus\Helpers\Auth::user()['role'] ?? ''); $sId = (int)($user['user_id'] ?? 0); ?>
          <?php if ($subjectRole !== 'admin'): ?>
            <?php $showPhone = \Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $vId, $vRole, $sId, 'phone'); ?>
            <?php $showAddress = \Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $vId, $vRole, $sId, 'address'); ?>
            <div class="d-flex align-items-center gap-2">
              <div>Phone: <?= $showPhone ? htmlspecialchars((string)($profile['phone'] ?? '')) : '<span class="text-muted">Hidden</span>' ?></div>
              <?php if (!empty($viewerIsAdmin) || !empty($isOwner)): ?>
                <form method="post" action="<?= $basePath ?>/profile/visibility" class="d-inline">
                  <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                  <input type="hidden" name="user_id" value="<?= (int)($user['user_id'] ?? 0) ?>">
                  <input type="hidden" name="field" value="phone">
                  <input type="hidden" name="visible" value="<?= $phoneFlag ? '0' : '1' ?>">
                    <?php $returnToProfile = '/profile' . (($canAdminHdr && $viewerIdHdr !== $subjectIdHdr) ? ('?user_id=' . (int)$subjectIdHdr) : ''); ?>
                    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToProfile) ?>">
                  <button class="btn btn-sm btn-outline-secondary" type="submit"><?= $phoneFlag ? 'Hide Phone Number' : 'Show Phone Number' ?></button>
                </form>
              <?php endif; ?>
            </div>
            <div>Address: <?= $showAddress ? htmlspecialchars((string)($profile['address'] ?? '')) : '<span class="text-muted">Hidden</span>' ?></div>
            <?php if (!empty($profile['region'])): ?>
              <div>Country: <?= htmlspecialchars((string)$profile['region']) ?></div>
            <?php endif; ?>
          <?php endif; ?>
          <div class="d-flex align-items-center gap-2 mt-1">
            <div>Email: 
              <?php
                $canSeeEmail = \Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $vId, $vRole, $sId, 'email');
                echo $canSeeEmail ? htmlspecialchars((string)($user['email'] ?? '')) : '<span class="text-muted">Hidden</span>';
              ?>
            </div>
            <?php if (!empty($viewerIsAdmin) || !empty($isOwner)): ?>
              <form method="post" action="<?= $basePath ?>/profile/visibility" class="d-inline">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)($user['user_id'] ?? 0) ?>">
                <input type="hidden" name="field" value="email">
                <input type="hidden" name="visible" value="<?= $emailFlag ? '0' : '1' ?>">
                <?php $returnToProfile = '/profile' . (($canAdminHdr && $viewerIdHdr !== $subjectIdHdr) ? ('?user_id=' . (int)$subjectIdHdr) : ''); ?>
                <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToProfile) ?>">
                <button class="btn btn-sm btn-outline-secondary" type="submit"><?= $emailFlag ? 'Hide Email' : 'Show Email' ?></button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        
      </div>
    </div>

    <?php if ($user['role'] === 'student' && $roleData): ?>
      <div class="card mb-3"><div class="card-body">
        <h5>Student</h5>
        <div>Program: <?= htmlspecialchars($roleData['program_level'] ?? '') ?></div>
  <?php $vId = (int)(\Nexus\Helpers\Auth::id() ?? 0); $vRole = (string)(\Nexus\Helpers\Auth::user()['role'] ?? ''); $sId = (int)($user['user_id'] ?? 0); ?>
  <?php $showCgpa = \Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $vId, $vRole, $sId, 'cgpa'); ?>
  <div>CGPA: <?= $showCgpa ? htmlspecialchars((string)($roleData['cgpa'] ?? '')) : '<span class="text-muted">Hidden</span>' ?></div>
        <div>Student ID: <?= htmlspecialchars($roleData['university_id'] ?? '') ?></div>
        <div>Admission Year: <?= htmlspecialchars((string)($roleData['admission_year'] ?? '')) ?></div>
        <div>Admission Trimester: <?= htmlspecialchars((string)($roleData['admission_trimester'] ?? '')) ?></div>
      </div></div>
    <?php endif; ?>

    <?php if ($user['role'] === 'alumni' && $roleData): ?>
      <div class="card mb-3"><div class="card-body">
        <h5>Alumni</h5>
        <div>Company: <?= htmlspecialchars($roleData['company'] ?? '') ?></div>
        <div>Job Title: <?= htmlspecialchars($roleData['job_title'] ?? '') ?></div>
        <div>Experience: <?= htmlspecialchars((string)($roleData['years_of_experience'] ?? '')) ?> years</div>
        <div>Graduation Year: <?= htmlspecialchars((string)($roleData['graduation_year'] ?? '')) ?></div>
        <?php $vId = (int)(\Nexus\Helpers\Auth::id() ?? 0); $vRole = (string)(\Nexus\Helpers\Auth::user()['role'] ?? ''); $sId = (int)($user['user_id'] ?? 0); $showCgpaAlumni = \Nexus\Helpers\Visibility::canViewField($GLOBALS['config'], $vId, $vRole, $sId, 'cgpa'); ?>
        <div>CGPA: <?= $showCgpaAlumni ? htmlspecialchars((string)($roleData['cgpa'] ?? '')) : '<span class="text-muted">Hidden</span>' ?></div>
        <?php if (!empty($roleData['university_id'])): ?>
          <div>Alumni ID: <?= htmlspecialchars((string)$roleData['university_id']) ?></div>
        <?php endif; ?>
        <?php if (!empty($roleData['student_id_number'])): ?>
          <div>Student ID: <?= htmlspecialchars((string)$roleData['student_id_number']) ?></div>
        <?php endif; ?>
        <?php if (!empty($roleData['program_level'])): ?>
          <div>Program: <?= htmlspecialchars((string)$roleData['program_level']) ?></div>
        <?php endif; ?>
        <div>Industry: <?= htmlspecialchars((string)($roleData['industry'] ?? '')) ?></div>
        <div>Mentorship: <?= !empty($roleData['mentorship_availability']) ? 'Available' : 'Not Available' ?></div>
      </div></div>
    <?php endif; ?>

    <?php if ($user['role'] === 'recruiter' && $roleData): ?>
      <div class="card mb-3"><div class="card-body">
        <h5>Recruiter</h5>
        <div>Company: <?= htmlspecialchars($roleData['company_name'] ?? '') ?></div>
        <div>Website: <a href="<?= htmlspecialchars($roleData['company_website'] ?? '#') ?>" target="_blank"><?= htmlspecialchars($roleData['company_website'] ?? '') ?></a></div>
        <div>Industry: <?= htmlspecialchars($roleData['industry'] ?? '') ?></div>
        <div>Location: <?= htmlspecialchars($roleData['company_location'] ?? '') ?></div>
      </div></div>
    <?php endif; ?>

  <?php /* Edit Profile button moved to header; no duplicate here */ ?>
  </div>
</div>
<?php if (($user['role'] ?? '') === 'student'): ?>
<div class="row mt-3">
  <div class="col-lg-8">
    <div class="card"><div class="card-body">
      <h5>Skills & Interests</h5>
      <div class="mb-2">
        <strong>Skills:</strong>
        <?php if (!empty($skills)): ?>
          <?php foreach ($skills as $sn): ?>
            <span class="badge rounded-pill text-bg-secondary me-1"><?= htmlspecialchars((string)$sn) ?></span>
          <?php endforeach; ?>
        <?php else: ?>
          <span class="text-muted">None</span>
        <?php endif; ?>
      </div>
      <?php
        $careerInterests = \Nexus\Models\CareerInterest::listNamesByUser($GLOBALS['config'], (int)($user['user_id'] ?? 0));
        $courseInterests = \Nexus\Models\UserCourseInterest::listByUser($GLOBALS['config'], (int)($user['user_id'] ?? 0));
      ?>
      <div class="mb-2">
        <strong>Career Interests:</strong>
        <?php if (!empty($careerInterests)): ?>
          <?php foreach ($careerInterests as $ci): ?>
            <span class="badge rounded-pill text-bg-secondary me-1"><?= htmlspecialchars((string)$ci) ?></span>
          <?php endforeach; ?>
        <?php else: ?>
          <span class="text-muted">None</span>
        <?php endif; ?>
      </div>
      <div class="mb-2">
        <strong>Courses Struggling:</strong>
        <?php if (!empty($courseInterests['struggling'])): ?>
          <?php foreach ($courseInterests['struggling'] as $sn): ?>
            <span class="badge rounded-pill text-bg-warning me-1"><?= htmlspecialchars((string)$sn) ?></span>
          <?php endforeach; ?>
        <?php else: ?>
          <span class="text-muted">None</span>
        <?php endif; ?>
      </div>
    </div></div>
  </div>
</div>
<?php elseif (($user['role'] ?? '') === 'alumni'): ?>
<div class="row mt-3">
  <div class="col-lg-8">
    <div class="card"><div class="card-body">
      <h5>Skills & Interests</h5>
      <div class="mb-2">
        <strong>Skills:</strong>
        <?php if (!empty($skills)): ?>
          <?php foreach ($skills as $sn): ?>
            <span class="badge rounded-pill text-bg-secondary me-1"><?= htmlspecialchars((string)$sn) ?></span>
          <?php endforeach; ?>
        <?php else: ?>
          <span class="text-muted">None</span>
        <?php endif; ?>
      </div>
      <?php $careerInterests = \Nexus\Models\CareerInterest::listNamesByUser($GLOBALS['config'], (int)($user['user_id'] ?? 0)); ?>
      <div class="mb-2">
        <strong>Career Interests:</strong>
        <?php if (!empty($careerInterests)): ?>
          <?php foreach ($careerInterests as $ci): ?>
            <span class="badge rounded-pill text-bg-secondary me-1"><?= htmlspecialchars((string)$ci) ?></span>
          <?php endforeach; ?>
        <?php else: ?>
          <span class="text-muted">None</span>
        <?php endif; ?>
      </div>
    </div></div>
  </div>
</div>
<?php endif; ?>
    </div>
  </div>
  <?php /* Certificates / Achievements are visible to non-recruiters and non-admins */ ?>
  <?php $subjectRoleCA = (string)($user['role'] ?? ''); ?>
  <?php if ($subjectRoleCA !== 'recruiter' && $subjectRoleCA !== 'admin'): ?>
  <div class="row mt-3">
    <div class="col-lg-8">
      <div class="card"><div class="card-body">
        <h5>Certificates / Achievements</h5>
        <?php $certs = \Nexus\Models\UserCertificate::listByUser($GLOBALS['config'], (int)($user['user_id'] ?? 0)); ?>
        <?php if (!empty($certs)): ?>
          <?php foreach ($certs as $c): ?>
            <div class="mb-2">
              <div class="fw-semibold"><?php echo htmlspecialchars((string)($c['title'] ?? '')); ?></div>
              <?php if (!empty($c['description'])): ?><div class="text-muted small"><?php echo htmlspecialchars((string)$c['description']); ?></div><?php endif; ?>
              <div class="small">
                <?php if (!empty($c['url'])): ?><a href="<?= htmlspecialchars((string)$c['url']) ?>" target="_blank">Link</a><?php endif; ?>
                <?php if (!empty($c['issued_by'])): ?><?= !empty($c['url']) ? ' · ' : '' ?>Issued by <?= htmlspecialchars((string)$c['issued_by']) ?><?php endif; ?>
                <?php if (!empty($c['issued_on'])): ?><?= (!empty($c['url']) || !empty($c['issued_by'])) ? ' · ' : '' ?><?= htmlspecialchars((string)$c['issued_on']) ?><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-muted">No certificates added yet.</div>
        <?php endif; ?>
      </div></div>
    </div>
</div>
  <?php endif; ?>

  <?php if (($user['role'] ?? '') === 'alumni'): ?>
  <div class="row mt-3">
    <div class="col-lg-8">
      <?php if (!empty($roleData['mentorship_availability'])): ?>
        <?php $ap = \Nexus\Models\AlumniPreference::get($GLOBALS['config'], (int)($user['user_id'] ?? 0)); ?>
        <div class="card"><div class="card-body">
          <h5>Mentorship Preferences</h5>
          <div class="row g-2">
            <div class="col-md-4"><strong>Status:</strong> <?= (!empty($ap['mentees_allowed'])) ? 'Taking mentees' : 'Not taking mentees' ?></div>
            <?php if (!empty($ap['meeting_type'])): ?><div class="col-md-4"><strong>Meeting Type:</strong> <?= htmlspecialchars((string)$ap['meeting_type']) ?></div><?php endif; ?>
            <?php if (!empty($ap['timezone'])): ?><div class="col-md-4"><strong>Time Zone:</strong> <?= htmlspecialchars((string)$ap['timezone']) ?></div><?php endif; ?>
            <?php if (!empty($ap['preferred_hours'])): ?><div class="col-12"><strong>Preferred Hours:</strong> <?= htmlspecialchars((string)$ap['preferred_hours']) ?></div><?php endif; ?>
            <?php if (!empty($ap['specific_requirements'])): ?><div class="col-12"><strong>Requirements:</strong> <?= htmlspecialchars((string)$ap['specific_requirements']) ?></div><?php endif; ?>
          </div>
        </div></div>
      <?php else: ?>
        <div class="card"><div class="card-body">
          <h5>Mentorship</h5>
          <div class="text-muted">Not Offering</div>
        </div></div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
<?php if (($user['role'] ?? '') === 'student'): ?>
<div class="row mt-3">
  <div class="col-lg-8">
    <div class="card"><div class="card-body">
      <h5>Projects</h5>
      <?php $projects = \Nexus\Models\StudentProject::listByUser($GLOBALS['config'], (int)($user['user_id'] ?? 0)); ?>
      <?php if (!empty($projects)): ?>
        <?php foreach ($projects as $p): ?>
          <div class="mb-2">
            <div class="fw-semibold"><?= htmlspecialchars((string)($p['title'] ?? 'Untitled')) ?></div>
            <?php if (!empty($p['short_description'])): ?><div class="text-muted small"><?= htmlspecialchars((string)$p['short_description']) ?></div><?php endif; ?>
            <div class="small">
              <?php if (!empty($p['github_url'])): ?><a href="<?= htmlspecialchars((string)$p['github_url']) ?>" target="_blank">GitHub</a><?php endif; ?>
              <?php if (!empty($p['portfolio_url'])): ?><?= !empty($p['github_url']) ? ' · ' : '' ?><a href="<?= htmlspecialchars((string)$p['portfolio_url']) ?>" target="_blank">Portfolio</a><?php endif; ?>
              <?php if (!empty($p['certificate_url'])): ?><?= (!empty($p['github_url']) || !empty($p['portfolio_url'])) ? ' · ' : '' ?><a href="<?= htmlspecialchars((string)$p['certificate_url']) ?>" target="_blank">Certificate</a><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="text-muted">No projects added yet.</div>
      <?php endif; ?>
    </div></div>
  </div>
</div>
<?php endif; ?>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
