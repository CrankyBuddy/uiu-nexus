<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Conversation
{
    public static function create(Config $config, int $creatorId, string $type = 'direct', ?string $title = null): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO conversations (created_by, title, conversation_type) VALUES (:c,:t,:ty)');
        $stmt->execute([':c' => $creatorId, ':t' => $title, ':ty' => $type]);
        return (int)$pdo->lastInsertId();
    }

    public static function addParticipant(Config $config, int $conversationId, int $userId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT IGNORE INTO conversation_participants (conversation_id, user_id) VALUES (:cid,:uid)');
        return $stmt->execute([':cid' => $conversationId, ':uid' => $userId]);
    }

    public static function listForUser(Config $config, int $userId, int $limit = 50, int $page = 1, ?string $q = null): array
    {
        $pdo = Database::pdo($config);
        $limit = max(1, (int)$limit);
        $page = max(1, (int)$page);
        $offset = ($page - 1) * $limit;
        $whereSearch = '';
        // Use distinct parameter names for native prepared statements (MySQL does not allow reusing the same named placeholder)
        $params = [
            ':u_me' => $userId,
            ':u_me2' => $userId,
            ':u_name1' => $userId,
            ':u_name2' => $userId,
            ':u_email1' => $userId,
        ];
        if ($q) {
            $whereSearch = ' AND (
                c.title LIKE :q
                OR (SELECT u2.email FROM conversation_participants cp_e JOIN users u2 ON u2.user_id = cp_e.user_id WHERE cp_e.conversation_id = c.conversation_id AND cp_e.user_id <> :u_email2 LIMIT 1) LIKE :q
                OR (SELECT up_first.first_name FROM conversation_participants cp_f JOIN user_profiles up_first ON up_first.user_id = cp_f.user_id WHERE cp_f.conversation_id = c.conversation_id AND cp_f.user_id <> :u_name3 LIMIT 1) LIKE :q
                OR (SELECT up_last.last_name FROM conversation_participants cp_l JOIN user_profiles up_last ON up_last.user_id = cp_l.user_id WHERE cp_l.conversation_id = c.conversation_id AND cp_l.user_id <> :u_name4 LIMIT 1) LIKE :q
            )';
            $params[':q'] = '%' . $q . '%';
            $params[':u_email2'] = $userId;
            $params[':u_name3'] = $userId;
            $params[':u_name4'] = $userId;
        }
        // Direct-only conversations the user participates in, with at least one message; avoid duplicates by using correlated subqueries
                $sql = "SELECT c.conversation_id, c.title, c.conversation_type, c.updated_at,
                       CASE WHEN c.conversation_type = 'direct'
                            THEN COALESCE(
                                NULLIF(
                                  CONCAT(
                                                                        TRIM(COALESCE((SELECT upn.first_name FROM conversation_participants cpn JOIN user_profiles upn ON upn.user_id = cpn.user_id WHERE cpn.conversation_id = c.conversation_id AND cpn.user_id <> :u_name1 LIMIT 1),'')),
                                    ' ',
                                                                        TRIM(COALESCE((SELECT upm.last_name FROM conversation_participants cpm JOIN user_profiles upm ON upm.user_id = cpm.user_id WHERE cpm.conversation_id = c.conversation_id AND cpm.user_id <> :u_name2 LIMIT 1),''))
                                  ), ' '
                                ),
                                                                (SELECT u2.email FROM conversation_participants cp2 JOIN users u2 ON u2.user_id = cp2.user_id WHERE cp2.conversation_id = c.conversation_id AND cp2.user_id <> :u_email1 LIMIT 1),
                                'Direct Message'
                            )
                            ELSE COALESCE(c.title, 'Group') END AS display_title,
                       CASE WHEN c.conversation_type = 'direct'
                                                        THEN (SELECT up2.profile_picture_url FROM conversation_participants cp2 JOIN user_profiles up2 ON up2.user_id = cp2.user_id WHERE cp2.conversation_id = c.conversation_id AND cp2.user_id <> :u_me2 LIMIT 1)
                            ELSE NULL END AS avatar_url
                FROM conversation_participants cp
                INNER JOIN conversations c ON c.conversation_id = cp.conversation_id
                WHERE cp.user_id = :u_me
                  AND c.conversation_type = 'direct'
                  AND EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id = c.conversation_id)
                  $whereSearch
                ORDER BY c.updated_at DESC, c.conversation_id DESC
                LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$rows) return [];
        // Batch fetch last_message and unread_count for this page of conversations
        $convIds = array_map(static fn($r) => (int)$r['conversation_id'], $rows);
        // Build IN clause safely
        $inPlaceholders = [];
        $inParams = [];
        foreach ($convIds as $idx => $cid) { $ph = ':c' . $idx; $inPlaceholders[] = $ph; $inParams[$ph] = $cid; }
        // Last message per conversation using group-wise max join (uses idx_messages_conversation_created)
        $sqlLast = 'SELECT m.conversation_id, m.message_text
                    FROM messages m
                    JOIN (
                        SELECT conversation_id, MAX(created_at) AS mx
                        FROM messages
                        WHERE conversation_id IN (' . implode(',', $inPlaceholders) . ')
                        GROUP BY conversation_id
                    ) t ON t.conversation_id = m.conversation_id AND t.mx = m.created_at';
        $stLast = $pdo->prepare($sqlLast);
        $stLast->execute($inParams);
        $lastMap = [];
        foreach ($stLast->fetchAll(PDO::FETCH_ASSOC) ?: [] as $lm) { $lastMap[(int)$lm['conversation_id']] = (string)$lm['message_text']; }
        // Unread counts per conversation for this user
        $sqlUnread = 'SELECT conversation_id, COUNT(*) AS unread
                      FROM messages
                      WHERE conversation_id IN (' . implode(',', $inPlaceholders) . ') AND sender_id <> :u AND is_read = 0
                      GROUP BY conversation_id';
        $stUnread = $pdo->prepare($sqlUnread);
        $stUnread->execute(array_merge($inParams, [':u' => $userId]));
        $unreadMap = [];
        foreach ($stUnread->fetchAll(PDO::FETCH_ASSOC) ?: [] as $ur) { $unreadMap[(int)$ur['conversation_id']] = (int)$ur['unread']; }
        // Merge into rows
        foreach ($rows as &$r) {
            $cid = (int)$r['conversation_id'];
            $r['last_message'] = $lastMap[$cid] ?? null;
            $r['unread_count'] = $unreadMap[$cid] ?? 0;
        }
        unset($r);
        return $rows;
    }

    public static function find(Config $config, int $conversationId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM conversations WHERE conversation_id = :id');
        $stmt->execute([':id' => $conversationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function userIsParticipant(Config $config, int $conversationId, int $userId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT 1 FROM conversation_participants WHERE conversation_id = :c AND user_id = :u LIMIT 1');
        $stmt->execute([':c' => $conversationId, ':u' => $userId]);
        return (bool)$stmt->fetch(PDO::FETCH_NUM);
    }

    public static function findDirectBetween(Config $config, int $userA, int $userB): ?int
    {
        $pdo = Database::pdo($config);
    $sql = 'SELECT c.conversation_id
        FROM conversations c
        JOIN conversation_participants p1 ON p1.conversation_id = c.conversation_id AND p1.user_id = :a
        JOIN conversation_participants p2 ON p2.conversation_id = c.conversation_id AND p2.user_id = :b
        WHERE c.conversation_type = \'direct\'
        ORDER BY c.updated_at DESC LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':a' => $userA, ':b' => $userB]);
        $cid = $stmt->fetchColumn();
        return $cid ? (int)$cid : null;
    }

    public static function touch(Config $config, int $conversationId): void
    {
        $pdo = Database::pdo($config);
        $pdo->prepare('UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE conversation_id = :id')->execute([':id' => $conversationId]);
    }

    public static function participants(Config $config, int $conversationId): array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT u.user_id, u.email, u.role, up.first_name, up.last_name
                FROM conversation_participants cp
                JOIN users u ON u.user_id = cp.user_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE cp.conversation_id = :c
                ORDER BY up.first_name, up.last_name, u.email';
        $st = $pdo->prepare($sql);
        $st->execute([':c' => $conversationId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function displayTitleForUser(Config $config, array $conversation, int $currentUserId): string
    {
        if (($conversation['conversation_type'] ?? '') !== 'direct') {
            return (string)($conversation['title'] ?? 'Group');
        }
        $parts = self::participants($config, (int)$conversation['conversation_id']);
        foreach ($parts as $p) {
            if ((int)$p['user_id'] !== $currentUserId) {
                $name = trim(((string)($p['first_name'] ?? '')) . ' ' . ((string)($p['last_name'] ?? '')));
                return $name !== '' ? $name : (string)$p['email'];
            }
        }
        return 'Direct Message';
    }
}
