<?php
  use Nexus\Helpers\Auth;
  $title = 'Welcome | ' . ($brand['name'] ?? 'UIU NEXUS');
  ob_start();
?>
<section class="py-5 text-center">
  <div class="container">
    <div class="mx-auto" style="max-width: 800px;">
      <h1 class="display-5 fw-bold mb-3">Welcome to <?= htmlspecialchars($brand['name']) ?></h1>
      <p class="lead text-muted">Connect, collaborate, and grow together across the UIU community — students, alumni, recruiters, and admins.</p>
      <?php if (!Auth::check()): ?>
        <div class="d-flex justify-content-center gap-2 mt-3">
          <a class="btn btn-dark" href="/auth/login"><i class="bi bi-box-arrow-in-right"></i> Login</a>
        </div>
      <?php endif; ?>
    </div>

    <div class="row mt-5 g-3 g-md-4">
      <div class="col-6 col-md-3">
        <a class="card h-100 text-decoration-none" href="/people">
          <div class="card-body d-flex flex-column align-items-center justify-content-center text-center" style="min-height:180px;">
            <div class="icon icon-lg rounded-circle mb-3" style="background:#fff3e9; color:#f56726;"><i class="bi bi-people"></i></div>
            <div class="card-title h5 mb-1">People</div>
            <div class="text-muted small">Discover peers and mentors by skills and roles.</div>
          </div>
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a class="card h-100 text-decoration-none" href="/forum">
          <div class="card-body d-flex flex-column align-items-center justify-content-center text-center" style="min-height:180px;">
            <div class="icon icon-lg rounded-circle mb-3" style="background:#fff3e9; color:#f56726;"><i class="bi bi-chat-left-text"></i></div>
            <div class="card-title h5 mb-1">Forum</div>
            <div class="text-muted small">Ask questions and share knowledge.</div>
          </div>
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a class="card h-100 text-decoration-none" href="/jobs">
          <div class="card-body d-flex flex-column align-items-center justify-content-center text-center" style="min-height:180px;">
            <div class="icon icon-lg rounded-circle mb-3" style="background:#fff3e9; color:#f56726;"><i class="bi bi-briefcase"></i></div>
            <div class="card-title h5 mb-1">Opportunities</div>
            <div class="text-muted small">Jobs, referrals, interviews, and insights.</div>
          </div>
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a class="card h-100 text-decoration-none" href="/events">
          <div class="card-body d-flex flex-column align-items-center justify-content-center text-center" style="min-height:180px;">
            <div class="icon icon-lg rounded-circle mb-3" style="background:#fff3e9; color:#f56726;"><i class="bi bi-calendar-event"></i></div>
            <div class="card-title h5 mb-1">Campus Life</div>
            <div class="text-muted small">Events, announcements, and updates.</div>
          </div>
        </a>
      </div>
    </div>
  </div>
  </section>
<?php
  $content = ob_get_clean();
  include __DIR__ . '/../layouts/main.php';
?>
