<?php
?><!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'UIU NEXUS') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <?php
    // Ensure asset paths work both at domain root and when app is served from a subfolder (e.g., /nexus/public)
    $__basePath = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $__basePath = ($__basePath === '' || $__basePath === '/') ? '' : $__basePath;
  $__assetVer = '20251024-4'; // cache-busting version for static assets
  ?>
  <link href="<?= $__basePath ?>/assets/css/app.css?v=<?= $__assetVer ?>" rel="stylesheet">
    <style>
      :root{
        --nexus-primary: #f56726;
        --nexus-bg: #fffffe;
      }
      body{ background: var(--nexus-bg); font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
      /* White header */
      .navbar{ background: #fff; border-bottom: 1px solid #eee; }
      .navbar .navbar-brand, .navbar .nav-link{ color:#111 !important; font-weight:600; }
      .navbar .nav-link:hover { color: var(--nexus-primary) !important; background:transparent !important; }
      /* Header buttons as plain text until hover; on hover -> orange pill */
      .navbar .btn {
        background: transparent !important;
        color: #111 !important;
        border-color: transparent !important;
        box-shadow: none !important;
      }
      .navbar .btn:hover, .navbar .btn:focus {
        background: var(--nexus-primary) !important;
        color: #fff !important;
        border-color: var(--nexus-primary) !important;
      }
      .badge-nexus{ background: var(--nexus-primary); color:#fff; }
    </style>
    <script>
      window.__BASE__ = (function(){
        var s = "<?= htmlspecialchars(str_replace('\\','/', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'))) ?>";
        return (s && s !== '/') ? s : '';
      })();
    </script>
  </head>
  <body>
    <nav class="navbar navbar-expand-lg navbar-light">
      <div class="container-fluid">
        <a class="navbar-brand" href="/">UIU NEXUS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nxNav" aria-controls="nxNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nxNav">
        <div class="ms-auto d-flex flex-wrap w-100 mt-2 mt-lg-0 align-items-center nav-actions">
          <?php \Nexus\Helpers\Auth::check() ? $isAuth = true : $isAuth = false; ?>
          <div class="nav-left d-flex align-items-center gap-3 flex-wrap">
            <?php if ($isAuth): ?>
              <?php $roleNav = (string)(\Nexus\Helpers\Auth::user()['role'] ?? ''); ?>
              <?php if (in_array($roleNav, ['student','alumni'], true)): ?>
                <a class="btn btn-sm btn-outline-dark" href="/wallet"><i class="bi bi-coin"></i> Wallet</a>
              <?php endif; ?>
              <a class="btn btn-sm btn-outline-dark" href="/leaderboards"><i class="bi bi-trophy"></i> Leaderboards</a>
              <a class="btn btn-sm btn-outline-dark" href="/events"><i class="bi bi-calendar-event"></i> Events</a>
              <a class="btn btn-sm btn-outline-dark" href="/people"><i class="bi bi-people"></i> People</a>
              <a class="btn btn-sm btn-outline-dark" href="/announcements"><i class="bi bi-megaphone"></i> Announcements</a>
              <?php
                $unreadCount = 0;
                try {
                  $cfg = $GLOBALS['config'] ?? null;
                  if ($cfg instanceof \Nexus\Core\Config) {
                    $pdo = \Nexus\Core\Database::pdo($cfg);
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :u AND is_read = 0');
                    $stmt->execute([':u' => (int)\Nexus\Helpers\Auth::id()]);
                    $unreadCount = (int)($stmt->fetchColumn() ?: 0);
                  }
                } catch (\Throwable $e) { $unreadCount = 0; }
              ?>
              <a class="btn btn-sm btn-outline-dark position-relative" href="/notifications"><i class="bi bi-bell"></i> Notifications
                <?php if ($unreadCount > 0): ?>
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unreadCount ?></span>
                <?php endif; ?>
              </a>
              <?php
                $unreadMsg = 0;
                try {
                  $cfg = $GLOBALS['config'] ?? null;
                  if ($cfg instanceof \Nexus\Core\Config) {
                    $pdo = \Nexus\Core\Database::pdo($cfg);
                    $uid = (int)\Nexus\Helpers\Auth::id();
                    // Match unread logic used in the inbox list: count messages where is_read = 0 and not sent by the user
                    $sqlUnread = 'SELECT COUNT(*)
                      FROM messages m
                      JOIN conversation_participants cp
                        ON cp.conversation_id = m.conversation_id AND cp.user_id = :u_cp
                      WHERE m.sender_id <> :u_sender AND m.is_read = 0';
                    $st = $pdo->prepare($sqlUnread);
                    $st->execute([':u_cp' => $uid, ':u_sender' => $uid]);
                    $unreadMsg = (int)($st->fetchColumn() ?: 0);
                  }
                } catch (\Throwable $e) { $unreadMsg = 0; }
              ?>
              <a class="btn btn-sm btn-outline-dark position-relative" href="/messages"><i class="bi bi-chat-dots"></i> Messages
                <?php if ($unreadMsg > 0): ?>
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $unreadMsg ?></span>
                <?php endif; ?>
              </a>
              <?php
                $isForumAdmin = false; $forumBadge = 0;
                try {
                  $cfg2 = $GLOBALS['config'] ?? null;
                  if ($cfg2 instanceof \Nexus\Core\Config) {
                    $uid2 = (int)(\Nexus\Helpers\Auth::id() ?? 0);
                    $isForumAdmin = ((\Nexus\Helpers\Auth::user()['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($cfg2, $uid2, 'manage.permissions');
                    if ($isForumAdmin) {
                      // Admins: show total pending (rejected/approved never alert for admin)
                      $forumBadge = (int)\Nexus\Models\ForumPost::countPending($cfg2, null);
                    } else {
                      // Users: sum freshly rejected + freshly approved since last seen markers
                      $pdo2 = \Nexus\Core\Database::pdo($cfg2);
                      // Rejected since last seen
                      $sinceR = '';
                      if (!empty($_SESSION['forum_seen_rejected_' . $uid2])) {
                        $sinceR = (string)$_SESSION['forum_seen_rejected_' . $uid2];
                      } else {
                        $lastSeenR = \Nexus\Models\SystemSetting::get($cfg2, 'forum_seen_rejected_' . $uid2);
                        if ($lastSeenR && isset($lastSeenR['setting_value'])) { $sinceR = trim((string)$lastSeenR['setting_value']); }
                      }
                      $sinceParamR = ($sinceR !== '') ? $sinceR : '1970-01-01 00:00:00';
                      $sqlR = "SELECT COUNT(*) FROM forum_posts 
                               WHERE author_id = :u 
                                 AND (
                                   moderation_status = 'rejected'
                                   OR (moderation_status IS NULL AND is_approved = 0 
                                       AND (rejected_at IS NOT NULL OR rejected_by IS NOT NULL OR reject_reason IS NOT NULL))
                                 )
                                 AND COALESCE(rejected_at, updated_at, created_at) > :t";
                      $stR = $pdo2->prepare($sqlR);
                      $stR->execute([':u' => $uid2, ':t' => $sinceParamR]);
                      $rejectedNew = (int)($stR->fetchColumn() ?: 0);

                      // Approved since last seen
                      $sinceA = '';
                      if (!empty($_SESSION['forum_seen_approved_' . $uid2])) {
                        $sinceA = (string)$_SESSION['forum_seen_approved_' . $uid2];
                      } else {
                        $lastSeenA = \Nexus\Models\SystemSetting::get($cfg2, 'forum_seen_approved_' . $uid2);
                        if ($lastSeenA && isset($lastSeenA['setting_value'])) { $sinceA = trim((string)$lastSeenA['setting_value']); }
                      }
                      $sinceParamA = ($sinceA !== '') ? $sinceA : '1970-01-01 00:00:00';
                      $sqlA = "SELECT COUNT(*) FROM forum_posts 
                               WHERE author_id = :u 
                                 AND (moderation_status = 'approved' OR (moderation_status IS NULL AND is_approved = 1))
                                 AND COALESCE(approved_at, updated_at, created_at) > :t";
                      $stA = $pdo2->prepare($sqlA);
                      $stA->execute([':u' => $uid2, ':t' => $sinceParamA]);
                      $approvedNew = (int)($stA->fetchColumn() ?: 0);

                      $forumBadge = $rejectedNew + $approvedNew;
                    }
                  }
                } catch (\Throwable $e) { $forumBadge = 0; }
              ?>
              <a class="btn btn-sm btn-outline-dark position-relative" href="/forum"><i class="bi bi-chat-left-text"></i> Forum
                <?php if ($forumBadge > 0): ?>
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $forumBadge ?></span>
                <?php endif; ?>
              </a>
              <a class="btn btn-sm btn-outline-dark" href="/jobs"><i class="bi bi-briefcase"></i> Jobs</a>
              <?php if (($roleNav ?? '') !== 'recruiter'): ?>
                <a class="btn btn-sm btn-outline-dark" href="/mentorship"><i class="bi bi-mortarboard"></i> Mentorship</a>
              <?php endif; ?>
              <?php 
                $canAdmin = false; 
                try {
                  $cfg = $GLOBALS['config'] ?? null;
                  if ($cfg instanceof \Nexus\Core\Config) {
                    $canAdmin = ((\Nexus\Helpers\Auth::user()['role'] ?? '') === 'admin')
                      || \Nexus\Helpers\Gate::has($cfg, (int)\Nexus\Helpers\Auth::id(), 'manage.permissions')
                      || \Nexus\Helpers\Gate::has($cfg, (int)\Nexus\Helpers\Auth::id(), 'manage.reports');
                  }
                } catch (\Throwable $e) {}
                if ($canAdmin): ?>
                <a class="btn btn-sm btn-outline-dark" href="/admin"><i class="bi bi-gear"></i> Admin</a>
                <a class="btn btn-sm btn-dark" href="/admin/reports"><i class="bi bi-graph-up"></i> Reports</a>
              <?php endif; ?>
              <?php $user = \Nexus\Helpers\Auth::user(); ?>
              <?php if (!empty($user) && (($user['role'] ?? '') === 'alumni')): ?>
                <a class="btn btn-sm btn-outline-dark" href="/mentorship/my-listings"><i class="bi bi-card-list"></i> My Listings</a>
              <?php endif; ?>
              <?php if (!empty($user) && (($user['role'] ?? '') === 'student')): ?>
                <?php
                  // Show wallet coins and free mentorship tickets inline
                  try {
                    $cfgH = $GLOBALS['config'] ?? null; $uidH = (int)($user['user_id'] ?? 0);
                    if ($cfgH instanceof \Nexus\Core\Config && $uidH) {
                      $pdoH = \Nexus\Core\Database::pdo($cfgH);
                      // Wallet
                      $stWH = $pdoH->prepare('SELECT balance FROM user_wallets WHERE user_id = :u');
                      $stWH->execute([':u' => $uidH]);
                      $balH = (int)($stWH->fetchColumn() ?: 0);
                      // Ensure student record exists, refresh window, then read tickets
                      try {
                        $stuH = \Nexus\Models\Student::findByUserId($cfgH, $uidH);
                        if (!$stuH) { \Nexus\Models\Student::upsert($cfgH, $uidH, []); $stuH = \Nexus\Models\Student::findByUserId($cfgH, $uidH); }
                        if ($stuH) { \Nexus\Models\Student::refreshFreeTicketsIfWindowElapsed($cfgH, (int)$stuH['student_id']); }
                      } catch (\Throwable $e) {}
                      $stFH = $pdoH->prepare('SELECT s.free_mentorship_requests FROM students s WHERE s.user_id = :u');
                      $stFH->execute([':u' => $uidH]);
                      $freeH = (int)($stFH->fetchColumn() ?: 0);
                      echo '<span class="btn btn-sm btn-outline-dark disabled" tabindex="-1" aria-disabled="true">Coins: ' . (int)$balH . '</span>';
                      echo '<span class="btn btn-sm btn-outline-dark disabled" tabindex="-1" aria-disabled="true">Free Tickets: ' . (int)$freeH . '</span>';
                    }
                  } catch (\Throwable $e) {}
                ?>
                <a class="btn btn-sm btn-outline-dark" href="/mentorship/requests/mine"><i class="bi bi-inbox"></i> My Requests</a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <div class="nav-auth ms-auto d-flex align-items-center gap-2">
            <?php if (!$isAuth): ?>
              <a class="btn btn-sm btn-dark" href="/auth/login"><i class="bi bi-box-arrow-in-right"></i> Login</a>
            <?php else: ?>
              <?php if ((\Nexus\Helpers\Auth::user()['role'] ?? '') === 'admin'): ?>
                <a class="btn btn-sm btn-outline-dark" href="/auth/register"><i class="bi bi-person-plus"></i> Add User</a>
              <?php endif; ?>
              <?php
                // Profile button: show user's Firstname Lastname
                $navUserName = '';
                try {
                  $cfgU = $GLOBALS['config'] ?? null;
                  if ($cfgU instanceof \Nexus\Core\Config) {
                    $uidU = (int)(\Nexus\Helpers\Auth::id() ?? 0);
                    if ($uidU) {
                      $pdoU = \Nexus\Core\Database::pdo($cfgU);
                        $stU = $pdoU->prepare("SELECT TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) AS full_name FROM user_profiles WHERE user_id = :u");
                      $stU->execute([':u' => $uidU]);
                      $full = trim((string)($stU->fetchColumn() ?: ''));
                      if ($full !== '') { $navUserName = $full; }
                    }
                  }
                } catch (\Throwable $e) { $navUserName = ''; }
                if ($navUserName === '' ) { $navUserName = (string)(\Nexus\Helpers\Auth::user()['email'] ?? 'Profile'); }
              ?>
              <a class="btn btn-sm btn-outline-dark" href="/profile"><?= htmlspecialchars($navUserName) ?></a>
              <form method="post" action="/auth/logout" class="m-0 p-0">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(\Nexus\Helpers\Csrf::token()) ?>">
                <button class="btn btn-sm btn-dark" type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        </div>
      </div>
    </nav>
    <main class="container py-4">
      <?php
        // Render flash messages (success/danger/warning/info)
        try {
          $flashes = \Nexus\Helpers\Flash::consume();
        } catch (\Throwable $e) { $flashes = []; }
        if (!empty($flashes) && is_array($flashes)):
      ?>
        <div class="mb-3">
          <?php foreach ($flashes as $f): $t = $f['type'] ?? 'info'; $msg = (string)($f['message'] ?? ''); ?>
            <div class="alert alert-<?= htmlspecialchars($t) ?>" role="alert"><?= htmlspecialchars($msg) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?= $content ?? '' ?>
    </main>
    <footer class="text-center py-4 text-muted">&copy; <?= date('Y') ?> UIU NEXUS</footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
  </html>
