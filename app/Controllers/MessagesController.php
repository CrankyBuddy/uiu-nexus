<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Helpers\Auth;
use Nexus\Helpers\Csrf;
use Nexus\Helpers\Messaging;
use Nexus\Core\Database;
use Nexus\Models\Conversation;
use Nexus\Models\Message;
use Nexus\Models\Notification;

final class MessagesController extends Controller
{
    public function inbox(): string
    {
        Auth::enforceAuth();
        $uid = (int)Auth::id();
        $q = trim((string)($_GET['q'] ?? ''));
        $page = (int)($_GET['page'] ?? 1);
        $per = (int)($_GET['per_page'] ?? 20);
        $list = Conversation::listForUser($this->config, $uid, $per, $page, $q);
        return $this->view('messages/inbox', ['conversations' => $list, 'q' => $q, 'page' => $page, 'per_page' => $per]);
    }

    public function new(): string
    {
        Auth::enforceAuth();
        $uid = (int)Auth::id();
        // Social suspension blocks starting new conversations for non-admins
        $me = Auth::user();
        try {
            $isAdmin = (($me['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($this->config, $uid, 'manage.permissions');
        } catch (\Throwable $e) { $isAdmin = false; }
        // Either general social or chat-specific suspension blocks starting a new conversation
        if (!$isAdmin && (\Nexus\Helpers\Restrictions::isSocialSuspended($this->config, $uid) || \Nexus\Helpers\Restrictions::isChatSuspended($this->config, $uid))) {
            http_response_code(403);
            return $this->view('messages/new', ['error' => 'Your social features are currently suspended.', 'eligible' => []]);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
            $rid = (int)($_POST['user_id'] ?? 0);
            if ($rid <= 0) {
                return $this->view('messages/new', ['error' => 'Please select a user to message.', 'eligible' => Messaging::eligibleRecipients($this->config, $uid, $_GET['q'] ?? null)]);
            }
            if ($rid === $uid) {
                return $this->view('messages/new', ['error' => 'You cannot start a conversation with yourself.', 'eligible' => Messaging::eligibleRecipients($this->config, $uid, $_GET['q'] ?? null)]);
            }
            // ACL: ensure the current user can message the selected recipient
            if (!Messaging::canMessage($this->config, $uid, $rid)) {
                if (!\Nexus\Helpers\Messaging::hasDmQuota($this->config, $uid)) {
                    http_response_code(429);
                    return $this->view('messages/new', ['error' => 'Daily DM quota reached. Try again tomorrow.', 'eligible' => Messaging::eligibleRecipients($this->config, $uid, $_GET['q'] ?? null)]);
                }
                http_response_code(403);
                return $this->view('messages/new', ['error' => 'You are not allowed to message this user.', 'eligible' => Messaging::eligibleRecipients($this->config, $uid, $_GET['q'] ?? null)]);
            }
            // reuse existing direct conversation if present
            $cid = Conversation::findDirectBetween($this->config, $uid, $rid);
            if (!$cid) {
                $cid = Conversation::create($this->config, $uid, 'direct', null);
                Conversation::addParticipant($this->config, $cid, $uid);
                Conversation::addParticipant($this->config, $cid, $rid);
            }
            $this->redirect('/messages/' . $cid);
            return '';
        }
        // GET: show eligible users picker
    $q = trim((string)($_GET['q'] ?? ''));
    $page = (int)($_GET['page'] ?? 1);
    $per = (int)($_GET['per_page'] ?? 20);
    $role = isset($_GET['role']) ? (string)$_GET['role'] : '';
    $eligible = Messaging::eligibleRecipients($this->config, $uid, $q, $page, $per, $role);
    $counts = Messaging::eligibleCounts($this->config, $uid, $q);
    return $this->view('messages/new', ['eligible' => $eligible, 'q' => $q, 'page' => $page, 'per_page' => $per, 'role' => $role, 'counts' => $counts]);
    }

    public function show(int $id): string
    {
        Auth::enforceAuth();
        $uid = (int)Auth::id();
        if (!Conversation::userIsParticipant($this->config, $id, $uid)) {
            http_response_code(403);
            echo 'Forbidden';
            return '';
        }
        $conv = Conversation::find($this->config, $id);
        if (!$conv) { http_response_code(404); echo 'Conversation not found'; return ''; }
        $msgs = Message::list($this->config, $id, 100);
            // Set friendly title for direct chats
            $title = Conversation::displayTitleForUser($this->config, $conv, $uid);
        // Mark unread messages from others as read now
        Message::markReadForUser($this->config, $id, $uid, null);
            $conv['title'] = $title;
        $participants = Conversation::participants($this->config, $id);
        return $this->view('messages.show', ['conversation' => $conv, 'messages' => $msgs, 'participants' => $participants, 'current_user_id' => $uid]);
    }

    // GET: seen status for messages in a conversation, returns last read timestamp per user (excluding requester)
    public function seen(int $id): string
    {
        Auth::enforceAuth();
        $uid = (int)Auth::id();
        if (!Conversation::userIsParticipant($this->config, $id, $uid)) { http_response_code(403); return json_encode(['error' => 'forbidden']); }
        $pdo = \Nexus\Core\Database::pdo($this->config);
        // Get my latest message in this conversation
        $sqlLastMine = 'SELECT m.message_id, m.created_at FROM messages m WHERE m.conversation_id = :c AND m.sender_id = :u ORDER BY m.created_at DESC LIMIT 1';
        $st = $pdo->prepare($sqlLastMine);
        $st->execute([':c' => $id, ':u' => $uid]);
        $mine = $st->fetch(\PDO::FETCH_ASSOC) ?: null;
        $allRead = false;
        if ($mine) {
            // Get conversation participants except me
            $stp = $pdo->prepare('SELECT user_id FROM conversation_participants WHERE conversation_id = :c AND user_id <> :u');
            $stp->execute([':c' => $id, ':u' => $uid]);
            $others = $stp->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            if (!empty($others)) {
                // Count how many of the others have a read receipt for my latest message
                $in = implode(',', array_fill(0, count($others), '?'));
                $params = array_merge([(int)$mine['message_id']], array_map('intval', $others));
                $stR = $pdo->prepare("SELECT COUNT(*) FROM message_reads WHERE message_id = ? AND user_id IN ($in)");
                $stR->execute($params);
                $cnt = (int)($stR->fetchColumn() ?: 0);
                $allRead = ($cnt === count($others));
            }
        }
        header('Content-Type: application/json');
        return json_encode(['last_mine_read' => $allRead, 'last_mine' => $mine['created_at'] ?? null]);
    }

    // Reported message IDs in a conversation for live UI updates
    public function reported(int $id): string
    {
        Auth::enforceAuth();
        $uid = (int)Auth::id();
        if (!Conversation::userIsParticipant($this->config, $id, $uid)) { http_response_code(403); return json_encode(['error' => 'forbidden']); }
        $pdo = Database::pdo($this->config);
        $st = $pdo->prepare("SELECT r.target_id AS message_id FROM reports r JOIN messages m ON m.message_id = r.target_id WHERE r.target_type='message' AND m.conversation_id = :c");
        $st->execute([':c' => $id]);
        $rows = $st->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        header('Content-Type: application/json');
        return json_encode(array_map('intval', $rows));
    }

    public function send(int $id): string
    {
        Auth::enforceAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        // Helper to detect AJAX/JSON requests
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $xrw = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $isAjax = (stripos($accept, 'application/json') !== false) || (strtolower($xrw) === 'xmlhttprequest');
        if (!Csrf::check($_POST['_token'] ?? null)) {
            http_response_code(419);
            if ($isAjax) { header('Content-Type: application/json'); return json_encode(['ok' => false, 'error' => 'CSRF']); }
            echo 'CSRF';
            return '';
        }
        $uid = (int)Auth::id();
        // Social suspension blocks sending messages for non-admins
        $me = Auth::user();
        try { $isAdmin = (($me['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($this->config, $uid, 'manage.permissions'); } catch (\Throwable $e) { $isAdmin = false; }
        if (!$isAdmin && (\Nexus\Helpers\Restrictions::isSocialSuspended($this->config, $uid) || \Nexus\Helpers\Restrictions::isChatSuspended($this->config, $uid))) {
            http_response_code(403);
            if ($isAjax) { header('Content-Type: application/json'); return json_encode(['ok' => false, 'error' => 'Your social features are currently suspended.']); }
            echo 'Your social features are currently suspended.'; return '';
        }
        if (!Conversation::userIsParticipant($this->config, $id, $uid)) {
            http_response_code(403);
            if ($isAjax) { header('Content-Type: application/json'); return json_encode(['ok' => false, 'error' => 'Forbidden']); }
            echo 'Forbidden'; return '';
        }
        // Apply DM quota for non-admins in direct conversations
        $pdo = Database::pdo($this->config);
        $isDirect = (function() use ($pdo, $id): bool { $st=$pdo->prepare('SELECT conversation_type FROM conversations WHERE conversation_id = :c'); $st->execute([':c'=>$id]); return ((string)($st->fetchColumn() ?: '')) === 'direct'; })();
        if ($isDirect) {
            $me = Auth::user();
            $isAdmin = (($me['role'] ?? '') === 'admin');
            if (!$isAdmin && !\Nexus\Helpers\Messaging::hasDmQuota($this->config, $uid)) {
                http_response_code(429);
                if ($isAjax) { header('Content-Type: application/json'); return json_encode(['ok' => false, 'error' => 'Daily DM quota reached.']); }
                echo 'Daily DM quota reached.'; return '';
            }
        }
        // Enforce recruiter-initiated and block rules on replies too
        $pdo = Database::pdo($this->config);
        $stp = $pdo->prepare('SELECT u.user_id, u.role FROM conversation_participants cp JOIN users u ON u.user_id = cp.user_id WHERE cp.conversation_id = :c AND cp.user_id <> :u');
        $stp->execute([':c' => $id, ':u' => $uid]);
        $other = $stp->fetch(\PDO::FETCH_ASSOC) ?: null;
        if ($other) {
            $me = Auth::user();
            if (($me['role'] ?? '') !== 'recruiter' && (($other['role'] ?? '') === 'recruiter')) {
                // I'm student/alumni replying to a recruiter; ensure recruiter initiated and not blocked
                if (!\Nexus\Helpers\Messaging::hasRecruiterInitiated($this->config, (int)$other['user_id'], $uid)
                    || \Nexus\Helpers\Messaging::isRecruiterRepliesBlocked($this->config, (int)$other['user_id'], $uid)) {
                    http_response_code(403);
                    if ($isAjax) { header('Content-Type: application/json'); return json_encode(['ok' => false, 'error' => 'Messaging disabled by recruiter.']); }
                    echo 'Messaging disabled by recruiter.'; return '';
                }
            }
        }
        // Accept text and attachments
        $text = trim((string)($_POST['message_text'] ?? ''));
        $hasFiles = !empty($_FILES['files']['name']) || !empty($_FILES['images']['name']);
        if ($text === '' && !$hasFiles) { $this->redirect('/messages/' . $id); return ''; }
        $mid = Message::send($this->config, $id, $uid, $text, 'text', null);
        // Create attachments if provided
        try {
            if (!empty($_FILES['files']['name']) || !empty($_FILES['images']['name'])) {
                $this->handleUploads($id, $mid);
            }
        } catch (\Throwable $e) { /* swallow upload issues for now */ }
        Conversation::touch($this->config, $id);
        // Do not create notifications for chat messages (real-time UX handles delivery)
        // If this was an AJAX fetch (Accept: application/json or X-Requested-With), return JSON
        if ($isAjax) {
            $msg = Message::findById($this->config, $mid);
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'message' => $msg]);
        }
        $this->redirect('/messages/' . $id);
        return '';
    }

    private function handleUploads(int $conversationId, int $messageId): void
    {
        $maxBytes = 25 * 1024 * 1024; // 25 MB per file limit
        // Resolve the public web root with priority to the executing script's directory (actual served public path)
        $script = (string)($_SERVER['SCRIPT_FILENAME'] ?? '');
        $base = rtrim(str_replace('\\','/', $script ? dirname($script) : ''), '/');
        if ($base === '' || !is_dir($base)) {
            // Fallback to DOCUMENT_ROOT
            $base = rtrim(str_replace('\\','/', (string)($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        }
        if ($base === '' || !is_dir($base)) {
            // Final fallback to the project's public directory
            $projectPublic = str_replace('\\','/', realpath(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public') ?: '');
            $base = $projectPublic;
        }
        $sub = '/uploads/conversations/' . $conversationId . '/' . date('Y') . '/' . date('m');
        $targetDir = $base . $sub;
        if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);
        $save = function(array $file) use ($sub, $targetDir, $messageId, $maxBytes) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return;
            if (($file['size'] ?? 0) > $maxBytes) return; // skip oversized files silently
            $name = basename($file['name'] ?? 'file');
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $safe = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $name);
            $destName = uniqid('att_') . ($ext ? ('.' . $ext) : '');
            $destPath = $targetDir . '/' . $destName;
            $tmp = $file['tmp_name'] ?? '';
            $moved = false;
            if ($tmp && @is_uploaded_file($tmp)) {
                $moved = @move_uploaded_file($tmp, $destPath);
            }
            if (!$moved && $tmp) {
                // Fallback: try a regular copy (useful in some dev servers)
                $moved = @copy($tmp, $destPath);
                if ($moved) { @unlink($tmp); }
            }
            if (!$moved) return;
            $url = $sub . '/' . $destName;
            $mime = mime_content_type($destPath) ?: ($file['type'] ?? 'application/octet-stream');
            $size = (int)filesize($destPath);
            \Nexus\Models\Message::addAttachment($this->config, $messageId, $safe, $url, $mime, $size);
        };
        $collect = function(string $key): array {
            $arr = [];
            if (empty($_FILES[$key])) return $arr;
            $F = $_FILES[$key];
            if (is_array($F['name'])) {
                for ($i=0; $i < count($F['name']); $i++) {
                    $arr[] = ['name'=>$F['name'][$i],'type'=>$F['type'][$i],'tmp_name'=>$F['tmp_name'][$i],'error'=>$F['error'][$i],'size'=>$F['size'][$i]];
                }
            } else {
                $arr[] = $F;
            }
            return $arr;
        };
        foreach (array_merge($collect('files'), $collect('images')) as $f) { $save($f); }
    }

    // Recruiter-only: toggle a specific user’s ability to reply to the recruiter
    public function toggleRecruiterReplies(): string
    {
        Auth::enforceAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) { http_response_code(419); return 'CSRF'; }
        $me = Auth::user();
        if (($me['role'] ?? '') !== 'recruiter') { http_response_code(403); return ''; }
        $target = (int)($_POST['user_id'] ?? 0);
        $blocked = (int)($_POST['blocked'] ?? 0) === 1;
        if ($target <= 0) { http_response_code(400); return 'Bad Request'; }
        \Nexus\Helpers\Messaging::setRecruiterRepliesBlocked($this->config, (int)$me['user_id'], $target, $blocked, (int)$me['user_id']);
        $ret = (string)($_POST['return_to'] ?? '/messages');
        // Use our redirect helper which prefixes base path
        $this->redirect($ret);
        return '';
    }

    // Basic polling endpoint for live chat
    public function poll(int $id): string
    {
        Auth::enforceAuth();
        $uid = (int)Auth::id();
        if (!Conversation::userIsParticipant($this->config, $id, $uid)) { http_response_code(403); return ''; }
        $since = (string)($_GET['since'] ?? '');
        $pdo = Database::pdo($this->config);
    $sql = 'SELECT m.*, u.email AS sender_email, up.first_name AS sender_first_name, up.last_name AS sender_last_name, up.profile_picture_url AS sender_picture_url FROM messages m JOIN users u ON u.user_id = m.sender_id LEFT JOIN user_profiles up ON up.user_id = u.user_id WHERE m.conversation_id = :c';
        $params = [':c' => $id];
        if ($since !== '') {
            $sql .= ' AND m.created_at > :since';
            $params[':since'] = $since;
        }
    $sql .= ' ORDER BY m.created_at ASC LIMIT 200';
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $rows = \Nexus\Models\Message::attachAttachments($this->config, $rows);
        $rows = \Nexus\Models\Message::attachReportFlags($this->config, $rows);
        // Mark as read for messages from others that were returned
        Message::markReadForUser($this->config, $id, $uid, $since !== '' ? $since : null);
        header('Content-Type: application/json');
        return json_encode($rows);
    }

    // POST: set typing; GET: check who is typing
    public function typing(int $id): string
    {
        Auth::enforceAuth();
        $uid = (int)Auth::id();
        if (!Conversation::userIsParticipant($this->config, $id, $uid)) { http_response_code(403); return ''; }
        $pdo = Database::pdo($this->config);
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                $pdo->prepare('REPLACE INTO typing_activity (conversation_id, user_id, last_typed_at) VALUES (:c, :u, CURRENT_TIMESTAMP)')->execute([':c' => $id, ':u' => $uid]);
            } catch (\Throwable $e) {
                // If table is missing in local legacy schema, ignore typing updates
            }
            return '';
        }
        // GET: return users typing within last 5s (excluding self)
        try {
            $st = $pdo->prepare('SELECT t.user_id, up.first_name, up.last_name FROM typing_activity t
                             JOIN users u ON u.user_id = t.user_id
                             LEFT JOIN user_profiles up ON up.user_id = u.user_id
                             WHERE t.conversation_id = :c AND t.user_id <> :u AND t.last_typed_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)');
            $st->execute([':c' => $id, ':u' => $uid]);
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }
        header('Content-Type: application/json');
        return json_encode($rows);
    }

    // Server-Sent Events stream for near real-time messages
    public function stream(int $id): string
    {
        Auth::enforceAuth();
        $uid = (int)Auth::id();
        if (!Conversation::userIsParticipant($this->config, $id, $uid)) { http_response_code(403); return ''; }
        // Headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        @ob_end_flush();
        @ob_implicit_flush(true);
        $pdo = Database::pdo($this->config);
        $last = (string)($_GET['since'] ?? '');
        $ticks = 0;
        while ($ticks < 60) { // ~1 minute; client will reconnect
            // First do a cheap check for new message id/timestamp to avoid heavy joins
            $chkSql = 'SELECT MAX(created_at) AS lc FROM messages WHERE conversation_id = :c';
            $chk = $pdo->prepare($chkSql);
            $chk->execute([':c' => $id]);
            $lc = (string)($chk->fetchColumn() ?: '');
            if ($lc !== '' && ($last === '' || $lc > $last)) {
                // Fetch new messages with joins only when there is something new
                $sql = 'SELECT m.*, u.email AS sender_email, up.first_name AS sender_first_name, up.last_name AS sender_last_name, up.profile_picture_url AS sender_picture_url '
                    . 'FROM messages m JOIN users u ON u.user_id = m.sender_id '
                    . 'LEFT JOIN user_profiles up ON up.user_id = u.user_id '
                    . 'WHERE m.conversation_id = :c';
                $params = [':c' => $id];
                if ($last !== '') { $sql .= ' AND m.created_at > :since'; $params[':since'] = $last; }
                $sql .= ' ORDER BY m.created_at ASC LIMIT 200';
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                $rows = \Nexus\Models\Message::attachAttachments($this->config, $rows);
                $rows = \Nexus\Models\Message::attachReportFlags($this->config, $rows);
                if (!empty($rows)) {
                    Message::markReadForUser($this->config, $id, $uid, $last !== '' ? $last : null);
                    $last = end($rows)['created_at'] ?? $lc;
                    echo 'data: ' . json_encode($rows) . "\n\n";
                    flush();
                }
            } else {
                echo "event: ping\n"; echo "data: {}\n\n"; flush();
            }
            usleep(500000); // 0.5s
            $ticks++;
        }
        return '';
    }

    // Paginated message history for infinite scroll
    public function history(int $id): string
    {
        Auth::enforceAuth();
        $uid = (int)Auth::id();
        if (!Conversation::userIsParticipant($this->config, $id, $uid)) { http_response_code(403); return ''; }
        $before = (string)($_GET['before'] ?? '');
        $limit = (int)($_GET['limit'] ?? 50);
        $limit = max(1, min(200, $limit));
        $pdo = Database::pdo($this->config);
    $sql = 'SELECT m.*, u.email AS sender_email, up.first_name AS sender_first_name, up.last_name AS sender_last_name, up.profile_picture_url AS sender_picture_url FROM messages m JOIN users u ON u.user_id = m.sender_id LEFT JOIN user_profiles up ON up.user_id = u.user_id WHERE m.conversation_id = :c';
        $params = [':c' => $id];
        if ($before !== '') {
            $sql .= ' AND m.created_at < :before';
            $params[':before'] = $before;
        }
        $sql .= ' ORDER BY m.created_at DESC LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute($params);
    $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    $rows = \Nexus\Models\Message::attachAttachments($this->config, $rows);
    $rows = \Nexus\Models\Message::attachReportFlags($this->config, $rows);
        header('Content-Type: application/json');
        return json_encode($rows);
    }

    // Delete a single message (only by sender, only if not reported); do not delete the conversation
    public function delete(int $id, int $messageId): string
    {
        Auth::enforceAuth();
        $uid = (int)Auth::id();
        if (!Conversation::userIsParticipant($this->config, $id, $uid)) { http_response_code(403); return 'Forbidden'; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return 'Method Not Allowed'; }
        if (!\Nexus\Helpers\Csrf::check($_POST['_token'] ?? null)) { http_response_code(419); return 'CSRF'; }
        $pdo = Database::pdo($this->config);
        // Ensure message belongs to this conversation and to the user
        $st = $pdo->prepare('SELECT m.message_id, m.sender_id FROM messages m WHERE m.message_id = :m AND m.conversation_id = :c');
        $st->execute([':m' => $messageId, ':c' => $id]);
        $msg = $st->fetch(\PDO::FETCH_ASSOC) ?: null;
        if (!$msg || (int)$msg['sender_id'] !== $uid) { http_response_code(403); return 'Forbidden'; }
        // Prevent delete if reported
        $chk = $pdo->prepare("SELECT 1 FROM reports WHERE target_type='message' AND target_id = :m LIMIT 1");
        $chk->execute([':m' => $messageId]);
        if ($chk->fetchColumn()) { http_response_code(409); return 'Cannot delete a reported message.'; }
        // Soft-delete: remove text and attachments but keep the message row
        // Delete attachments files and rows
        $at = $pdo->prepare('SELECT attachment_id, file_url FROM message_attachments WHERE message_id = :m');
        $at->execute([':m' => $messageId]);
        $atts = $at->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        foreach ($atts as $a) {
            $url = (string)($a['file_url'] ?? '');
            $base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_FILENAME'] ?? '')), '/');
            if ($url !== '') {
                $path = $base . (str_starts_with($url,'/') ? $url : ('/' . $url));
                if (is_file($path)) { @unlink($path); }
            }
        }
        $pdo->prepare('DELETE FROM message_attachments WHERE message_id = :m')->execute([':m' => $messageId]);
        $pdo->prepare('UPDATE messages SET message_text = "", message_type = "deleted" WHERE message_id = :m')->execute([':m' => $messageId]);
        // Mark reads remain for analytics; conversation not deleted
        if ((string)($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') {
            header('Content-Type: application/json');
            return json_encode(['ok' => true]);
        }
        $this->redirect('/messages/' . $id);
        return '';
    }
}
