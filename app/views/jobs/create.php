<?php
  $title = 'Post a Job | UIU NEXUS';
  ob_start();
  use Nexus\Helpers\Csrf;
?>
<h2 class="mb-3">Post a Job</h2>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" action="/jobs/create">
  <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
  <div class="mb-3"><label class="form-label">Title</label><input required name="job_title" class="form-control"></div>
  <div class="mb-3"><label class="form-label">Description</label><textarea required name="job_description" rows="6" class="form-control"></textarea></div>
  <div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Category</label><select required name="category_id" class="form-select"><?php foreach ($categories as $c): ?><option value="<?= (int)$c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Type</label><select required name="type_id" class="form-select"><?php foreach ($types as $t): ?><option value="<?= (int)$t['type_id'] ?>"><?= htmlspecialchars($t['type_name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Location</label><select required name="location_id" class="form-select"><?php foreach ($locations as $l): ?><option value="<?= (int)$l['location_id'] ?>"><?= htmlspecialchars($l['location_name']) ?></option><?php endforeach; ?></select></div>
  </div>
  <div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Duration</label><input name="duration" class="form-control" placeholder="e.g., 6 months"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Salary Min</label><input name="salary_range_min" class="form-control" type="number"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Salary Max</label><input name="salary_range_max" class="form-control" type="number"></div>
  </div>
  <div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Stipend</label><input name="stipend_amount" class="form-control" type="number"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Deadline</label><input name="application_deadline" class="form-control" type="date"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Required Skills (comma-separated)</label><input name="required_skills" class="form-control"></div>
  </div>
  <hr>
  <h5 class="mb-2">Application Questions (optional)</h5>
  <div id="appQuestions"></div>
  <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addQuestion()">+ Add Question</button>
  <template id="qTpl">
    <div class="border rounded p-2 mb-2 qrow">
      <div class="row g-2 align-items-center">
        <div class="col-md-7"><input name="question_text[]" class="form-control" placeholder="Question text"></div>
        <div class="col-md-3">
          <select name="question_type[]" class="form-select">
            <option value="text">Short text</option>
            <option value="textarea">Paragraph</option>
          </select>
        </div>
        <div class="col-md-2 form-check">
          <input class="form-check-input" type="checkbox" name="question_required[]" value="1" id="qreq_{{i}}">
          <label class="form-check-label" for="qreq_{{i}}">Required</label>
        </div>
      </div>
      <div class="text-end mt-2"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.qrow').remove()">Remove</button></div>
    </div>
  </template>
  <script>
    let qCounter = 0;
    function addQuestion() {
      const tpl = document.getElementById('qTpl').innerHTML.replaceAll('{{i}}', (++qCounter).toString());
      const wrap = document.createElement('div');
      wrap.innerHTML = tpl.trim();
      document.getElementById('appQuestions').appendChild(wrap.firstElementChild);
    }
  </script>
  <button class="btn btn-dark" type="submit">Publish Job</button>
</form>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
