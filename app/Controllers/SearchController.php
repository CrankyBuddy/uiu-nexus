<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Core\Database;
use Nexus\Helpers\Auth;
use Nexus\Helpers\Gate;
use PDO;

final class SearchController extends Controller
{
    public function index(): string
    {
        // Open search to authenticated users; guests can still search public content
        $uid = Auth::id();
        $pdo = Database::pdo($this->config);

        $raw = isset($_GET['q']) ? (string)$_GET['q'] : '';
        $q = trim($raw);
        if ($q !== '' && mb_strlen($q) > 100) {
            $q = mb_substr($q, 0, 100);
        }

        $results = [
            'forum' => [],
            'jobs' => [],
            'events' => [],
            'announcements' => [],
        ];
        $adminUsers = [];
        $userRole = (string) (Auth::user()['role'] ?? '');

        if ($q !== '') {
            $like = '%' . $q . '%';

            // Forum posts (questions/discussions)
            $stmt = $pdo->prepare("SELECT post_id, title, content, post_type, created_at FROM forum_posts WHERE (title LIKE :q OR content LIKE :q2) ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([':q' => $like, ':q2' => $like]);
            $results['forum'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Active & approved job listings
            $stmt = $pdo->prepare("SELECT job_id, job_title, job_description, is_premium FROM job_listings WHERE is_active = 1 AND is_approved = 1 AND (job_title LIKE :q OR job_description LIKE :q2) ORDER BY updated_at DESC LIMIT 10");
            $stmt->execute([':q' => $like, ':q2' => $like]);
            $results['jobs'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Events
            $stmt = $pdo->prepare("SELECT event_id, title, description, event_date FROM events WHERE (title LIKE :q OR description LIKE :q2) ORDER BY event_date DESC LIMIT 10");
            $stmt->execute([':q' => $like, ':q2' => $like]);
            $results['events'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Published announcements filtered by target role (admins see all)
            if ($userRole === 'admin') {
                $stmt = $pdo->prepare("SELECT a.announcement_id, a.title, a.content, a.is_published, a.publish_at FROM announcements a WHERE a.is_published = 1 AND (a.publish_at IS NULL OR a.publish_at <= CURRENT_TIMESTAMP) AND (a.expires_at IS NULL OR a.expires_at > CURRENT_TIMESTAMP) AND (a.title LIKE :q OR a.content LIKE :q2) ORDER BY a.publish_at DESC LIMIT 10");
                $stmt->execute([':q' => $like, ':q2' => $like]);
            } elseif ($userRole !== '') {
                $stmt = $pdo->prepare("SELECT a.announcement_id, a.title, a.content, a.is_published, a.publish_at FROM announcements a INNER JOIN announcement_target_roles r ON r.announcement_id = a.announcement_id WHERE a.is_published = 1 AND (a.publish_at IS NULL OR a.publish_at <= CURRENT_TIMESTAMP) AND (a.expires_at IS NULL OR a.expires_at > CURRENT_TIMESTAMP) AND r.role = :role AND (a.title LIKE :q OR a.content LIKE :q2) ORDER BY a.publish_at DESC LIMIT 10");
                $stmt->execute([':q' => $like, ':q2' => $like, ':role' => $userRole]);
            } else {
                // Guests: show nothing (requires sign-in to tailor by role)
                $stmt = null;
            }
            if ($stmt) {
                $results['announcements'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            // Users (admin or manage.users only)
            $canSeeUsers = false;
            try {
                $canSeeUsers = (($uid && (Auth::user()['role'] ?? '') === 'admin')
                    || Gate::has($this->config, (int)$uid, 'manage.users'));
            } catch (\Throwable $e) { /* ignore */ }
            if ($canSeeUsers) {
                $stmt = $pdo->prepare("SELECT user_id, email, role, is_active FROM users WHERE email LIKE :q ORDER BY user_id DESC LIMIT 10");
                $stmt->execute([':q' => $like]);
                $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        }

        return $this->view('search.index', [
            'title' => 'Search',
            'q' => $q,
            'results' => $results,
            'adminUsers' => $adminUsers,
        ]);
    }
}
