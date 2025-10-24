<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Core\Database;
use Nexus\Helpers\Auth;
use Nexus\Helpers\Csrf;
use Nexus\Helpers\Audit;
use Nexus\Helpers\Schema;
use Nexus\Models\Notification;
use PDO;

final class ReportsController extends Controller
{
    private const ALLOWED_TYPES = ['user','post','job','message','event'];

    public function create(): string
    {
        Auth::enforceAuth();
        $type = (string)($_GET['target_type'] ?? '');
        $id = (int)($_GET['target_id'] ?? 0);
        $error = null;
        if ($type !== '' && !in_array($type, self::ALLOWED_TYPES, true)) {
            $error = 'Invalid target type';
        }
        if ($id < 0) { $id = 0; }
        $preview = null;
        // If reporting a message, load a small preview (sender, time, text, attachments)
        if ($error === null && $type === 'message' && $id > 0) {
            try {
                $pdo = Database::pdo($this->config);
                $sql = 'SELECT m.*, u.email AS sender_email, up.first_name AS sender_first_name, up.last_name AS sender_last_name '
                    . 'FROM messages m JOIN users u ON u.user_id = m.sender_id '
                    . 'LEFT JOIN user_profiles up ON up.user_id = u.user_id WHERE m.message_id = :id';
                $st = $pdo->prepare($sql);
                $st->execute([':id' => $id]);
                $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                if ($row) {
                    // Fetch attachments
                    $at = $pdo->prepare('SELECT file_name, file_url, mime_type, file_size FROM message_attachments WHERE message_id = :m');
                    $at->execute([':m' => $id]);
                    $atts = $at->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    $row['attachments'] = $atts;
                    $preview = $row;
                }
            } catch (\Throwable $e) { /* ignore preview errors */ }
        }
        return $this->view('reports/create', [
            'target_type' => $type,
            'target_id' => $id,
            'error' => $error,
            'success' => null,
            'target_preview' => $preview,
        ]);
    }

