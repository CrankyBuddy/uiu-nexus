<?php
  $title = 'Ask a Question | Forum';
  ob_start();
  use Nexus\Helpers\Csrf;
?>
<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">Ask a Question</div>
      <div class="card-body">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/forum/create">
          <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select class="form-select" name="category_id" required>
              <option value="">Select...</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Details</label>
            <textarea class="form-control" name="content" rows="6" required></textarea>
          </div>
          <button class="btn btn-dark" type="submit">Post</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <a class="btn btn-sm btn-outline-dark w-100" href="/forum">Back to Forum</a>
  </div>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
