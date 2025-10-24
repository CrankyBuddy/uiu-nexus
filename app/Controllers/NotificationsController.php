<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Helpers\Auth;
use Nexus\Core\Database;
use Nexus\Helpers\Csrf;
use PDO;

final class NotificationsController extends Controller
{
    public function index(): string
    {
        Auth::enforceAuth();
        $uid = Auth::id();
        $pdo = Database::pdo($this->config);
        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = :u ORDER BY created_at DESC LIMIT 100');
        $stmt->execute([':u' => $uid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $this->view('notifications/index', ['notifications' => $rows]);
    }

    public function markRead(int $id): string
    {
        Auth::enforceAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $uid = Auth::id();
        $pdo = Database::pdo($this->config);
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = :id AND user_id = :u');
        $stmt->execute([':id' => $id, ':u' => $uid]);
        $this->redirect('/notifications');
        return '';
    }

    public function markAllRead(): string
    {
        Auth::enforceAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $uid = Auth::id();
        $pdo = Database::pdo($this->config);
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :u AND is_read = 0');
        $stmt->execute([':u' => $uid]);
        $this->redirect('/notifications');
        return '';
    }
}