    public function store(): string
    {
        Auth::enforceAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check($_POST['_token'] ?? null)) {
            http_response_code(400);
            echo 'Bad Request';
            return '';
        }
        $uid = (int)Auth::id();
        $type = (string)($_POST['target_type'] ?? '');
        $id = (int)($_POST['target_id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));
        if (!in_array($type, self::ALLOWED_TYPES, true) || $id <= 0 || $reason === '') {
            return $this->view('reports/create', [
                'target_type' => $type,
                'target_id' => $id,
                'error' => 'Please provide all required fields.',
                'success' => null,
            ]);
        }
        // Validate target exists
        $pdo = Database::pdo($this->config);
        $exists = false;
        if ($type === 'user') {
            $st = $pdo->prepare('SELECT 1 FROM users WHERE user_id = :id');
            $st->execute([':id' => $id]);
            $exists = (bool)$st->fetch(PDO::FETCH_NUM);
        } elseif ($type === 'post') {
            $st = $pdo->prepare('SELECT 1 FROM forum_posts WHERE post_id = :id');
            $st->execute([':id' => $id]);
            $exists = (bool)$st->fetch(PDO::FETCH_NUM);
        } elseif ($type === 'job') {
            $st = $pdo->prepare('SELECT 1 FROM job_listings WHERE job_id = :id');
            $st->execute([':id' => $id]);
            $exists = (bool)$st->fetch(PDO::FETCH_NUM);
        } elseif ($type === 'message') {
            $st = $pdo->prepare('SELECT 1 FROM messages WHERE message_id = :id');
            $st->execute([':id' => $id]);
            $exists = (bool)$st->fetch(PDO::FETCH_NUM);
        } elseif ($type === 'event') {
            $st = $pdo->prepare('SELECT 1 FROM events WHERE event_id = :id');
            $st->execute([':id' => $id]);
            $exists = (bool)$st->fetch(PDO::FETCH_NUM);
        }
        if (!$exists) {
            return $this->view('reports/create', [
                'target_type' => $type,
                'target_id' => $id,
                'error' => 'Target not found.',
                'success' => null,
            ]);
        }

        // Schema is authoritative in v2; legacy runtime ALTERs removed

        // Derive target author's id and email for supported types
        $targetAuthorId = null; $targetAuthorEmail = null;
        try {
            if ($type === 'post') {
                $stA = $pdo->prepare('SELECT p.author_id AS aid, u.email AS aemail FROM forum_posts p JOIN users u ON u.user_id = p.author_id WHERE p.post_id = :id');
                $stA->execute([':id' => $id]);
                $rowA = $stA->fetch(PDO::FETCH_ASSOC) ?: null;
                if ($rowA) { $targetAuthorId = (int)($rowA['aid'] ?? 0) ?: null; $targetAuthorEmail = (string)($rowA['aemail'] ?? ''); }
            } elseif ($type === 'message') {
                $stA = $pdo->prepare('SELECT m.sender_id AS aid, u.email AS aemail FROM messages m JOIN users u ON u.user_id = m.sender_id WHERE m.message_id = :id');
                $stA->execute([':id' => $id]);
                $rowA = $stA->fetch(PDO::FETCH_ASSOC) ?: null;
                if ($rowA) { $targetAuthorId = (int)($rowA['aid'] ?? 0) ?: null; $targetAuthorEmail = (string)($rowA['aemail'] ?? ''); }
            }
        } catch (\Throwable $e) { /* ignore lookup errors */ }

    // Create report (include target author metadata when available)
    $stmt = $pdo->prepare('INSERT INTO reports (reported_by, target_type, target_id, reason, status, target_author_id, target_author_email) VALUES (:by,:t,:id,:r,\'pending\', :taid, :taemail)');
    $stmt->execute([':by' => $uid, ':t' => $type, ':id' => $id, ':r' => $reason, ':taid' => $targetAuthorId, ':taemail' => $targetAuthorEmail]);
        $reportId = (int)$pdo->lastInsertId();

        // If user attached files, save them under /uploads/reports/{reportId} and add to evidence JSON
        $reporterFiles = [];
        if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name'] ?? null)) {
            $max = 25 * 1024 * 1024; // 25MB per file
            $allowed = [
                'image/jpeg','image/png','image/gif','image/webp','application/pdf',
                'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','text/plain'
            ];
            $publicRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
            $relDir = 'uploads' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . $reportId;
            $targetDir = $publicRoot . DIRECTORY_SEPARATOR . $relDir;
            if (!is_dir($targetDir)) { @mkdir($targetDir, 0775, true); }
            $names = $_FILES['attachments']['name'];
            $tmps = $_FILES['attachments']['tmp_name'];
            $sizes = $_FILES['attachments']['size'];
            $errs = $_FILES['attachments']['error'];
            $types = $_FILES['attachments']['type'];
            $count = is_array($names) ? count($names) : 0;
            for ($i = 0; $i < $count; $i++) {
                if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { continue; }
                $size = (int)($sizes[$i] ?? 0);
                if ($size <= 0 || $size > $max) { continue; }
                // determine mime
                $mime = '';
                try { if (class_exists('finfo')) { $f = new \finfo(FILEINFO_MIME_TYPE); $mime = (string)($f->file($tmps[$i]) ?: ''); } } catch (\Throwable $e) {}
                if ($mime === '' && isset($types[$i])) { $mime = (string)$types[$i]; }
                if (!in_array($mime, $allowed, true)) { continue; }
                $origName = (string)($names[$i] ?? 'file');
                $ext = pathinfo($origName, PATHINFO_EXTENSION) ?: 'bin';
                $safeName = 'user_' . bin2hex(random_bytes(6)) . '.' . strtolower(preg_replace('/[^A-Za-z0-9]+/', '', (string)$ext));
                $dest = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
                if (!@move_uploaded_file($tmps[$i], $dest)) { continue; }
                $reporterFiles[] = [
                    'file_name' => $origName,
                    'snapshot_url' => '/uploads/reports/' . $reportId . '/' . $safeName,
                    'mime_type' => $mime,
                    'size' => $size,
                    'uploaded_by' => $uid,
                    'uploaded_at' => date('c'),
                ];
            }
        }

        // Optionally capture evidence for messages: text + attachment snapshots
        $includeEvidence = ((string)($_POST['include_evidence'] ?? '1') === '1');
        if ($includeEvidence && $type === 'message' && $id > 0) {
            try {
                // Load message and attachments
                $sql = 'SELECT m.*, u.email AS sender_email, up.first_name AS sender_first_name, up.last_name AS sender_last_name '
                    . 'FROM messages m JOIN users u ON u.user_id = m.sender_id '
                    . 'LEFT JOIN user_profiles up ON up.user_id = u.user_id WHERE m.message_id = :id';
                $st = $pdo->prepare($sql);
                $st->execute([':id' => $id]);
                $msg = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                if ($msg) {
                    $at = $pdo->prepare('SELECT file_name, file_url, mime_type, file_size FROM message_attachments WHERE message_id = :m');
                    $at->execute([':m' => $id]);
                    $atts = $at->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    // Attempt to copy attachment files into a report-specific folder
                    $script = (string)($_SERVER['SCRIPT_FILENAME'] ?? '');
                    $base = rtrim(str_replace('\\','/', $script ? dirname($script) : ''), '/');
                    if ($base === '' || !is_dir($base)) {
                        $base = rtrim(str_replace('\\','/', (string)($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
                    }
                    if ($base === '' || !is_dir($base)) {
                        $projectPublic = str_replace('\\','/', realpath(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public') ?: '');
                        $base = $projectPublic;
                    }
                    $snapSub = '/uploads/reports/' . $reportId;
                    $snapDir = $base . $snapSub;
                    if (!is_dir($snapDir)) { @mkdir($snapDir, 0775, true); }
                    $snapAt = [];
                    foreach ($atts as $a) {
                        $origUrl = (string)($a['file_url'] ?? '');
                        $fname = basename((string)($a['file_name'] ?? 'attachment'));
                        $mime = (string)($a['mime_type'] ?? 'application/octet-stream');
                        $size = (int)($a['file_size'] ?? 0);
                        $snapshotUrl = null;
                        if ($origUrl !== '') {
                            $src = $base . (str_starts_with($origUrl, '/') ? $origUrl : ('/' . $origUrl));
                            if (is_file($src)) {
                                $destName = uniqid('rep_') . '_' . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $fname);
                                $dest = $snapDir . '/' . $destName;
                                if (@copy($src, $dest)) {
                                    $snapshotUrl = $snapSub . '/' . $destName;
                                }
                            }
                        }
                        $snapAt[] = [
                            'file_name' => $fname,
                            'mime_type' => $mime,
                            'size' => $size,
                            'original_url' => $origUrl,
                            'snapshot_url' => $snapshotUrl,
                        ];
                    }
                    $evidence = [
                        'type' => 'message',
                        'message' => [
                            'message_id' => (int)$msg['message_id'],
                            'conversation_id' => (int)$msg['conversation_id'],
                            'sender_id' => (int)$msg['sender_id'],
                            'sender_email' => (string)($msg['sender_email'] ?? ''),
                            'sender_first_name' => (string)($msg['sender_first_name'] ?? ''),
                            'sender_last_name' => (string)($msg['sender_last_name'] ?? ''),
                            'created_at' => (string)($msg['created_at'] ?? ''),
                            'text' => (string)($msg['message_text'] ?? ''),
                            'message_type' => (string)($msg['message_type'] ?? ''),
                        ],
                        'attachments' => $snapAt,
                    ];
                    // merge reporter files if any
                    if (!empty($reporterFiles)) {
                        $evidence['attachments_reporter'] = $reporterFiles;
                    }
                    $up = $pdo->prepare('UPDATE reports SET evidence = :e WHERE report_id = :id');
                    $up->execute([':e' => json_encode($evidence), ':id' => $reportId]);
                }
            } catch (\Throwable $e) { /* ignore evidence errors */ }
        }
        // If not a message or includeEvidence not requested, still persist reporter files into evidence JSON if present
        if ($type !== 'message' || !$includeEvidence) {
            if (!empty($reporterFiles)) {
                try {
                    $up = $pdo->prepare('UPDATE reports SET evidence = :e WHERE report_id = :id');
                    $up->execute([':e' => json_encode(['attachments_reporter' => $reporterFiles]), ':id' => $reportId]);
                } catch (\Throwable $e) {}
            }
        }

        // Notify admins
        $admins = $pdo->query("SELECT user_id FROM users WHERE role='admin' AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($admins as $aid) {
            Notification::send($this->config, (int)$aid, 'New Report', 'A new report was filed', 'report', 'report', $reportId, '/admin/reports/' . $reportId);
        }
        Audit::log($this->config, $uid, 'report.create', 'report', $reportId, null, ['target_type' => $type, 'target_id' => $id]);

        return $this->view('reports/create', [
            'target_type' => $type,
            'target_id' => $id,
            'error' => null,
            'success' => 'Thank you. Your report has been submitted.',
        ]);
    }
}
