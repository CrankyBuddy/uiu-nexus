<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Core\Database;
use Nexus\Helpers\Auth;
use Nexus\Helpers\Csrf;
use Nexus\Helpers\Gate;
use Nexus\Helpers\Audit;
use Nexus\Helpers\Flash;
use PDO;
use Nexus\Models\ReportExport;
use Nexus\Models\SystemSetting;
use Nexus\Helpers\Schema;
use Nexus\Models\UserFieldLock;
use Nexus\Models\MentorshipCancellation;
use Nexus\Models\UserWallet;

final class AdminController extends Controller
{
    private function ensureRestrictionEventsTable(\PDO $pdo): void {}
    private function enforceAdminPermission(string $permissionKey = 'manage.permissions'): void
    {
        $uid = Auth::id();
        if (!$uid) {
            header('Location: /auth/login');
            exit;
        }
        // Admins have all permissions implicitly
        $user = Auth::user();
        if (($user['role'] ?? '') === 'admin') {
            return;
        }
        Gate::require($this->config, (int)$uid, $permissionKey);
    }

    public function index(): string
    {
        $this->enforceAdminPermission();
        $pdo = Database::pdo($this->config);
        $stats = [
            'users' => (int)($pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() ?: 0),
            'reports_pending' => (int)($pdo->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn() ?: 0),
            'audit_today' => (int)($pdo->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at)=CURRENT_DATE()")
                ->fetchColumn() ?: 0),
            'cancellations_pending' => (int)($pdo->query("SELECT COUNT(*) FROM mentorship_cancellation_requests WHERE status='pending'")->fetchColumn() ?: 0),
        ];
        $schemaV2 = isset($GLOBALS['schema_v2']) ? (bool)$GLOBALS['schema_v2'] : Schema::isV2($this->config);
        return $this->view('admin.index', ['title' => 'Admin', 'stats' => $stats, 'schema_v2' => $schemaV2]);
    }

    public function cancellations(): string
    {
        $this->enforceAdminPermission('manage.users');
        $items = MentorshipCancellation::listPending($this->config, 200);
        return $this->view('admin.cancellations', ['title' => 'Pending Mentorship Cancellations', 'items' => $items]);
    }

    public function users(): string
    {
        $this->enforceAdminPermission('manage.users');
        $pdo = Database::pdo($this->config);
        $sql = 'SELECT u.user_id, u.email, u.role, u.is_active, u.is_verified, u.last_login_at, COALESCE(w.balance, 0) AS wallet_balance
            FROM users u
            LEFT JOIN user_wallets w ON w.user_id = u.user_id
            ORDER BY u.user_id DESC
            LIMIT 200';
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $this->view('admin.users', ['title' => 'Manage Users', 'users' => $rows]);
    }

    public function toggleUser(): void
    {
        $this->enforceAdminPermission('manage.users');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) { http_response_code(400); echo 'Bad Request'; return; }
        $pdo = Database::pdo($this->config);
        $old = $pdo->prepare('SELECT is_active FROM users WHERE user_id = :id');
        $old->execute([':id' => $userId]);
        $prev = $old->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$prev) { http_response_code(404); echo 'Not Found'; return; }
        $newVal = ((int)$prev['is_active']) ? 0 : 1;
        $stmt = $pdo->prepare('UPDATE users SET is_active = :v WHERE user_id = :id');
        $stmt->execute([':v' => $newVal, ':id' => $userId]);
        Audit::log($this->config, Auth::id(), 'user.toggle_active', 'user', $userId, $prev, ['is_active' => $newVal]);
        $this->redirect('/admin/users');
    }

    public function changeRole(): void
    {
        $this->enforceAdminPermission('manage.users');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $userId = (int)($_POST['user_id'] ?? 0);
        $role = (string)($_POST['role'] ?? '');
        if (!in_array($role, ['student','alumni','recruiter','admin'], true) || $userId <= 0) { http_response_code(400); echo 'Bad Request'; return; }
        $pdo = Database::pdo($this->config);
        $old = $pdo->prepare('SELECT role FROM users WHERE user_id = :id');
        $old->execute([':id' => $userId]);
        $prev = $old->fetch(PDO::FETCH_ASSOC) ?: null;
        $stmt = $pdo->prepare('UPDATE users SET role = :r WHERE user_id = :id');
        $stmt->execute([':r' => $role, ':id' => $userId]);
        Audit::log($this->config, Auth::id(), 'user.change_role', 'user', $userId, $prev, ['role' => $role]);
        $this->redirect('/admin/users');
    }

    public function adjustWallet(): void
    {
        $this->enforceAdminPermission('manage.users');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $userId = (int)($_POST['user_id'] ?? 0);
        $amount = (int)($_POST['amount'] ?? 0);
        $direction = (string)($_POST['direction'] ?? 'credit');
        $note = trim((string)($_POST['note'] ?? ''));
        $returnTo = trim((string)($_POST['return_to'] ?? ''));
        if ($returnTo === '' || strpos($returnTo, '/') !== 0) {
            $returnTo = '/admin/users';
        }
        if ($userId <= 0 || $amount <= 0 || !in_array($direction, ['credit','debit'], true)) { http_response_code(400); echo 'Bad Request'; return; }
        $pdo = Database::pdo($this->config);
        $stmt = $pdo->prepare('SELECT user_id, email, role FROM users WHERE user_id = :id');
        $stmt->execute([':id' => $userId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$target) { http_response_code(404); echo 'User not found'; return; }
        if (!in_array((string)($target['role'] ?? ''), ['student','alumni'], true)) {
            Flash::add('danger', 'Wallet adjustments are limited to students and alumni.');
            $this->redirect($returnTo);
            return;
        }
        $isCredit = $direction === 'credit';
        $before = UserWallet::getByUserId($this->config, $userId);
        $typeName = $isCredit ? 'Admin Wallet Credit' : 'Admin Wallet Debit';
        $desc = $note !== '' ? $note : ($isCredit ? 'Coins added by admin' : 'Coins removed by admin');
        $ok = UserWallet::transact($this->config, $userId, $typeName, $amount, $isCredit, $desc, 'admin_adjustment', null);
        if ($ok) {
            $after = UserWallet::getByUserId($this->config, $userId);
            Audit::log($this->config, Auth::id(), 'wallet.adjust', 'user', $userId, ['wallet' => $before], ['wallet' => $after, 'direction' => $direction, 'amount' => $amount, 'note' => $note]);
            $deltaLabel = ($isCredit ? '+' : '-') . $amount;
            Flash::add('success', 'Wallet updated for ' . ($target['email'] ?? 'user') . ' (' . $deltaLabel . ' coins).');
        } else {
            $errorKey = UserWallet::getLastError() ?? 'unknown_error';
            Flash::add('danger', 'Wallet update failed: ' . $errorKey);
        }
        $this->redirect($returnTo);
    }

    public function permissions(): string
    {
        $this->enforceAdminPermission('manage.permissions');
        $pdo = Database::pdo($this->config);
        $perms = $pdo->query('SELECT * FROM permissions ORDER BY module, permission_key')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $rolePerms = $pdo->query('SELECT rp.role, rp.permission_id, p.permission_key FROM role_permissions rp INNER JOIN permissions p ON p.permission_id = rp.permission_id ORDER BY rp.role')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    return $this->view('admin.permissions', ['title' => 'Permissions', 'perms' => $perms, 'rolePerms' => $rolePerms]);
    }

    public function grantRolePermission(): void
    {
        $this->enforceAdminPermission('manage.permissions');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $role = (string)($_POST['role'] ?? '');
        $pid = (int)($_POST['permission_id'] ?? 0);
        if (!in_array($role, ['student','alumni','recruiter','admin'], true) || $pid <= 0) { http_response_code(400); echo 'Bad Request'; return; }
        $pdo = Database::pdo($this->config);
        $stmt = $pdo->prepare('INSERT IGNORE INTO role_permissions (role, permission_id) VALUES (:r, :p)');
        $stmt->execute([':r' => $role, ':p' => $pid]);
        Audit::log($this->config, Auth::id(), 'permission.grant_role', 'permission', $pid, null, ['role' => $role]);
        $this->redirect('/admin/permissions');
    }

    public function revokeRolePermission(): void
    {
        $this->enforceAdminPermission('manage.permissions');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $role = (string)($_POST['role'] ?? '');
        $pid = (int)($_POST['permission_id'] ?? 0);
        if (!in_array($role, ['student','alumni','recruiter','admin'], true) || $pid <= 0) { http_response_code(400); echo 'Bad Request'; return; }
        $pdo = Database::pdo($this->config);
        $stmt = $pdo->prepare('DELETE FROM role_permissions WHERE role = :r AND permission_id = :p');
        $stmt->execute([':r' => $role, ':p' => $pid]);
        Audit::log($this->config, Auth::id(), 'permission.revoke_role', 'permission', $pid, ['role' => $role], null);
        $this->redirect('/admin/permissions');
    }

    public function reports(): string
    {
        $this->enforceAdminPermission('manage.reports');
        $pdo = Database::pdo($this->config);
        // Optional filters/sorting
        $status = isset($_GET['status']) ? strtolower(trim((string)$_GET['status'])) : '';
        $allowedStatuses = ['pending','investigating','resolved','dismissed'];
        $sort = isset($_GET['sort']) ? strtolower(trim((string)$_GET['sort'])) : 'created_desc';
        $orderBy = 'r.created_at DESC';
        if ($sort === 'created_asc') {
            $orderBy = 'r.created_at ASC';
        } elseif ($sort === 'status_asc') {
            $orderBy = 'r.status ASC, r.created_at DESC';
        } elseif ($sort === 'status_desc') {
            $orderBy = 'r.status DESC, r.created_at DESC';
        }

        $where = [];
        $params = [];
        if ($status !== '' && in_array($status, $allowedStatuses, true)) {
            $where[] = 'r.status = :status';
            $params[':status'] = $status;
        }
        $sql = 'SELECT r.*, u.email as reporter_email FROM reports r INNER JOIN users u ON u.user_id = r.reported_by';
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY ' . $orderBy . ' LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $admins = $pdo->query("SELECT user_id, email FROM users WHERE role='admin' ORDER BY user_id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $this->view('admin.reports', [
            'title' => 'Reports',
            'reports' => $rows,
            'admins' => $admins,
            'filter_status' => in_array($status, $allowedStatuses, true) ? $status : '',
            'sort' => in_array($sort, ['created_desc','created_asc','status_asc','status_desc'], true) ? $sort : 'created_desc',
        ]);
    }

    public function reportDetail(int $id): string
    {
        $this->enforceAdminPermission('manage.reports');
        $pdo = Database::pdo($this->config);
        $stmt = $pdo->prepare('SELECT r.*, u.email as reporter_email FROM reports r INNER JOIN users u ON u.user_id = r.reported_by WHERE r.report_id = :id');
        $stmt->execute([':id' => $id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$report) { http_response_code(404); return 'Report not found'; }
        // Decode evidence JSON
        $evidence = null;
        if (isset($report['evidence']) && $report['evidence'] !== null && $report['evidence'] !== '') {
            $evidence = json_decode((string)$report['evidence'], true);
        }
        // List any files saved under /uploads/reports/{id}
        $publicRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
        $snapDir = $publicRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $id;
        $files = [];
        if (is_dir($snapDir)) {
            foreach (scandir($snapDir) ?: [] as $f) {
                if ($f === '.' || $f === '..') continue;
                $files[] = '/uploads/reports/' . $id . '/' . rawurlencode($f);
            }
        }
        // Admin list for assignment dropdown
        $admins = $pdo->query("SELECT user_id, email FROM users WHERE role='admin' ORDER BY user_id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $this->view('admin.report_detail', [
            'title' => 'Report #' . $id,
            'report' => $report,
            'evidence' => $evidence,
            'files' => $files,
            'admins' => $admins,
        ]);
    }

    public function attachToReport(int $id): void
    {
        $this->enforceAdminPermission('manage.reports');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        // Single file input named 'attachment'
        if (!isset($_FILES['attachment'])) { http_response_code(400); echo 'No file'; return; }
        // Write into /public/uploads/reports/{id}
        $publicRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
        $relDir = 'uploads' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $id;
        $targetDir = $publicRoot . DIRECTORY_SEPARATOR . $relDir;
        if (!is_dir($targetDir)) { @mkdir($targetDir, 0775, true); }
        $f = $_FILES['attachment'];
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { http_response_code(400); echo 'Upload error'; return; }
        $max = 25 * 1024 * 1024; // 25MB
        if (($f['size'] ?? 0) > $max) { http_response_code(400); echo 'File too large'; return; }
        $ext = pathinfo($f['name'] ?? 'file', PATHINFO_EXTENSION) ?: 'bin';
        $safeName = 'admin_' . bin2hex(random_bytes(6)) . '.' . strtolower(preg_replace('/[^A-Za-z0-9]+/', '', $ext));
        $dest = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
        if (!move_uploaded_file($f['tmp_name'], $dest)) { http_response_code(500); echo 'Failed to save'; return; }
        // Optionally append to evidence JSON under attachments_admin
        try {
            $pdo = Database::pdo($this->config);
            $stmt = $pdo->prepare('SELECT evidence FROM reports WHERE report_id = :id');
            $stmt->execute([':id' => $id]);
            $raw = (string)($stmt->fetchColumn() ?: '');
            $ev = $raw !== '' ? (json_decode($raw, true) ?: []) : [];
            if (!isset($ev['attachments_admin']) || !is_array($ev['attachments_admin'])) $ev['attachments_admin'] = [];
            $ev['attachments_admin'][] = [
                'file_name' => $safeName,
                'snapshot_url' => '/uploads/reports/' . $id . '/' . $safeName,
                'uploaded_by' => (int)Auth::id(),
                'uploaded_at' => date('c'),
            ];
            $up = $pdo->prepare('UPDATE reports SET evidence = :e WHERE report_id = :id');
            $up->execute([':e' => json_encode($ev), ':id' => $id]);
        } catch (\Throwable $e) { /* ignore */ }
        $this->redirect('/admin/reports/' . $id);
    }

    public function updateReport(): void
    {
        $this->enforceAdminPermission('manage.reports');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $rid = (int)($_POST['report_id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        $assignedTo = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? (int)$_POST['assigned_to'] : null;
        if ($rid <= 0 || !in_array($status, ['pending','investigating','resolved','dismissed'], true)) { http_response_code(400); echo 'Bad Request'; return; }
        $pdo = Database::pdo($this->config);
        $old = $pdo->prepare('SELECT status, assigned_to FROM reports WHERE report_id = :id');
        $old->execute([':id' => $rid]);
        $prev = $old->fetch(PDO::FETCH_ASSOC) ?: null;
        // Avoid reusing the same named parameter twice (PDO MySQL limitation). Compute resolution flag separately.
        $isResolved = in_array($status, ['resolved','dismissed'], true) ? 1 : 0;
        $stmt = $pdo->prepare('UPDATE reports SET status = :s, assigned_to = :a, resolved_at = CASE WHEN :isr = 1 THEN CURRENT_TIMESTAMP ELSE resolved_at END WHERE report_id = :id');
        $stmt->execute([':s' => $status, ':a' => $assignedTo, ':isr' => $isResolved, ':id' => $rid]);
        Audit::log($this->config, Auth::id(), 'report.update', 'report', $rid, $prev, ['status' => $status, 'assigned_to' => $assignedTo]);
        $this->redirect('/admin/reports');
    }

    public function auditLogs(): string
    {
        $this->enforceAdminPermission('view.audit_logs');
        $pdo = Database::pdo($this->config);
        $rows = $pdo->query('SELECT a.*, u.email FROM audit_logs a LEFT JOIN users u ON u.user_id = a.user_id ORDER BY a.created_at DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    return $this->view('admin.audit_logs', ['title' => 'Audit Logs', 'logs' => $rows]);
    }

    // Stage 4: Field Locks Management
    public function locks(): string
    {
        $this->enforceAdminPermission('manage.users');
        $items = UserFieldLock::recent($this->config, 200);
        return $this->view('admin.locks', [
            'title' => 'Field Locks',
            'items' => $items,
        ]);
    }

    public function userLocks(int $id): string
    {
        $this->enforceAdminPermission('manage.users');
        $pdo = Database::pdo($this->config);
        $uStmt = $pdo->prepare('SELECT user_id, email, role FROM users WHERE user_id = :id');
        $uStmt->execute([':id' => $id]);
        $user = $uStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$user) { http_response_code(404); return 'User not found'; }
        $locks = UserFieldLock::forUser($this->config, $id);
        return $this->view('admin.user_locks', [
            'title' => 'Manage Locks',
            'subject' => $user,
            'locks' => $locks,
        ]);
    }

    public function addLock(): void
    {
        $this->enforceAdminPermission('manage.users');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $uid = (int)($_POST['user_id'] ?? 0);
        $field = trim((string)($_POST['field_key'] ?? ''));
        $until = trim((string)($_POST['locked_until'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($uid <= 0 || $field === '') { http_response_code(400); echo 'Bad Request'; return; }
        $untilDb = null;
        if ($until !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d\\TH:i', $until) ?: \DateTime::createFromFormat('Y-m-d H:i', $until);
            if ($dt) { $untilDb = $dt->format('Y-m-d H:i:s'); }
        }
        $id = UserFieldLock::add($this->config, $uid, $field, (int)Auth::id(), $untilDb, $reason);
        if ($id > 0) {
            Audit::log($this->config, Auth::id(), 'field_lock.add', 'user', $uid, null, ['field_key' => $field, 'locked_until' => $untilDb]);
        }
        $ret = (string)($_POST['return_to'] ?? ('/admin/users/' . $uid . '/locks'));
        $this->redirect($ret);
    }

    public function removeLock(): void
    {
        $this->enforceAdminPermission('manage.users');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $lockId = (int)($_POST['lock_id'] ?? 0);
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($lockId <= 0) { http_response_code(400); echo 'Bad Request'; return; }
        $ok = UserFieldLock::remove($this->config, $lockId);
        if ($ok) { Audit::log($this->config, Auth::id(), 'field_lock.remove', 'user', $uid ?: null, ['lock_id' => $lockId], null); }
        $ret = (string)($_POST['return_to'] ?? ($uid > 0 ? ('/admin/users/' . $uid . '/locks') : '/admin/locks'));
        $this->redirect($ret);
    }

    public function restrictions(): string
    {
        $this->enforceAdminPermission('manage.restrictions');
        $pdo = Database::pdo($this->config);
        $rows = $pdo->query('SELECT r.*, u.email FROM user_feature_restrictions r INNER JOIN users u ON u.user_id = r.user_id ORDER BY r.created_at DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    return $this->view('admin.restrictions', ['title' => 'Restrictions', 'items' => $rows]);
    }

    public function addRestriction(): void
    {
        $this->enforceAdminPermission('manage.restrictions');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $uid = (int)($_POST['user_id'] ?? 0);
        $feature = trim((string)($_POST['feature_key'] ?? ''));
        $until = trim((string)($_POST['restricted_until'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($uid <= 0 || $feature === '') { http_response_code(400); echo 'Bad Request'; return; }
        // Normalize HTML datetime-local (YYYY-MM-DDTHH:MM) to MySQL DATETIME (YYYY-MM-DD HH:MM:SS)
        $untilDb = null;
        if ($until !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $until) ?: \DateTime::createFromFormat('Y-m-d H:i', $until);
            if ($dt) {
                $untilDb = $dt->format('Y-m-d H:i:s');
            }
        }
        $pdo = Database::pdo($this->config);
        $stmt = $pdo->prepare('INSERT INTO user_feature_restrictions (user_id, feature_key, restricted_until, reason, restricted_by) VALUES (:u, :f, :ru, :r, :by)');
        $stmt->execute([':u' => $uid, ':f' => $feature, ':ru' => $untilDb, ':r' => $reason !== '' ? $reason : null, ':by' => Auth::id()]);
        Audit::log($this->config, Auth::id(), 'restriction.add', 'user', $uid, null, ['feature_key' => $feature, 'restricted_until' => $untilDb]);
        // Record history event
        try {
            $this->ensureRestrictionEventsTable($pdo);
            $etype = ($untilDb === null) ? 'ban' : 'suspend';
            $ev = $pdo->prepare('INSERT INTO user_feature_restriction_events (user_id, feature_key, event_type, restricted_until, reason, acted_by) VALUES (:u, :f, :t, :ru, :r, :by)');
            $ev->execute([':u'=>$uid, ':f'=>$feature, ':t'=>$etype, ':ru'=>$untilDb, ':r'=>($reason !== '' ? $reason : null), ':by'=>(int)Auth::id()]);
        } catch (\Throwable $e) { /* ignore */ }
        $this->redirect('/admin/restrictions');
    }

    public function removeRestriction(): void
    {
        $this->enforceAdminPermission('manage.restrictions');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $rid = (int)($_POST['restriction_id'] ?? 0);
        if ($rid <= 0) { http_response_code(400); echo 'Bad Request'; return; }
        $pdo = Database::pdo($this->config);
        $old = $pdo->prepare('SELECT * FROM user_feature_restrictions WHERE restriction_id = :id');
        $old->execute([':id' => $rid]);
        $prev = $old->fetch(PDO::FETCH_ASSOC) ?: null;
        $stmt = $pdo->prepare('DELETE FROM user_feature_restrictions WHERE restriction_id = :id');
        $stmt->execute([':id' => $rid]);
        Audit::log($this->config, Auth::id(), 'restriction.remove', 'user', $prev['user_id'] ?? null, $prev, null);
        // Record history event as lift
        try {
            if ($prev && isset($prev['user_id'], $prev['feature_key'])) {
                $this->ensureRestrictionEventsTable($pdo);
                $ev = $pdo->prepare('INSERT INTO user_feature_restriction_events (user_id, feature_key, event_type, restricted_until, reason, acted_by) VALUES (:u, :f, :t, NULL, NULL, :by)');
                $ev->execute([':u'=>(int)$prev['user_id'], ':f'=>(string)$prev['feature_key'], ':t'=>'lift', ':by'=>(int)Auth::id()]);
            }
        } catch (\Throwable $e) { /* ignore */ }
        $this->redirect('/admin/restrictions');
    }

    // Quick admin actions to suspend/ban users
    public function suspendUser(): void
    {
        $this->enforceAdminPermission('manage.restrictions');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $uid = (int)($_POST['user_id'] ?? 0);
    $scope = (string)($_POST['scope'] ?? 'platform'); // 'platform' | 'social' | 'chat' | 'mentorship'
    $until = trim((string)($_POST['until'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));
    $isBan = ((int)($_POST['ban'] ?? 0)) === 1;
    if ($uid <= 0 || !in_array($scope, ['platform','social','chat','mentorship'], true)) { http_response_code(400); echo 'Bad Request'; return; }
        $untilDb = null;
        if (!$isBan && $until !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d\\TH:i', $until) ?: \DateTime::createFromFormat('Y-m-d H:i', $until);
            if ($dt) { $untilDb = $dt->format('Y-m-d H:i:s'); }
        }
        $pdo = Database::pdo($this->config);
        $stmt = $pdo->prepare('INSERT INTO user_feature_restrictions (user_id, feature_key, restricted_until, reason, restricted_by) VALUES (:u, :f, :ru, :r, :by)');
        $stmt->execute([':u' => $uid, ':f' => $scope, ':ru' => $untilDb, ':r' => ($reason !== '' ? $reason : null), ':by' => (int)Auth::id()]);
        $action = $isBan || $untilDb === null ? 'restriction.ban' : 'restriction.suspend';
        Audit::log($this->config, Auth::id(), $action, 'user', $uid, null, ['scope' => $scope, 'until' => $untilDb]);
        // Record history event (3NF)
        try {
            $this->ensureRestrictionEventsTable($pdo);
            $ev = $pdo->prepare('INSERT INTO user_feature_restriction_events (user_id, feature_key, event_type, restricted_until, reason, acted_by) VALUES (:u, :f, :t, :ru, :r, :by)');
            $ev->execute([':u'=>$uid, ':f'=>$scope, ':t'=>$isBan ? 'ban' : 'suspend', ':ru'=>$untilDb, ':r'=>($reason !== '' ? $reason : null), ':by'=>(int)Auth::id()]);
        } catch (\Throwable $e) { /* ignore for now */ }
        $ret = (string)($_POST['return_to'] ?? '');
        if ($ret !== '') { $this->redirect($ret); return; }
        $this->redirect('/admin/users');
    }

    public function liftSuspension(): void
    {
        $this->enforceAdminPermission('manage.restrictions');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $uid = (int)($_POST['user_id'] ?? 0);
    $scope = (string)($_POST['scope'] ?? 'platform');
    if ($uid <= 0 || !in_array($scope, ['platform','social','chat','mentorship'], true)) { http_response_code(400); echo 'Bad Request'; return; }
        $pdo = Database::pdo($this->config);
        $stmt = $pdo->prepare('DELETE FROM user_feature_restrictions WHERE user_id = :u AND feature_key = :f');
        $stmt->execute([':u' => $uid, ':f' => $scope]);
        Audit::log($this->config, Auth::id(), 'restriction.lift', 'user', $uid, ['scope' => $scope], null);
        // Record history event (3NF)
        try {
            $this->ensureRestrictionEventsTable($pdo);
            $ev = $pdo->prepare('INSERT INTO user_feature_restriction_events (user_id, feature_key, event_type, restricted_until, reason, acted_by) VALUES (:u, :f, :t, NULL, NULL, :by)');
            $ev->execute([':u'=>$uid, ':f'=>$scope, ':t'=>'lift', ':by'=>(int)Auth::id()]);
        } catch (\Throwable $e) { /* ignore for now */ }
        $ret = (string)($_POST['return_to'] ?? '');
        if ($ret !== '') { $this->redirect($ret); return; }
        $this->redirect('/admin/users');
    }

    // Phase 12: Exports
    public function exports(): string
    {
        $this->enforceAdminPermission('manage.reports');
        $pdo = Database::pdo($this->config);
        $rows = $pdo->query('SELECT * FROM report_exports ORDER BY created_at DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    return $this->view('admin.exports', ['title' => 'Exports', 'exports' => $rows]);
    }

    public function export(): void
    {
        $this->enforceAdminPermission('manage.reports');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $type = (string)($_POST['type'] ?? '');
        $jobId = isset($_POST['job_id']) && $_POST['job_id'] !== '' ? (int)$_POST['job_id'] : null;
        $pdo = Database::pdo($this->config);
        $filename = 'export_' . $type . '_' . date('Ymd_His') . '.csv';
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'exports';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        $headers = [];
        $rows = [];

        if ($type === 'applications') {
            if ($jobId === null) { http_response_code(400); echo 'Job ID required'; return; }
            $stmt = $pdo->prepare('SELECT a.application_id, a.job_id, a.student_id, a.status, a.applied_at, a.shortlisted_at, a.interviewed_at, a.decided_at FROM job_applications a WHERE a.job_id = :j ORDER BY a.applied_at DESC');
            $stmt->execute([':j' => $jobId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $headers = ['application_id','job_id','student_id','status','applied_at','shortlisted_at','interviewed_at','decided_at'];
        } elseif ($type === 'reports') {
            $rows = $pdo->query('SELECT report_id, reported_by, target_type, target_id, status, created_at, resolved_at FROM reports ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $headers = ['report_id','reported_by','target_type','target_id','status','created_at','resolved_at'];
        } elseif ($type === 'audit') {
            $rows = $pdo->query('SELECT log_id, user_id, action, entity_type, entity_id, ip_address, created_at FROM audit_logs ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $headers = ['log_id','user_id','action','entity_type','entity_id','ip_address','created_at'];
        } elseif ($type === 'users') {
            $rows = $pdo->query('SELECT user_id, email, role, is_active, is_verified, created_at, last_login_at FROM users ORDER BY user_id ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $headers = ['user_id','email','role','is_active','is_verified','created_at','last_login_at'];
        } else {
            http_response_code(400); echo 'Unknown export type'; return;
        }

        // Write CSV
        $fp = fopen($path, 'w');
        if ($fp === false) { http_response_code(500); echo 'Failed to write file'; return; }
        fputcsv($fp, $headers);
        foreach ($rows as $r) {
            $line = [];
            foreach ($headers as $h) { $line[] = $r[$h] ?? ''; }
            fputcsv($fp, $line);
        }
        fclose($fp);

        // Log export
        $url = '/exports/' . $filename;
        $filters = ['job_id' => $jobId];
        ReportExport::log($this->config, (int)Auth::id(), $type, $filters, 'csv', $url, count($rows));
        Audit::log($this->config, Auth::id(), 'export.create', 'export', null, null, ['type' => $type, 'count' => count($rows)]);
        $this->redirect('/admin/exports');
    }

    // Phase 13: System Settings Management
    public function settings(): string
    {
        $this->enforceAdminPermission('manage.permissions');
        $pdo = Database::pdo($this->config);
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $perPage = isset($_GET['per_page']) && $_GET['per_page'] !== '' ? (int)$_GET['per_page'] : 20;
        $perPage = max(5, min(100, $perPage));
        $page = isset($_GET['page']) && $_GET['page'] !== '' ? (int)$_GET['page'] : 1;
        $page = max(1, $page);
        $params = [];
        $conds = ["setting_key NOT LIKE 'verify_token_%'"];
        if ($q !== '') {
            $conds[] = '(setting_key LIKE :q OR description LIKE :q2)';
            $params[':q'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
        }
        $where = $conds ? (' WHERE ' . implode(' AND ', $conds)) : '';
        // Total count
        $countSql = 'SELECT COUNT(*) FROM system_settings' . $where;
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)($countStmt->fetchColumn() ?: 0);
        $pages = max(1, (int)ceil($total / $perPage));
        if ($page > $pages) { $page = $pages; }
        $offset = ($page - 1) * $perPage;
        // Page data
        $sql = 'SELECT setting_key, setting_value, data_type, description, updated_by, updated_at FROM system_settings' . $where . ' ORDER BY updated_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $this->view('admin.settings', [
            'title' => 'System Settings',
            'items' => $rows,
            'q' => $q,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    public function updateSetting(): void
    {
        $this->enforceAdminPermission('manage.permissions');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $key = trim((string)($_POST['setting_key'] ?? ''));
        $val = (string)($_POST['setting_value'] ?? '');
        $type = (string)($_POST['data_type'] ?? 'string');
        $desc = trim((string)($_POST['description'] ?? ''));
        if ($key === '') { http_response_code(400); echo 'Key required'; return; }
        // Normalize value per data_type
        $normalized = $val;
        if ($type === 'integer') {
            if (!is_numeric($val)) { http_response_code(400); echo 'Invalid integer'; return; }
            $normalized = (string)intval($val);
        } elseif ($type === 'boolean') {
            $truthy = ['1','true','on','yes'];
            $normalized = in_array(strtolower(trim($val)), $truthy, true) ? 'true' : 'false';
        } elseif ($type === 'json') {
            $decoded = json_decode($val, true);
            if (json_last_error() !== JSON_ERROR_NONE) { http_response_code(400); echo 'Invalid JSON'; return; }
            $normalized = json_encode($decoded);
        } else {
            $type = 'string';
        }
        // Fetch old for audit
        $old = SystemSetting::get($this->config, $key);
        SystemSetting::set($this->config, $key, $normalized, $type, $desc !== '' ? $desc : null, (int)Auth::id());
        Audit::log($this->config, Auth::id(), 'system_setting.upsert', 'system_setting', null, $old, ['setting_key' => $key, 'data_type' => $type]);
        $this->redirect('/admin/settings');
    }

    public function deleteSetting(): void
    {
        $this->enforceAdminPermission('manage.permissions');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? '')) { http_response_code(400); echo 'Bad Request'; return; }
        $key = trim((string)($_POST['setting_key'] ?? ''));
        if ($key === '' || strpos($key, 'verify_token_') === 0) { http_response_code(400); echo 'Invalid key'; return; }
        $old = SystemSetting::get($this->config, $key);
        SystemSetting::delete($this->config, $key);
        Audit::log($this->config, Auth::id(), 'system_setting.delete', 'system_setting', null, $old, null);
        $this->redirect('/admin/settings');
    }

    // Show restriction history for a specific user (admin-only)
    public function userRestrictions(int $id): string
    {
        $this->enforceAdminPermission('manage.restrictions');
        $pdo = Database::pdo($this->config);
        // Basic user info
        $uStmt = $pdo->prepare('SELECT user_id, email, role FROM users WHERE user_id = :id');
        $uStmt->execute([':id' => $id]);
        $user = $uStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$user) { http_response_code(404); return 'User not found'; }

        // Event history (most recent first)
        $eStmt = $pdo->prepare('SELECT event_id, feature_key, event_type, restricted_until, reason, acted_by, created_at FROM user_feature_restriction_events WHERE user_id = :id ORDER BY created_at DESC LIMIT 500');
        try {
            $eStmt->execute([':id' => $id]);
            $events = $eStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            $events = [];
        }

        // Summary counts
        $summary = ['ban' => 0, 'suspend' => 0, 'lift' => 0];
        foreach ($events as $ev) {
            $t = $ev['event_type'] ?? '';
            if (isset($summary[$t])) { $summary[$t]++; }
        }

        return $this->view('admin.user_restrictions', [
            'title' => 'Restriction History',
            'user' => $user,
            'events' => $events,
            'summary' => $summary,
        ]);
    }
}
