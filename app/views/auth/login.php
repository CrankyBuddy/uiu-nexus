<?php
  use Nexus\Helpers\Csrf;
  $title = 'Login | UIU NEXUS';
  ob_start();
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <h2 class="mb-3">Login</h2>
    <?php if (!empty($errors ?? [])): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach (($errors ?? []) as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    <form method="post" action="/auth/login">
      <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button class="btn btn-dark" type="submit">Login</button>
    </form>
  </div>
  </div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
