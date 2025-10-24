<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Helpers\Auth;
use Nexus\Helpers\Csrf;
use Nexus\Models\Announcement;
use Nexus\Models\Notification;
use Nexus\Core\Database;
use PDO;

final class AnnouncementsController extends Controller
{
    public function index(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        $role = $user['role'] ?? null;
        $isAdmin = ($role === 'admin');

        $adminOnly = false;
        if ($isAdmin) {
            // Admins see all published announcements by default, or only those targeting admins when ?admin_only=1
            $adminOnly = (isset($_GET['admin_only']) && $_GET['admin_only'] == '1');
            if ($adminOnly) {
                $anns = Announcement::listPublished($this->config, 'admin', 50);
            } else {
                $anns = Announcement::listPublished($this->config, null, 50);
            }
        } else {
            // Non-admins see only announcements for their role
            $anns = Announcement::listPublished($this->config, $role, 20);
        }

        return $this->view('announcements/index', [
            'announcements' => $anns,
            'isAdmin' => $isAdmin,
            'adminOnly' => $adminOnly,
        ]);
    }

    public function create(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'admin') { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
            $title = trim((string)($_POST['title'] ?? ''));
            $content = trim((string)($_POST['content'] ?? ''));
            $roles = array_values(array_unique(array_map('strval', (array)($_POST['target_roles'] ?? []))));
            $allowedRoles = ['student','alumni','recruiter','admin'];
            $roles = array_values(array_intersect($roles, $allowedRoles));
            $publish = isset($_POST['is_published']);
            if ($title === '' || $content === '' || empty($roles)) {
                return $this->view('announcements/create', ['error' => 'Title, content and at least one role are required.']);
            }
            $annId = Announcement::create($this->config, $title, $content, (int)$user['user_id'], $roles, $publish, null, null);
            if ($publish) {
                // notify all users within those roles (simple baseline)
                $pdo = Database::pdo($this->config);
                $placeholders = implode(',', array_fill(0, count($roles), '?'));
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role IN ($placeholders) AND is_active = 1");
                $stmt->execute(array_values($roles));
                $uids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                foreach ($uids as $uid) {
                    Notification::send($this->config, (int)$uid, 'New Announcement', $title, 'announcement', 'announcement', $annId, '/announcements');
                }
            }
            // Base-aware redirect (works under subfolders like /nexus/public)
            $this->redirect('/announcements');
            return '';
        }
        return $this->view('announcements/create');
    }
}
