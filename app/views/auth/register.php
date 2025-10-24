<?php
  use Nexus\Helpers\Csrf;
  $title = 'Register | UIU NEXUS';
  ob_start();
?>
<div class="row justify-content-center">
  <div class="col-md-7">
    <h2 class="mb-3">Create Account</h2>
    <?php if (!empty($errors ?? [])): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach (($errors ?? []) as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="post" action="/auth/register">
      <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email ?? '') ?>" required>
        <div class="form-text">For student, alumni, and admin, must be a UIU email (@uiu.ac.bd).</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" id="role-select" required>
          <?php $r = $role ?? 'student'; ?>
          <option value="student" <?= $r==='student'?'selected':''; ?>>Student</option>
          <option value="alumni" <?= $r==='alumni'?'selected':''; ?>>Alumni</option>
          <option value="recruiter" <?= $r==='recruiter'?'selected':''; ?>>Recruiter</option>
          <option value="admin" <?= $r==='admin'?'selected':''; ?>>Admin</option>
        </select>
      </div>
      <div id="fields-student" class="border rounded p-3 mb-3" style="display:none;">
        <h6 class="mb-2">Student details</h6>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">First Name</label>
            <input type="text" name="student_first_name" class="form-control" data-required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Last Name</label>
            <input type="text" name="student_last_name" class="form-control" data-required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Student ID</label>
            <input type="text" name="student_university_id" class="form-control" data-required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Department</label>
            <input type="text" name="student_department" class="form-control" data-required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Program</label>
            <select name="student_program" class="form-select" data-required>
              <option value="">Select</option>
              <option value="BSc">BSc</option>
              <option value="MSc">MSc</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Admission Year</label>
            <input type="number" name="student_admission_year" class="form-control" min="1990" max="2100" data-required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Admission Trimester</label>
            <select name="student_admission_trimester" class="form-select" data-required>
              <option value="">Select</option>
              <option value="Spring">Spring</option>
              <option value="Summer">Summer</option>
              <option value="Fall">Fall</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">CGPA</label>
            <input type="number" step="0.01" min="0" max="4" name="student_cgpa" class="form-control" data-required>
          </div>
        </div>
      </div>
      <div id="fields-alumni" class="border rounded p-3 mb-3" style="display:none;">
        <h6 class="mb-2">Alumni details</h6>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">First Name</label>
            <input type="text" name="alumni_first_name" class="form-control" data-required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Last Name</label>
            <input type="text" name="alumni_last_name" class="form-control" data-required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Graduation year</label>
            <input type="number" name="alumni_graduation_year" class="form-control" min="1990" max="2100" data-required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Department</label>
            <input type="text" name="alumni_department" class="form-control" data-required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Program</label>
            <select name="alumni_program" class="form-select" data-required>
              <option value="">Select</option>
              <option value="BSc">BSc</option>
              <option value="MSc">MSc</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Alumni ID</label>
            <input type="text" name="alumni_university_id" class="form-control" data-required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Student ID (historical)</label>
            <input type="text" name="alumni_student_id" class="form-control" data-required>
          </div>
          <div class="col-md-6">
            <label class="form-label">CGPA</label>
            <input type="number" step="0.01" min="0" max="4" name="alumni_cgpa" class="form-control" data-required>
          </div>
        </div>
      </div>
      <div id="fields-recruiter" class="border rounded p-3 mb-3" style="display:none;">
        <h6 class="mb-2">Recruiter details</h6>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Company name</label>
            <input type="text" name="recruiter_company_name" class="form-control" data-required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Company email</label>
            <input type="email" name="recruiter_company_email" class="form-control" data-required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Representative’s First Name</label>
            <input type="text" name="recruiter_rep_first_name" class="form-control" data-required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Representative’s Last Name</label>
            <input type="text" name="recruiter_rep_last_name" class="form-control" data-required>
          </div>
        </div>
      </div>
      <div id="fields-admin" class="border rounded p-3 mb-3" style="display:none;">
        <h6 class="mb-2">Admin details</h6>
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Admin's First Name</label>
            <input type="text" name="admin_first_name" class="form-control" data-required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Admin's Last Name</label>
            <input type="text" name="admin_last_name" class="form-control" data-required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Role</label>
            <input type="text" name="admin_role_title" class="form-control" placeholder="e.g., Super Admin" data-required>
          </div>
        </div>
      </div>
      <script>
        (function(){
          const roleSel = document.getElementById('role-select');
          const blocks = {
            student: document.getElementById('fields-student'),
            alumni: document.getElementById('fields-alumni'),
            recruiter: document.getElementById('fields-recruiter'),
            admin: document.getElementById('fields-admin')
          };
          function sync(){
            const v = roleSel.value;
            Object.keys(blocks).forEach(k => {
              const visible = (k === v);
              const el = blocks[k];
              el.style.display = visible ? 'block' : 'none';
              const fields = el.querySelectorAll('input, select, textarea');
              fields.forEach(f => {
                // Disable hidden fields so browser ignores them
                f.disabled = !visible;
                // Only enforce required for fields marked as data-required in visible block
                if (f.hasAttribute('data-required')) {
                  f.required = visible;
                } else {
                  f.required = false;
                }
              });
            });
          }
          roleSel.addEventListener('change', sync);
          sync();
        })();
      </script>
      <button class="btn btn-dark" type="submit">Register</button>
    </form>
  </div>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
