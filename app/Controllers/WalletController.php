<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Helpers\Auth;
use Nexus\Core\Database;
use Nexus\Models\UserWallet;
use Nexus\Models\ForumPost;
use Nexus\Models\UserBadge;
use Nexus\Models\Student;
use Nexus\Helpers\Gamify;
use PDO;

final class WalletController extends Controller
{
    public function index(): string
    {
        Auth::enforceAuth();
        $uid = (int) Auth::id();
        $role = (string)(Auth::user()['role'] ?? '');
        UserWallet::ensureExists($this->config, $uid);
        $wallet = UserWallet::getByUserId($this->config, $uid);
        $pdo = Database::pdo($this->config);
    $stmt = $pdo->prepare('SELECT t.*, tt.type_name, tt.is_earning FROM transactions t INNER JOIN transaction_types tt ON tt.type_id = t.type_id WHERE t.user_id = :uid ORDER BY t.created_at DESC LIMIT 10');
    $stmt->execute([':uid' => $uid]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $repStmt = $pdo->prepare('SELECT delta, source, reference_entity_type, reference_entity_id, created_at FROM reputation_events WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10');
    $repStmt->execute([':uid' => $uid]);
    $reputationEvents = $repStmt->fetchAll(PDO::FETCH_ASSOC);
    // Enrich reputation events with human-friendly references (title and URL)
    foreach ($reputationEvents as &$ev) {
        $rtype = (string)($ev['reference_entity_type'] ?? '');
        $rid = isset($ev['reference_entity_id']) ? (int)$ev['reference_entity_id'] : 0;
        $ev['ref_title'] = null;
        $ev['ref_url'] = null;
        $ev['ref_noun'] = null;
        if ($rid > 0 && in_array($rtype, ['forum_post','forum_answer','forum_comment'], true)) {
            try {
                $post = ForumPost::find($this->config, $rid);
                if ($post) {
                    if ($rtype === 'forum_post' || (string)($post['post_type'] ?? '') === 'question') {
                        $title = (string)($post['title'] ?? '') !== '' ? (string)$post['title'] : (string)substr((string)($post['content'] ?? ''), 0, 80);
                        $ev['ref_title'] = $title !== '' ? $title : null;
                        $ev['ref_url'] = '/forum/post/' . $rid;
                        $ev['ref_noun'] = 'post';
                    } else { // answer or comment -> show parent question title
                        $parentId = (int)($post['parent_post_id'] ?? 0);
                        if ($parentId > 0) {
                            $q = ForumPost::find($this->config, $parentId);
                            $qt = $q ? ((string)($q['title'] ?? '') !== '' ? (string)$q['title'] : (string)substr((string)($q['content'] ?? ''), 0, 80)) : '';
                            $ev['ref_title'] = $qt !== '' ? $qt : null;
                            $ev['ref_url'] = '/forum/post/' . $parentId;
                        } else {
                            $ev['ref_title'] = (string)substr((string)($post['content'] ?? ''), 0, 80) ?: null;
                            $ev['ref_url'] = '/forum/post/' . $rid;
                        }
                        $ev['ref_noun'] = ($rtype === 'forum_answer') ? 'answer' : 'comment';
                    }
                }
            } catch (\Throwable $e) { /* ignore enrichment failures */ }
        }
    }
    unset($ev);
        // Free mentorship tickets (students only)
        $freeTickets = null; $freeResetAt = null;
        if ($role === 'student') {
            $student = Student::findByUserId($this->config, $uid);
            if (!$student) { Student::upsert($this->config, $uid, []); $student = Student::findByUserId($this->config, $uid); }
            if ($student) {
                Student::refreshFreeTicketsIfWindowElapsed($this->config, (int)$student['student_id']);
                $stF = $pdo->prepare('SELECT free_mentorship_requests, free_mentorship_reset_at FROM students WHERE student_id = :sid');
                $stF->execute([':sid' => (int)$student['student_id']]);
                $row = $stF->fetch(PDO::FETCH_ASSOC) ?: [];
                $freeTickets = isset($row['free_mentorship_requests']) ? (int)$row['free_mentorship_requests'] : null;
                $freeResetAt = $row['free_mentorship_reset_at'] ?? null;
            }
        }
        // Ensure badges are synced to current reputation thresholds before rendering
        try { Gamify::autoAwardBadges($this->config, $uid); } catch (\Throwable $e) { /* non-fatal */ }
        $userBadges = UserBadge::listByUser($this->config, $uid);
        return $this->view('wallet.index', compact('wallet','transactions','reputationEvents','freeTickets','freeResetAt','userBadges'));
    }
}
