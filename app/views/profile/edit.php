<?php
  use Nexus\Helpers\Csrf;
  $title = 'Edit Profile | UIU NEXUS';
  ob_start();
  $isAdminSubject = ((string)($user['role'] ?? '')) === 'admin';
?>
<div class="row">
  <div class="col-lg-9">
    <h2 class="mb-3">Edit Profile</h2>
  <?php $basePath = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); $basePath = ($basePath === '' || $basePath === '/') ? '' : $basePath; ?>
  <?php
    // Parse privacy settings early for sections that reference it (e.g., certificates visibility)
    $ps = $profile['privacy_settings'] ?? '{}';
    $psArr = is_array($ps) ? $ps : (json_decode((string)$ps, true) ?? []);
  ?>
  <form id="profile-edit-form" method="post" action="<?= $basePath ?>/profile/edit" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
      <input type="hidden" name="current_picture" value="<?= htmlspecialchars($profile['profile_picture_url'] ?? '') ?>">
      <?php if (!empty($viewerIsAdmin)): ?>
        <input type="hidden" name="target_user_id" value="<?= (int)($user['user_id'] ?? 0) ?>">
      <?php endif; ?>
      <!-- Basic Information -->
      <div class="card mb-3"><div class="card-body">
        <h5>Basic Information</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label"><?= ($user['role'] ?? '') === 'recruiter' ? "Representative's First Name" : 'First Name' ?></label>
            <input class="form-control" name="first_name" value="<?= htmlspecialchars($profile['first_name'] ?? '') ?>" required <?= empty($viewerIsAdmin) ? 'disabled' : '' ?>>
          </div>
          <div class="col-md-6">
            <label class="form-label"><?= ($user['role'] ?? '') === 'recruiter' ? "Representative's Last Name" : 'Last Name' ?></label>
            <input class="form-control" name="last_name" value="<?= htmlspecialchars($profile['last_name'] ?? '') ?>" required <?= empty($viewerIsAdmin) ? 'disabled' : '' ?>>
          </div>
          <div class="col-12">
            <label class="form-label">Bio</label>
            <textarea class="form-control" name="bio" rows="3"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
          </div>
          <div class="col-md-6 d-flex align-items-end gap-3">
            <?php $pic = $profile['profile_picture_url'] ?? null; ?>
            <?php if (!empty($pic)): ?>
              <img class="nx-avatar" src="<?= htmlspecialchars($pic) ?>" alt="avatar">
            <?php else: ?>
              <div class="nx-avatar nx-avatar-initials" aria-hidden="true">👤</div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Upload Profile Picture</label>
            <input type="file" class="form-control" name="profile_picture" accept="image/png,image/jpeg,image/gif">
            <div class="form-text">PNG, JPG, or GIF.</div>
          </div>
        </div>
      </div></div>

      <!-- Contact & Links (Admins see only Email) -->
      <div class="card mb-3"><div class="card-body">
        <h5><?= $isAdminSubject ? 'Contact' : 'Contact & Links' ?></h5>
        <div class="row g-3">
          <?php if (!$isAdminSubject): ?>
            <div class="col-md-6">
              <label class="form-label">Portfolio URL</label>
              <input type="url" class="form-control" name="portfolio_url" value="<?= htmlspecialchars($profile['portfolio_url'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">LinkedIn URL</label>
              <input type="url" class="form-control" name="linkedin_url" value="<?= htmlspecialchars($profile['linkedin_url'] ?? '') ?>">
            </div>
            <?php if (($user['role'] ?? '') !== 'recruiter'): ?>
              <div class="col-md-6">
                <label class="form-label">Resume/CV URL</label>
                <input type="url" class="form-control" name="resume_url" value="<?= htmlspecialchars($profile['resume_url'] ?? '') ?>">
                <div class="form-text">Optional if you prefer to paste a link instead of uploading a file.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Upload CV (PDF/DOC)</label>
                <input type="file" class="form-control" name="cv_file" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                <div class="form-text">Max 10 MB. Recruiters, mentors (if accepted), and admins can always view.</div>
                <?php
                  $cvDoc = \Nexus\Models\UserDocument::getByUserAndType($GLOBALS['config'], (int)($user['user_id'] ?? 0), 'cv');
                  $hasCv = !empty($cvDoc['file_url']) || !empty($profile['resume_url']);
                ?>
                <?php if ($hasCv): ?>
                  <button type="button" id="btn-remove-cv" class="btn btn-sm btn-outline-danger mt-2">Remove Current CV</button>
                  <script>
                    (function(){
                      const btn = document.getElementById('btn-remove-cv');
                      if (!btn) return;
                      btn.addEventListener('click', function(){
                        if (!confirm('Remove your current CV?')) return;
                        const fd = new FormData();
                        fd.append('_token', '<?= htmlspecialchars(Csrf::token()) ?>');
                        fd.append('user_id', '<?= (int)($user['user_id'] ?? 0) ?>');
                        const returnTo = '/profile/edit' + (<?= !empty($viewerIsAdmin) ? 'true' : 'false' ?> && (<?= (int)(\Nexus\Helpers\Auth::id() ?? 0) ?> !== <?= (int)($user['user_id'] ?? 0) ?>) ? ('?user_id=' + <?= (int)($user['user_id'] ?? 0) ?>) : '');
                        fd.append('return_to', returnTo);
                        const basePath = '<?= $basePath ?>';
                        fetch(`${basePath}/profile/remove-cv`, { method: 'POST', body: fd, credentials: 'same-origin' })
                          .then(() => { window.location.href = `${basePath}${returnTo}`; })
                          .catch(() => { window.location.href = `${basePath}${returnTo}`; });
                      });
                    })();
                  </script>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <div class="col-md-4">
              <label class="form-label">Phone</label>
              <input class="form-control" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Country</label>
              <input class="form-control" name="region" value="<?= htmlspecialchars($profile['region'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Address</label>
              <input class="form-control" name="address" value="<?= htmlspecialchars($profile['address'] ?? '') ?>">
              <div class="form-text">Only admins can view your address.</div>
            </div>
          <?php endif; ?>
          <div class="col-md-4">
            <label class="form-label">Email</label>
            <input class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
          </div>
        </div>
      </div></div>

      <?php if (!$isAdminSubject && ($user['role'] ?? '') !== 'recruiter'): ?>
      <div class="card mb-3"><div class="card-body">
        <h5>Skills and Interests</h5>
        <div class="text-muted small mb-2">Skills: comma-separated (e.g., PHP, MySQL, Leadership)</div>
        <?php $skillsCsv = isset($skills) && is_array($skills) ? implode(', ', $skills) : ''; ?>
        <input class="form-control" name="skills" value="<?= htmlspecialchars($skillsCsv) ?>" placeholder="Add skills">
        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label class="form-label">Career Interests</label>
            <?php
              $ciList = \Nexus\Models\CareerInterest::listNamesByUser($GLOBALS['config'], (int)($user['user_id'] ?? 0));
              $ciCsv = !empty($ciList) ? implode(', ', $ciList) : '';
            ?>
            <input class="form-control" name="career_interests" value="<?= htmlspecialchars($ciCsv) ?>" placeholder="e.g., Data Science, Entrepreneurship">
          </div>
          <?php if (($user['role'] ?? '') === 'student'): ?>
            <?php $courseLists = \Nexus\Models\UserCourseInterest::listByUser($GLOBALS['config'], (int)($user['user_id'] ?? 0)); $coursesStrugglingCsv = !empty($courseLists['struggling']) ? implode(', ', $courseLists['struggling']) : ''; ?>
            <div class="col-md-6">
              <label class="form-label">Courses struggling with</label>
              <input class="form-control" name="courses_struggling" value="<?= htmlspecialchars($coursesStrugglingCsv) ?>" placeholder="e.g., Calculus, Statistics">
            </div>
          <?php endif; ?>
        </div>
  </div></div>
  <?php endif; ?>

  <?php if (!$isAdminSubject && $user['role'] === 'student'): ?>
        <div class="card mb-3"><div class="card-body">
          <h5>Student Details</h5>
            <?php $__disableStudent = empty($viewerIsAdmin); if ($__disableStudent): ?><fieldset disabled><?php endif; ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Admission Program</label>
              <select class="form-select" name="program_level">
                <?php $plevel = $roleData['program_level'] ?? ''; ?>
                <option value="">Select</option>
                <option value="BSc" <?= $plevel==='BSc'?'selected':''; ?>>BSc</option>
                <option value="MSc" <?= $plevel==='MSc'?'selected':''; ?>>MSc</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Department</label>
              <input class="form-control" name="department" value="<?= htmlspecialchars($roleData['department'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">CGPA</label>
              <input class="form-control" name="cgpa" value="<?= htmlspecialchars((string)($roleData['cgpa'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Student ID</label>
              <input class="form-control" name="university_id" value="<?= htmlspecialchars($roleData['university_id'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Admission Year</label>
              <input class="form-control" name="admission_year" value="<?= htmlspecialchars((string)($roleData['admission_year'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Admission Trimester</label>
              <?php $atrim = $roleData['admission_trimester'] ?? ''; ?>
              <select class="form-select" name="admission_trimester">
                <option value="">Select</option>
                <option value="Spring" <?= $atrim==='Spring'?'selected':''; ?>>Spring</option>
                <option value="Summer" <?= $atrim==='Summer'?'selected':''; ?>>Summer</option>
                <option value="Fall" <?= $atrim==='Fall'?'selected':''; ?>>Fall</option>
              </select>
            </div>
          </div>
            <?php if ($__disableStudent): ?></fieldset><?php endif; ?>
        </div></div>

        <div class="card mb-3"><div class="card-body">
          <h5>Projects</h5>
          <div class="text-muted small mb-2">Add multiple projects. Project numbers are assigned automatically.</div>
          <div id="projects-repeat">
            <?php if (!empty($projects)): ?>
              <?php foreach ($projects as $p): ?>
                <div class="row g-2 mb-2 js-project-row">
                  <div class="col-md-2">
                    <input class="form-control js-project-num" value="" placeholder="Project #" disabled>
                    <input type="hidden" name="project_id[]" value="<?= (int)($p['project_id'] ?? 0) ?>">
                  </div>
                  <div class="col-md-4"><input class="form-control js-project-title" name="project_title[]" value="<?= htmlspecialchars((string)($p['title'] ?? '')) ?>" placeholder="Title"></div>
                  <div class="col-md-6"><input class="form-control js-project-desc" name="project_desc[]" value="<?= htmlspecialchars((string)($p['short_description'] ?? '')) ?>" placeholder="Short description"></div>
                  <div class="col-md-4"><input class="form-control js-project-github" name="project_github[]" value="<?= htmlspecialchars((string)($p['github_url'] ?? '')) ?>" placeholder="GitHub URL"></div>
                  <div class="col-md-4"><input class="form-control js-project-portfolio" name="project_portfolio[]" value="<?= htmlspecialchars((string)($p['portfolio_url'] ?? '')) ?>" placeholder="Portfolio URL"></div>
                  <div class="col-md-4"><input class="form-control js-project-certificate" name="project_certificate[]" value="<?= htmlspecialchars((string)($p['certificate_url'] ?? '')) ?>" placeholder="Certificate URL"></div>
                  <div class="col-12 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-project">Remove</button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
            <!-- Always include one empty row for new project insertion -->
            <div class="row g-2 mb-2 js-project-row">
              <div class="col-md-2">
                <input class="form-control js-project-num" value="" placeholder="Project #" disabled>
                <input type="hidden" name="project_id[]" value="">
              </div>
              <div class="col-md-4"><input class="form-control js-project-title" name="project_title[]" placeholder="Title"></div>
              <div class="col-md-6"><input class="form-control js-project-desc" name="project_desc[]" placeholder="Short description"></div>
              <div class="col-md-4"><input class="form-control js-project-github" name="project_github[]" placeholder="GitHub URL"></div>
              <div class="col-md-4"><input class="form-control js-project-portfolio" name="project_portfolio[]" placeholder="Portfolio URL"></div>
              <div class="col-md-4"><input class="form-control js-project-certificate" name="project_certificate[]" placeholder="Certificate URL"></div>
              <div class="col-12 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-project">Remove</button>
              </div>
            </div>
          </div>
          <!-- Collector for delete requests of existing projects -->
          <div id="project-deletes"></div>
          <input type="hidden" name="projects_payload" id="projects_payload" value="">
          <input type="hidden" name="projects_remove_all" id="projects_remove_all" value="0">
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-project">+ Add project</button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-remove-all-projects">Remove all projects</button>
          </div>
          <script>
            (function(){
              const wrap = document.getElementById('projects-repeat');
              const addBtn = document.getElementById('btn-add-project');
              const removeAllBtn = document.getElementById('btn-remove-all-projects');
              const delWrap = document.getElementById('project-deletes');
              const removeAllInput = document.getElementById('projects_remove_all');
              if (!wrap || !addBtn) return;
              // Update the visible sequential project numbers (1..N)
              function renumber(){
                let i = 1;
                wrap.querySelectorAll('.js-project-row .js-project-num').forEach(inp => { inp.value = String(i++); });
              }
              function createBlankRow(){
                const div = document.createElement('div');
                div.className = 'row g-2 mb-2 js-project-row';
                div.innerHTML = `
                  <div class="col-md-2">
                    <input class="form-control js-project-num" value="" placeholder="Project #" disabled>
                    <input type="hidden" name="project_id[]" value="">
                  </div>
                  <div class="col-md-4"><input class="form-control js-project-title" name="project_title[]" placeholder="Title"></div>
                  <div class="col-md-6"><input class="form-control js-project-desc" name="project_desc[]" placeholder="Short description"></div>
                  <div class="col-md-4"><input class="form-control js-project-github" name="project_github[]" placeholder="GitHub URL"></div>
                  <div class="col-md-4"><input class="form-control js-project-portfolio" name="project_portfolio[]" placeholder="Portfolio URL"></div>
                  <div class="col-md-4"><input class="form-control js-project-certificate" name="project_certificate[]" placeholder="Certificate URL"></div>
                  <div class="col-12 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-project">Remove</button>
                  </div>`;
                return div;
              }
              addBtn.addEventListener('click', function(){
                const row = createBlankRow();
                wrap.appendChild(row);
                // Since user is adding, ensure bulk delete flag is off
                if (removeAllInput) removeAllInput.value = '0';
                renumber();
              });
              if (removeAllBtn) {
                removeAllBtn.addEventListener('click', function(){
                  if (!confirm('Remove all projects?')) return;
                  // clear all rows from UI
                  wrap.querySelectorAll('.js-project-row').forEach(row => row.remove());
                  // mark bulk delete for server
                  if (removeAllInput) removeAllInput.value = '1';
                  // keep one fresh blank row so the UI isn't empty
                  wrap.appendChild(createBlankRow());
                  renumber();
                });
              }
              wrap.addEventListener('click', function(e){
                const btn = e.target.closest('.btn-remove-project');
                if (!btn) return;
                const row = btn.closest('.js-project-row');
                if (!row) return;
                const idInput = row.querySelector('input[name="project_id[]"]');
                const idVal = idInput && idInput.value ? parseInt(idInput.value, 10) : 0;
                if (idVal && delWrap) {
                  const hidden = document.createElement('input');
                  hidden.type = 'hidden';
                  hidden.name = 'project_delete_ids[]';
                  hidden.value = String(idVal);
                  delWrap.appendChild(hidden);
                }
                row.remove();
                if (!wrap.querySelector('.js-project-row')) {
                  wrap.appendChild(createBlankRow());
                }
                renumber();
              });
              // Serialization moved to a single global form submit handler below to avoid conflicts
              // initial numbering
              renumber();
            })();
          </script>
        </div></div>

        <div class="card mb-3"><div class="card-body">
          <h5>Certificates / Achievements</h5>
          <div class="text-muted small mb-2">Add multiple certificates. Numbers are assigned automatically.</div>
          <div id="certs-repeat">
            <!-- Existing certs rendered server-side if available -->
            <?php $certs = \Nexus\Models\UserCertificate::listByUser($GLOBALS['config'], (int)($user['user_id'] ?? 0)); ?>
            <?php if (!empty($certs)): ?>
              <?php foreach ($certs as $c): ?>
                <div class="row g-2 mb-2 js-cert-row">
                  <div class="col-md-2">
                    <input class="form-control js-cert-num" value="" placeholder="#" disabled>
                    <input type="hidden" name="cert_id[]" value="<?= (int)($c['certificate_id'] ?? 0) ?>">
                  </div>
                  <div class="col-md-4"><input class="form-control js-cert-title" name="cert_title[]" value="<?= htmlspecialchars((string)($c['title'] ?? '')) ?>" placeholder="Title"></div>
                  <div class="col-md-6"><input class="form-control js-cert-desc" name="cert_desc[]" value="<?= htmlspecialchars((string)($c['description'] ?? '')) ?>" placeholder="Description"></div>
                  <div class="col-md-4"><input class="form-control js-cert-url" name="cert_url[]" value="<?= htmlspecialchars((string)($c['url'] ?? '')) ?>" placeholder="Link"></div>
                  <div class="col-md-4"><input class="form-control js-cert-issuer" name="cert_issuer[]" value="<?= htmlspecialchars((string)($c['issued_by'] ?? '')) ?>" placeholder="Issued By"></div>
                  <div class="col-md-2"><input class="form-control js-cert-date" name="cert_date[]" value="<?= htmlspecialchars((string)($c['issued_on'] ?? '')) ?>" placeholder="YYYY-MM-DD"></div>
                  <div class="col-md-2 text-end d-flex align-items-center justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-cert">Remove</button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
            <!-- Always include a blank row -->
            <div class="row g-2 mb-2 js-cert-row">
              <div class="col-md-2">
                <input class="form-control js-cert-num" value="" placeholder="#" disabled>
                <input type="hidden" name="cert_id[]" value="">
              </div>
              <div class="col-md-4"><input class="form-control js-cert-title" name="cert_title[]" placeholder="Title"></div>
              <div class="col-md-6"><input class="form-control js-cert-desc" name="cert_desc[]" placeholder="Description"></div>
              <div class="col-md-4"><input class="form-control js-cert-url" name="cert_url[]" placeholder="Link"></div>
              <div class="col-md-4"><input class="form-control js-cert-issuer" name="cert_issuer[]" placeholder="Issued By"></div>
              <div class="col-md-2"><input class="form-control js-cert-date" name="cert_date[]" placeholder="YYYY-MM-DD"></div>
              <div class="col-md-2 text-end d-flex align-items-center justify-content-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-cert">Remove</button>
              </div>
            </div>
          </div>
          <div id="cert-deletes"></div>
          <input type="hidden" name="certs_payload" id="certs_payload" value="">
          <input type="hidden" name="certs_remove_all" id="certs_remove_all" value="0">
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-cert">+ Add certificate</button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-remove-all-certs">Remove all certificates</button>
          </div>
          <script>
            (function(){
              const wrap = document.getElementById('certs-repeat');
              const addBtn = document.getElementById('btn-add-cert');
              const removeAllBtn = document.getElementById('btn-remove-all-certs');
              const delWrap = document.getElementById('cert-deletes');
              const removeAllInput = document.getElementById('certs_remove_all');
              if (!wrap || !addBtn) return;
              function renumber(){
                let i = 1;
                wrap.querySelectorAll('.js-cert-row .js-cert-num').forEach(inp => { inp.value = String(i++); });
              }
              function createBlankRow(){
                const div = document.createElement('div');
                div.className = 'row g-2 mb-2 js-cert-row';
                div.innerHTML = `
                  <div class="col-md-2">
                    <input class="form-control js-cert-num" value="" placeholder="#" disabled>
                    <input type="hidden" name="cert_id[]" value="">
                  </div>
                  <div class="col-md-4"><input class="form-control js-cert-title" name="cert_title[]" placeholder="Title"></div>
                  <div class="col-md-6"><input class="form-control js-cert-desc" name="cert_desc[]" placeholder="Description"></div>
                  <div class="col-md-4"><input class="form-control js-cert-url" name="cert_url[]" placeholder="Link"></div>
                  <div class="col-md-4"><input class="form-control js-cert-issuer" name="cert_issuer[]" placeholder="Issued By"></div>
                  <div class="col-md-2"><input class="form-control js-cert-date" name="cert_date[]" placeholder="YYYY-MM-DD"></div>
                  <div class="col-md-2 text-end d-flex align-items-center justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-cert">Remove</button>
                  </div>`;
                return div;
              }
              addBtn.addEventListener('click', function(){
                wrap.appendChild(createBlankRow());
                if (removeAllInput) removeAllInput.value = '0';
                renumber();
              });
              if (removeAllBtn) {
                removeAllBtn.addEventListener('click', function(){
                  if (!confirm('Remove all certificates?')) return;
                  wrap.querySelectorAll('.js-cert-row').forEach(row => row.remove());
                  if (removeAllInput) removeAllInput.value = '1';
                  wrap.appendChild(createBlankRow());
                  renumber();
                });
              }
              wrap.addEventListener('click', function(e){
                const btn = e.target.closest('.btn-remove-cert');
                if (!btn) return;
                const row = btn.closest('.js-cert-row');
                if (!row) return;
                const idInput = row.querySelector('input[name="cert_id[]"]');
                const idVal = idInput && idInput.value ? parseInt(idInput.value, 10) : 0;
                if (idVal && delWrap) {
                  const hidden = document.createElement('input');
                  hidden.type = 'hidden'; hidden.name = 'cert_delete_ids[]'; hidden.value = String(idVal);
                  delWrap.appendChild(hidden);
                }
                row.remove();
                if (!wrap.querySelector('.js-cert-row')) { wrap.appendChild(createBlankRow()); }
                renumber();
              });
              // Serialization moved to a single global form submit handler below to avoid conflicts
              renumber();
            })();
          </script>
        </div></div>
      <?php endif; ?>

  <?php if (!$isAdminSubject && $user['role'] === 'alumni'): ?>
        <div class="card mb-3"><div class="card-body">
          <h5>Alumni Details</h5>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Company</label>
              <input class="form-control" name="company" value="<?= htmlspecialchars($roleData['company'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Job Title</label>
              <input class="form-control" name="job_title" value="<?= htmlspecialchars($roleData['job_title'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Years of Experience</label>
              <input class="form-control" name="years_of_experience" value="<?= htmlspecialchars((string)($roleData['years_of_experience'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Graduation Year</label>
              <input class="form-control" name="graduation_year" value="<?= htmlspecialchars((string)($roleData['graduation_year'] ?? '')) ?>" <?= empty($viewerIsAdmin) ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-3">
              <label class="form-label">CGPA</label>
              <input class="form-control" name="alumni_cgpa" value="<?= htmlspecialchars((string)($roleData['cgpa'] ?? '')) ?>" <?= empty($viewerIsAdmin) ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-3">
              <label class="form-label">Max Mentorship Slots</label>
              <input class="form-control" name="max_mentorship_slots" value="<?= htmlspecialchars((string)($roleData['max_mentorship_slots'] ?? '5')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Alumni ID</label>
              <input class="form-control" name="alumni_university_id" value="<?= htmlspecialchars((string)($roleData['university_id'] ?? '')) ?>" <?= empty($viewerIsAdmin) ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-6">
              <label class="form-label">Student ID</label>
              <input class="form-control" name="alumni_student_id" value="<?= htmlspecialchars((string)($roleData['student_id_number'] ?? '')) ?>" <?= empty($viewerIsAdmin) ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-6">
              <label class="form-label">Program</label>
              <?php $alumPl = $roleData['program_level'] ?? ''; ?>
              <?php if (!empty($viewerIsAdmin)): ?>
                <select class="form-select" name="alumni_program">
                  <option value="">Select</option>
                  <option value="BSc" <?= $alumPl==='BSc'?'selected':''; ?>>BSc</option>
                  <option value="MSc" <?= $alumPl==='MSc'?'selected':''; ?>>MSc</option>
                </select>
              <?php else: ?>
                <input class="form-control" value="<?= htmlspecialchars((string)$alumPl) ?>" disabled>
              <?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label">Industry</label>
              <input class="form-control" name="industry" value="<?= htmlspecialchars((string)($roleData['industry'] ?? '')) ?>">
            </div>
          </div>
        </div></div>

        <!-- Mentorship availability toggle above preferences -->
        <div class="card mb-2"><div class="card-body py-2">
          <div class="d-flex align-items-center justify-content-between">
            <h6 class="mb-0">Mentorship Availability</h6>
            <div class="form-check form-switch m-0">
              <input class="form-check-input" type="checkbox" name="mentorship_availability" id="mentorship_availability" <?= !empty($roleData['mentorship_availability']) ? 'checked' : '' ?>>
              <label class="form-check-label ms-2" for="mentorship_availability">Enable</label>
            </div>
          </div>
        </div></div>

        <div id="mentorship_prefs_card" class="card mb-3"><div class="card-body">
          <h5>Mentorship Preferences</h5>
          <?php $__mentorshipOn = !empty($roleData['mentorship_availability']); ?>
          <fieldset id="mentorship_prefs_fieldset" <?= $__mentorshipOn ? '' : 'disabled' ?> >
          <div class="row g-3">
            <div class="col-md-4">
              <?php $taking = !empty($roleData['mentorship_availability']); ?>
              <label class="form-label d-block">Mentees Allowed</label>
              <div class="btn-group">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="menteesAllowedBtn" data-bs-toggle="dropdown" aria-expanded="false">
                  <?= $taking ? 'Taking mentees' : 'Not taking mentees' ?>
                </button>
                <ul class="dropdown-menu" aria-labelledby="menteesAllowedBtn">
                  <li><a class="dropdown-item js-mentees-opt" href="#" data-value="1">Taking mentees</a></li>
                  <li><a class="dropdown-item js-mentees-opt" href="#" data-value="0">Not taking mentees</a></li>
                </ul>
              </div>
              <input type="hidden" name="mentees_allowed" id="mentees_allowed" value="<?= $taking ? '1' : '0' ?>">
              <div class="form-text">Choose whether you are currently accepting mentees.</div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Meeting Type</label>
              <select class="form-select" name="meeting_type">
                <option value="">Select</option>
                <option value="online">Online</option>
                <option value="in-person">In-person</option>
                <option value="hybrid">Hybrid</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Specific Requirements</label>
              <input class="form-control" name="specific_requirements" placeholder="e.g., CV required, portfolio required">
            </div>
            <div class="col-md-4">
              <label class="form-label">Time Zone</label>
              <input class="form-control" name="timezone" placeholder="e.g., Asia/Dhaka">
            </div>
            <div class="col-md-8">
              <label class="form-label">Preferred Hours</label>
              <input class="form-control" name="preferred_hours" placeholder="e.g., Weekdays 7-9 PM">
            </div>
            <div class="form-text">Add skills separated by commas in the main Skills section above.</div>
            </div>
          </fieldset>
          </div>
          <script>
            (function(){
              const btn = document.getElementById('menteesAllowedBtn');
              const input = document.getElementById('mentees_allowed');
              document.querySelectorAll('.js-mentees-opt').forEach(a => {
                a.addEventListener('click', (e) => {
                  e.preventDefault();
                  const val = a.getAttribute('data-value') || '0';
                  input.value = val;
                  btn.textContent = (val === '1') ? 'Taking mentees' : 'Not taking mentees';
                });
              });
              // Gate preferences by availability toggle
              const avail = document.getElementById('mentorship_availability');
              const fieldset = document.getElementById('mentorship_prefs_fieldset');
              const card = document.getElementById('mentorship_prefs_card');
              function syncPrefsGate(){
                const on = !!(avail && avail.checked);
                if (fieldset) fieldset.disabled = !on;
                if (card) {
                  if (on) { card.classList.remove('opacity-50'); }
                  else { card.classList.add('opacity-50'); }
                }
              }
              if (avail) {
                avail.addEventListener('change', syncPrefsGate);
                // initial
                syncPrefsGate();
              }
            })();
          </script>
        </div></div>
      <?php endif; ?>

  <?php if (!$isAdminSubject && $user['role'] === 'alumni'): ?>
        <div class="card mb-3"><div class="card-body">
          <h5>Certificates / Achievements</h5>
          <div class="text-muted small mb-2">Add multiple certificates. Numbers are assigned automatically.</div>
          <div id="certs-repeat">
            <!-- Existing certs rendered server-side if available -->
            <?php $certs = \Nexus\Models\UserCertificate::listByUser($GLOBALS['config'], (int)($user['user_id'] ?? 0)); ?>
            <?php if (!empty($certs)): ?>
              <?php foreach ($certs as $c): ?>
                <div class="row g-2 mb-2 js-cert-row">
                  <div class="col-md-2">
                    <input class="form-control js-cert-num" value="" placeholder="#" disabled>
                    <input type="hidden" name="cert_id[]" value="<?= (int)($c['certificate_id'] ?? 0) ?>">
                  </div>
                  <div class="col-md-4"><input class="form-control js-cert-title" name="cert_title[]" value="<?= htmlspecialchars((string)($c['title'] ?? '')) ?>" placeholder="Title"></div>
                  <div class="col-md-6"><input class="form-control js-cert-desc" name="cert_desc[]" value="<?= htmlspecialchars((string)($c['description'] ?? '')) ?>" placeholder="Description"></div>
                  <div class="col-md-4"><input class="form-control js-cert-url" name="cert_url[]" value="<?= htmlspecialchars((string)($c['url'] ?? '')) ?>" placeholder="Link"></div>
                  <div class="col-md-4"><input class="form-control js-cert-issuer" name="cert_issuer[]" value="<?= htmlspecialchars((string)($c['issued_by'] ?? '')) ?>" placeholder="Issued By"></div>
                  <div class="col-md-2"><input class="form-control js-cert-date" name="cert_date[]" value="<?= htmlspecialchars((string)($c['issued_on'] ?? '')) ?>" placeholder="YYYY-MM-DD"></div>
                  <div class="col-md-2 text-end d-flex align-items-center justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-cert">Remove</button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
            <!-- Always include a blank row -->
            <div class="row g-2 mb-2 js-cert-row">
              <div class="col-md-2">
                <input class="form-control js-cert-num" value="" placeholder="#" disabled>
                <input type="hidden" name="cert_id[]" value="">
              </div>
              <div class="col-md-4"><input class="form-control js-cert-title" name="cert_title[]" placeholder="Title"></div>
              <div class="col-md-6"><input class="form-control js-cert-desc" name="cert_desc[]" placeholder="Description"></div>
              <div class="col-md-4"><input class="form-control js-cert-url" name="cert_url[]" placeholder="Link"></div>
              <div class="col-md-4"><input class="form-control js-cert-issuer" name="cert_issuer[]" placeholder="Issued By"></div>
              <div class="col-md-2"><input class="form-control js-cert-date" name="cert_date[]" placeholder="YYYY-MM-DD"></div>
              <div class="col-md-2 text-end d-flex align-items-center justify-content-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-cert">Remove</button>
              </div>
            </div>
          </div>
          <div id="cert-deletes"></div>
          <input type="hidden" name="certs_payload" id="certs_payload" value="">
          <input type="hidden" name="certs_remove_all" id="certs_remove_all" value="0">
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-cert">+ Add certificate</button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-remove-all-certs">Remove all certificates</button>
          </div>
          <script>
            (function(){
              const wrap = document.getElementById('certs-repeat');
              const addBtn = document.getElementById('btn-add-cert');
              const removeAllBtn = document.getElementById('btn-remove-all-certs');
              const delWrap = document.getElementById('cert-deletes');
              const removeAllInput = document.getElementById('certs_remove_all');
              if (!wrap || !addBtn) return;
              function renumber(){
                let i = 1;
                wrap.querySelectorAll('.js-cert-row .js-cert-num').forEach(inp => { inp.value = String(i++); });
              }
              function createBlankRow(){
                const div = document.createElement('div');
                div.className = 'row g-2 mb-2 js-cert-row';
                div.innerHTML = `
                  <div class="col-md-2">
                    <input class="form-control js-cert-num" value="" placeholder="#" disabled>
                    <input type="hidden" name="cert_id[]" value="">
                  </div>
                  <div class="col-md-4"><input class="form-control js-cert-title" name="cert_title[]" placeholder="Title"></div>
                  <div class="col-md-6"><input class="form-control js-cert-desc" name="cert_desc[]" placeholder="Description"></div>
                  <div class="col-md-4"><input class="form-control js-cert-url" name="cert_url[]" placeholder="Link"></div>
                  <div class="col-md-4"><input class="form-control js-cert-issuer" name="cert_issuer[]" placeholder="Issued By"></div>
                  <div class="col-md-2"><input class="form-control js-cert-date" name="cert_date[]" placeholder="YYYY-MM-DD"></div>
                  <div class="col-md-2 text-end d-flex align-items-center justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-cert">Remove</button>
                  </div>`;
                return div;
              }
              addBtn.addEventListener('click', function(){
                wrap.appendChild(createBlankRow());
                if (removeAllInput) removeAllInput.value = '0';
                renumber();
              });
              if (removeAllBtn) {
                removeAllBtn.addEventListener('click', function(){
                  if (!confirm('Remove all certificates?')) return;
                  wrap.querySelectorAll('.js-cert-row').forEach(row => row.remove());
                  if (removeAllInput) removeAllInput.value = '1';
                  wrap.appendChild(createBlankRow());
                  renumber();
                });
              }
              wrap.addEventListener('click', function(e){
                const btn = e.target.closest('.btn-remove-cert');
                if (!btn) return;
                const row = btn.closest('.js-cert-row');
                if (!row) return;
                const idInput = row.querySelector('input[name="cert_id[]"]');
                const idVal = idInput && idInput.value ? parseInt(idInput.value, 10) : 0;
                if (idVal && delWrap) {
                  const hidden = document.createElement('input');
                  hidden.type = 'hidden'; hidden.name = 'cert_delete_ids[]'; hidden.value = String(idVal);
                  delWrap.appendChild(hidden);
                }
                row.remove();
                if (!wrap.querySelector('.js-cert-row')) { wrap.appendChild(createBlankRow()); }
                renumber();
              });
              renumber();
            })();
          </script>
        </div></div>
  <?php endif; ?>

  <?php if (!$isAdminSubject && $user['role'] === 'recruiter'): ?>
        <div class="card mb-3"><div class="card-body">
          <h5>Company Details</h5>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Company Name</label>
              <input class="form-control" name="company_name" value="<?= htmlspecialchars($roleData['company_name'] ?? '') ?>" <?= empty($viewerIsAdmin) ? 'disabled' : '' ?> >
            </div>
            <div class="col-md-6">
              <label class="form-label">Website</label>
              <input class="form-control" name="company_website" value="<?= htmlspecialchars($roleData['company_website'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Industry</label>
              <input class="form-control" name="industry" value="<?= htmlspecialchars($roleData['industry'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">HR Contact Name</label>
              <input class="form-control" name="hr_contact_name" value="<?= htmlspecialchars($roleData['hr_contact_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">HR Contact Email</label>
              <input class="form-control" name="hr_contact_email" value="<?= htmlspecialchars($roleData['hr_contact_email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Company Size</label>
              <input class="form-control" name="company_size" value="<?= htmlspecialchars($roleData['company_size'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Company Location</label>
              <input class="form-control" name="company_location" value="<?= htmlspecialchars($roleData['company_location'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Career Page URL</label>
              <input class="form-control" name="career_page_url" value="<?= htmlspecialchars($roleData['career_page_url'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Company LinkedIn</label>
              <input class="form-control" name="company_linkedin" value="<?= htmlspecialchars($roleData['company_linkedin'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">HR Phone</label>
              <input class="form-control" name="hr_contact_phone" value="<?= htmlspecialchars($roleData['hr_contact_phone'] ?? '') ?>">
            </div>
          </div>
        </div></div>

        
      <?php endif; ?>

      <?php if (!$isAdminSubject): ?>
      <div class="card mb-3"><div class="card-body">
        <h5>Privacy</h5>
        <?php
          $ps = $profile['privacy_settings'] ?? '{}';
          $psArr = is_array($ps) ? $ps : (json_decode((string)$ps, true) ?? []);
          $rawFlags = \Nexus\Models\ProfileVisibility::getFlagsForUser($GLOBALS['config'], (int)($user['user_id'] ?? 0));
          $phoneFlag = (bool)($rawFlags['phone'] ?? ($psArr['contact_visible'] ?? false));
          $emailFlag = (bool)($rawFlags['email'] ?? ($psArr['email_visible'] ?? false));
          $resumeFlag = (bool)(($rawFlags['cv'] ?? ($rawFlags['resume'] ?? ($psArr['resume_visible'] ?? false))));
        ?>
        <div class="form-check form-switch mb-1">
          <input class="form-check-input" type="checkbox" id="contact_visible_privacy" <?= $phoneFlag ? 'checked' : '' ?>>
          <label class="form-check-label" for="contact_visible_privacy">Show Phone</label>
        </div>
        <div class="form-check form-switch mb-1">
          <input class="form-check-input" type="checkbox" id="email_visible_privacy" <?= $emailFlag ? 'checked' : '' ?>>
          <label class="form-check-label" for="email_visible_privacy">Show Email</label>
        </div>
        <?php if (($user['role'] ?? '') !== 'recruiter'): ?>
          <div class="form-check form-switch mb-1">
            <input class="form-check-input" type="checkbox" id="resume_visible_privacy" <?= $resumeFlag ? 'checked' : '' ?> >
            <label class="form-check-label" for="resume_visible_privacy">Show Resume/CV</label>
          </div>
          <?php $cgpaFlag = (bool)(($rawFlags['cgpa'] ?? ($psArr['cgpa_visible'] ?? false))); ?>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="cgpa_visible" <?= $cgpaFlag ? 'checked' : '' ?>>
            <label class="form-check-label" for="cgpa_visible">Show CGPA</label>
          </div>
        <?php endif; ?>
        <script>
          (function(){
            const basePath = '<?= $basePath ?>';
            const uid = <?= (int)($user['user_id'] ?? 0) ?>;
            const token = '<?= htmlspecialchars(Csrf::token()) ?>';
            function postToggle(field, visible){
              const fd = new FormData();
              fd.append('_token', token);
              fd.append('user_id', String(uid));
              fd.append('field', field);
              fd.append('visible', visible ? '1' : '0');
              const returnTo = '/profile/edit' + (<?= !empty($viewerIsAdmin) ? 'true' : 'false' ?> && (<?= (int)(\Nexus\Helpers\Auth::id() ?? 0) ?> !== uid) ? ('?user_id=' + uid) : '');
              fd.append('return_to', returnTo);
              fetch(`${basePath}/profile/visibility`, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(() => { window.location.href = `${basePath}${returnTo}`; })
                .catch(() => { window.location.href = `${basePath}${returnTo}`; });
            }
            const phoneEl = document.getElementById('contact_visible_privacy');
            const emailEl = document.getElementById('email_visible_privacy');
            const cgpaEl = (<?= ($user['role'] ?? '') !== 'recruiter' ? 'true' : 'false' ?>) ? document.getElementById('cgpa_visible') : null;
            const resumeEl = (<?= ($user['role'] ?? '') !== 'recruiter' ? 'true' : 'false' ?>) ? document.getElementById('resume_visible_privacy') : null;
            if (phoneEl) phoneEl.addEventListener('change', () => postToggle('phone', phoneEl.checked));
            if (emailEl) emailEl.addEventListener('change', () => postToggle('email', emailEl.checked));
            if (cgpaEl) cgpaEl.addEventListener('change', () => postToggle('cgpa', cgpaEl.checked));
            if (resumeEl) resumeEl.addEventListener('change', () => postToggle('cv', resumeEl.checked));
          })();
        </script>
      </div></div>
      <?php endif; ?>

      <!-- Change Password -->
      <?php
        // Show change password section when:
        // - Subject is not an admin user (regular users), or
        // - Viewer is admin (admins can reset others' passwords)
        $viewerIdPwd = (int)(\Nexus\Helpers\Auth::id() ?? 0);
        $viewerRolePwd = (string)(\Nexus\Helpers\Auth::user()['role'] ?? '');
        $viewerIsAdminPwd = ($viewerRolePwd === 'admin') || \Nexus\Helpers\Gate::has($GLOBALS['config'], $viewerIdPwd, 'manage.permissions');
        $adminEditingOtherPwd = $viewerIsAdminPwd && $viewerIdPwd !== (int)($user['user_id'] ?? 0);
      ?>
      <?php if (!$isAdminSubject || $viewerIsAdminPwd): ?>
      <div class="card mb-3"><div class="card-body">
        <h5>Change Password</h5>
        <div class="row g-3">
          <?php if (!$adminEditingOtherPwd): ?>
            <div class="col-md-4">
              <label class="form-label">Current Password</label>
              <input type="password" class="form-control" name="current_password" autocomplete="current-password">
            </div>
          <?php else: ?>
            <div class="col-12">
              <div class="alert alert-secondary py-2 mb-0">Admin is resetting this user's password. Current password is not required.</div>
            </div>
          <?php endif; ?>
          <div class="col-md-4">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="new_password" autocomplete="new-password">
          </div>
          <div class="col-md-4">
            <label class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" name="confirm_password" autocomplete="new-password">
          </div>
        </div>
        <div class="form-text mt-2">Leave blank to keep the current password.</div>
      </div></div>
      <?php endif; ?>

      <script>
        // Final, global submit handler to serialize both Projects and Certificates in one place
        (function(){
          const form = document.getElementById('profile-edit-form') || document.querySelector('form');
          if (!form) return;
          form.addEventListener('submit', function(){
            // Projects
            const pWrap = document.getElementById('projects-repeat');
            const pPayload = document.getElementById('projects_payload');
            const pRemoveAll = document.getElementById('projects_remove_all');
            if (pWrap && pPayload) {
              const pItems = [];
              pWrap.querySelectorAll('.js-project-row').forEach(row => {
                const get = sel => (row.querySelector(sel)?.value || '').trim();
                const id = get('input[name="project_id[]"]');
                let title = get('input.js-project-title');
                const desc = get('input.js-project-desc');
                const github = get('input.js-project-github');
                const portfolio = get('input.js-project-portfolio');
                const cert = get('input.js-project-certificate');
                const hasAny = !!(title || desc || github || portfolio || cert);
                if (!hasAny) return;
                if (!title) title = '(Untitled Project)';
                pItems.push({ id, title, short_description: desc, github_url: github, portfolio_url: portfolio, certificate_url: cert });
              });
              pPayload.value = JSON.stringify(pItems);
              if (pItems.length > 0 && pRemoveAll) { pRemoveAll.value = '0'; }
            }
            // Certificates
            const cWrap = document.getElementById('certs-repeat');
            const cPayload = document.getElementById('certs_payload');
            const cRemoveAll = document.getElementById('certs_remove_all');
            if (cWrap && cPayload) {
              const cItems = [];
              cWrap.querySelectorAll('.js-cert-row').forEach(row => {
                const get = sel => (row.querySelector(sel)?.value || '').trim();
                const id = get('input[name="cert_id[]"]');
                let title = get('input.js-cert-title');
                const description = get('input.js-cert-desc');
                const url = get('input.js-cert-url');
                const issued_by = get('input.js-cert-issuer');
                const issued_on = get('input.js-cert-date');
                const hasAny = !!(title || description || url || issued_by || issued_on);
                if (!hasAny) return;
                if (!title) title = '(Untitled Achievement)';
                cItems.push({ id, title, description, url, issued_by, issued_on });
              });
              cPayload.value = JSON.stringify(cItems);
              if (cItems.length > 0 && cRemoveAll) { cRemoveAll.value = '0'; }
            }
          });
        })();
      </script>
      <button class="btn btn-dark" type="submit">Save Changes</button>
  <a class="btn btn-outline-dark" href="<?= $basePath ?>/profile">Cancel</a>
    </form>
  </div>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
