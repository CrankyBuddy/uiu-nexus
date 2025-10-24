<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use Nexus\Helpers\Schema;
use PDO;

final class Message
{
    /**
     * No-op: Schema is managed via SQL migrations. This method exists only for backward compatibility
     * with older code paths that attempted to create tables at runtime.
     */
    public static function ensureAttachmentsSchema(Config $config): void
    {
        // Intentionally empty. The message_attachments table is created by the DB schema.
    }

    /**
     * No-op: Schema is managed via SQL migrations. This method exists only for backward compatibility
     * with older code paths that attempted to create tables at runtime.
     */
    public static function ensureReadsSchema(Config $config): void
    {
        // Intentionally empty. The message_reads table is created by the DB schema.
    }

    public static function attachReportFlags(Config $config, array $rows): array
    {
        if (empty($rows)) return $rows;
        $ids = [];
        foreach ($rows as $r) { if (!empty($r['message_id'])) $ids[] = (int)$r['message_id']; }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) return $rows;
        $pdo = Database::pdo($config);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT target_id FROM reports WHERE target_type = 'message' AND target_id IN ($in)");
        $st->execute($ids);
        $rep = array_fill_keys(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []), true);
        foreach ($rows as &$r) {
            $mid = (int)($r['message_id'] ?? 0);
            $r['reported'] = isset($rep[$mid]);
        }
        return $rows;
    }
    public static function send(Config $config, int $conversationId, int $senderId, string $text, string $type = 'text', ?string $meetingLink = null): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO messages (conversation_id, sender_id, message_text, message_type, meeting_link) VALUES (:c,:s,:t,:ty,:ml)');
        $stmt->execute([':c' => $conversationId, ':s' => $senderId, ':t' => $text, ':ty' => $type, ':ml' => $meetingLink]);
        return (int)$pdo->lastInsertId();
    }

    public static function list(Config $config, int $conversationId, int $limit = 100): array
    {
        $pdo = Database::pdo($config);
        $limit = max(1, (int)$limit);
        $sql = "SELECT m.*, u.email AS sender_email, up.first_name AS sender_first_name, up.last_name AS sender_last_name, up.profile_picture_url AS sender_picture_url
                FROM messages m
                INNER JOIN users u ON u.user_id = m.sender_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE m.conversation_id = :c
                ORDER BY m.created_at DESC
                LIMIT $limit";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':c' => $conversationId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $rows = array_reverse($rows); // oldest first for display
    $rows = self::attachAttachments($config, $rows);
    $rows = self::attachReportFlags($config, $rows);
    return $rows;
    }

    public static function markReadForUser(Config $config, int $conversationId, int $userId, ?string $since = null): void
    {
        $pdo = Database::pdo($config);
        if ($since) {
            $sql = 'UPDATE messages SET is_read = 1 WHERE conversation_id = :c AND sender_id <> :u AND is_read = 0 AND created_at > :since';
            $pdo->prepare($sql)->execute([':c' => $conversationId, ':u' => $userId, ':since' => $since]);
        } else {
            $sql = 'UPDATE messages SET is_read = 1 WHERE conversation_id = :c AND sender_id <> :u AND is_read = 0';
            $pdo->prepare($sql)->execute([':c' => $conversationId, ':u' => $userId]);
        }
    // 3NF per-user read receipts (schema provided by DB v2)
        $sqlReads = $since
            ? 'INSERT IGNORE INTO message_reads (message_id, user_id, read_at)
               SELECT m.message_id, :u, NOW() FROM messages m
               WHERE m.conversation_id = :c AND m.sender_id <> :u AND m.created_at > :since'
            : 'INSERT IGNORE INTO message_reads (message_id, user_id, read_at)
               SELECT m.message_id, :u, NOW() FROM messages m
               WHERE m.conversation_id = :c AND m.sender_id <> :u';
        try {
            $st = $pdo->prepare($sqlReads);
            $params = [':c' => $conversationId, ':u' => $userId];
            if ($since) { $params[':since'] = $since; }
            $st->execute($params);
        } catch (\Throwable $e) { /* ignore */ }
    }

    // One-time backfill to make initial unread count 0 for existing messages
    public static function initializeReadsForUser(Config $config, int $userId): void
    {
        self::ensureReadsSchema($config);
        $pdo = Database::pdo($config);
        try {
            // If the user already has any read rows, assume initialized
            $chk = $pdo->prepare('SELECT 1 FROM message_reads WHERE user_id = :u LIMIT 1');
            $chk->execute([':u' => $userId]);
            if ($chk->fetch(PDO::FETCH_NUM)) return;
            // Backfill: mark all existing messages in user's conversations as read
            $sql = 'INSERT IGNORE INTO message_reads (message_id, user_id, read_at)
                    SELECT m.message_id, :u, NOW()
                    FROM messages m
                    JOIN conversation_participants cp ON cp.conversation_id = m.conversation_id AND cp.user_id = :u
                    WHERE m.sender_id <> :u';
            $pdo->prepare($sql)->execute([':u' => $userId]);
        } catch (\Throwable $e) {
            // Silently ignore; fallback is raw count which may include historic messages
        }
    }

    public static function findById(Config $config, int $messageId): ?array
    {
        $pdo = Database::pdo($config);
        $sql = "SELECT m.*, u.email AS sender_email, up.first_name AS sender_first_name, up.last_name AS sender_last_name, up.profile_picture_url AS sender_picture_url
                FROM messages m
                JOIN users u ON u.user_id = m.sender_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE m.message_id = :id LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([':id' => $messageId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
    $with = self::attachAttachments($config, [$row]);
    $with = self::attachReportFlags($config, $with);
        return $with[0] ?? $row;
    }

    // Attachment schema is managed by DB v2; these methods no longer create/alter tables.

    public static function addAttachment(Config $config, int $messageId, string $fileName, string $fileUrl, string $mimeType, int $size): void
    {
    // DB schema ensures table exists in v2
        $pdo = Database::pdo($config);
        $st = $pdo->prepare('INSERT INTO message_attachments (message_id, file_name, file_url, mime_type, file_size) VALUES (:m,:n,:u,:t,:s)');
        $st->execute([':m' => $messageId, ':n' => $fileName, ':u' => $fileUrl, ':t' => $mimeType, ':s' => $size]);
    }

    public static function fetchAttachmentsForMessages(Config $config, array $messageIds): array
    {
        if (empty($messageIds)) return [];
        self::ensureAttachmentsSchema($config);
        $pdo = Database::pdo($config);
        $in = implode(',', array_fill(0, count($messageIds), '?'));
        $st = $pdo->prepare("SELECT message_id, attachment_id, file_name, file_url, mime_type, file_size, created_at FROM message_attachments WHERE message_id IN ($in) ORDER BY attachment_id ASC");
        $st->execute(array_values($messageIds));
        $map = [];
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $mid = (int)$row['message_id'];
            if (!isset($map[$mid])) $map[$mid] = [];
            $map[$mid][] = $row;
        }
        return $map;
    }

    public static function attachAttachments(Config $config, array $rows): array
    {
        if (empty($rows)) return $rows;
        $ids = [];
        foreach ($rows as $r) { if (!empty($r['message_id'])) $ids[] = (int)$r['message_id']; }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) return $rows;
        $map = self::fetchAttachmentsForMessages($config, $ids);
        foreach ($rows as &$r) {
            $mid = (int)($r['message_id'] ?? 0);
            $r['attachments'] = $map[$mid] ?? [];
        }
        return $rows;
    }
}
