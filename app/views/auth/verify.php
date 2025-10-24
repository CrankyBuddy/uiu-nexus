<?php
  $title = 'Verify Account | UIU NEXUS';
  ob_start();
?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="alert alert-info">
      <?= htmlspecialchars($message ?? 'Please verify your email to activate your account.') ?>
    </div>
    <a class="btn btn-dark" href="/auth/login">Go to Login</a>
  </div>
</div>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
