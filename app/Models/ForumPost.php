<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use Nexus\Helpers\Schema;
use PDO;

final class ForumPost
{
    /**
     * Legacy no-op: moderation schema is managed via SQL migrations, not at runtime.
     * Kept for backward compatibility with older code paths that attempted runtime DDL.
     */
    private static function ensureModerationSchema(Config $config): void
    {
        // Intentionally left blank. The schema (columns like moderation_status, approved_by, etc.)
        // is provisioned in the migration files under docs/ and should already exist.
        // Do not perform runtime DDL here.
    }

    public static function createQuestion(Config $config, int $authorId, int $categoryId, string $title, string $content): int
    {
        $pdo = Database::pdo($config);
    $stmt = $pdo->prepare('INSERT INTO forum_posts (author_id, category_id, title, content, post_type, is_approved, moderation_status) VALUES (?,?,?,?,\'question\',0,\'pending\')');
        $stmt->execute([$authorId, $categoryId, $title, $content]);
        return (int)$pdo->lastInsertId();
    }

    public static function createAnswer(Config $config, int $authorId, int $categoryId, int $parentPostId, string $content): int
    {
        $pdo = Database::pdo($config);
        // Answers/comments should not require approval; mark approved immediately
    $stmt = $pdo->prepare('INSERT INTO forum_posts (author_id, category_id, parent_post_id, content, post_type, is_approved, moderation_status) VALUES (?,?,?,?,\'answer\',1,NULL)');
        $stmt->execute([$authorId, $categoryId, $parentPostId, $content]);
        return (int)$pdo->lastInsertId();
    }

    public static function find(Config $config, int $postId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM forum_posts WHERE post_id = ?');
        $stmt->execute([$postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findWithAuthor(Config $config, int $postId): ?array
    {
        $pdo = Database::pdo($config);
    $stmt = $pdo->prepare("SELECT p.*, COALESCE(NULLIF(CONCAT_WS(' ', up.first_name, up.last_name), ''), u.email) AS author_name, u.email AS author_email, u.role AS author_role FROM forum_posts p INNER JOIN users u ON u.user_id = p.author_id LEFT JOIN user_profiles up ON up.user_id = u.user_id WHERE p.post_id = ?");
        $stmt->execute([$postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function incrementView(Config $config, int $postId): void
    {
        $pdo = Database::pdo($config);
        $pdo->prepare('UPDATE forum_posts SET view_count = view_count + 1 WHERE post_id = ?')->execute([$postId]);
    }

    public static function listRecent(Config $config, int $limit = 10): array
    {
        $pdo = Database::pdo($config);
        $limit = max(1, (int)$limit);
        $sql = "SELECT p.*, c.category_name,
            COALESCE(NULLIF(CONCAT_WS(' ', up.first_name, up.last_name), ''), u.email) AS author_name,
            u.email AS author_email
            FROM forum_posts p
            INNER JOIN forum_categories c ON c.category_id = p.category_id
            INNER JOIN users u ON u.user_id = p.author_id
            LEFT JOIN user_profiles up ON up.user_id = u.user_id
            WHERE p.post_type = 'question' AND ((p.moderation_status = 'approved') OR (p.moderation_status IS NULL AND p.is_approved = 1))
            ORDER BY p.created_at DESC
            LIMIT $limit";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function listByCategory(Config $config, int $categoryId, int $limit = 20, int $offset = 0): array
    {
        $pdo = Database::pdo($config);
        $cid = (int)$categoryId;
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        $sql = "SELECT p.*,
            COALESCE(NULLIF(CONCAT_WS(' ', up.first_name, up.last_name), ''), u.email) AS author_name,
            u.email AS author_email
            FROM forum_posts p
            INNER JOIN users u ON u.user_id = p.author_id
            LEFT JOIN user_profiles up ON up.user_id = u.user_id
            WHERE p.category_id = $cid AND p.post_type = 'question' AND ((p.moderation_status = 'approved') OR (p.moderation_status IS NULL AND p.is_approved = 1))
            ORDER BY p.is_pinned DESC, p.created_at DESC
            LIMIT $limit OFFSET $offset";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function listAnswers(Config $config, int $postId): array
    {
        $pdo = Database::pdo($config);
    $stmt = $pdo->prepare("SELECT a.*, COALESCE(NULLIF(CONCAT_WS(' ', up.first_name, up.last_name), ''), u.email) AS author_name, u.email AS author_email FROM forum_posts a INNER JOIN users u ON u.user_id = a.author_id LEFT JOIN user_profiles up ON up.user_id = u.user_id WHERE a.parent_post_id = ? AND a.post_type = 'answer' ORDER BY a.is_best_answer DESC, a.created_at ASC");
        $stmt->execute([$postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function markBestAnswer(Config $config, int $answerId): void
    {
        $pdo = Database::pdo($config);
        // Find parent question of this answer
        $getParent = $pdo->prepare('SELECT parent_post_id FROM forum_posts WHERE post_id = ? AND post_type = \'answer\'');
        $getParent->execute([$answerId]);
        $parentId = (int)($getParent->fetchColumn() ?: 0);
        if ($parentId > 0) {
            // Unset previous best answers for the same question
            $pdo->prepare("UPDATE forum_posts SET is_best_answer = 0 WHERE parent_post_id = :pid AND post_type = 'answer'")->execute([':pid' => $parentId]);
        }
        $stmt = $pdo->prepare("UPDATE forum_posts SET is_best_answer = 1 WHERE post_id = ? AND post_type = 'answer'");
        $stmt->execute([$answerId]);
    }

    public static function recountVotes(Config $config, int $postId): void
    {
        $pdo = Database::pdo($config);
        $up = $pdo->prepare("SELECT COUNT(*) FROM post_votes WHERE post_id = :p AND vote_type = 'upvote'");
        $down = $pdo->prepare("SELECT COUNT(*) FROM post_votes WHERE post_id = :p AND vote_type = 'downvote'");
        $up->execute([':p' => $postId]);
        $down->execute([':p' => $postId]);
        $pdo->prepare('UPDATE forum_posts SET upvote_count = :u, downvote_count = :d WHERE post_id = :p')
            ->execute([':u' => (int)$up->fetchColumn(), ':d' => (int)$down->fetchColumn(), ':p' => $postId]);
    }

    public static function countPending(Config $config, ?int $userId = null): int
    {
        $pdo = Database::pdo($config);
        // Only questions participate in moderation workflow
        if ($userId) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM forum_posts WHERE post_type = 'question' AND (moderation_status = 'pending' OR (moderation_status IS NULL AND is_approved = 0)) AND author_id = :u");
            $st->execute([':u' => $userId]);
            return (int)($st->fetchColumn() ?: 0);
        } else {
            return (int)($pdo->query("SELECT COUNT(*) FROM forum_posts WHERE post_type = 'question' AND (moderation_status = 'pending' OR (moderation_status IS NULL AND is_approved = 0))")->fetchColumn() ?: 0);
        }
    }

    public static function listPending(Config $config, ?int $userId = null, int $limit = 50, int $offset = 0): array
    {
        $pdo = Database::pdo($config);
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        if ($userId) {
            $sql = "SELECT p.*, c.category_name,
                COALESCE(NULLIF(CONCAT_WS(' ', up.first_name, up.last_name), ''), u.email) AS author_name,
                u.email AS author_email
                FROM forum_posts p
                INNER JOIN forum_categories c ON c.category_id = p.category_id
                INNER JOIN users u ON u.user_id = p.author_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE p.post_type = 'question' AND (p.moderation_status = 'pending' OR (p.moderation_status IS NULL AND p.is_approved = 0)) AND p.author_id = :u
                ORDER BY p.created_at DESC
                LIMIT $limit OFFSET $offset";
            $st = $pdo->prepare($sql);
            $st->execute([':u' => $userId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } else {
            $sql = "SELECT p.*, c.category_name,
                COALESCE(NULLIF(CONCAT_WS(' ', up.first_name, up.last_name), ''), u.email) AS author_name,
                u.email AS author_email
                FROM forum_posts p
                INNER JOIN forum_categories c ON c.category_id = p.category_id
                INNER JOIN users u ON u.user_id = p.author_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE p.post_type = 'question' AND (p.moderation_status = 'pending' OR (p.moderation_status IS NULL AND p.is_approved = 0))
                ORDER BY p.created_at DESC
                LIMIT $limit OFFSET $offset";
            $st = $pdo->query($sql);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }

    public static function approve(Config $config, int $postId, int $adminUserId): void
    {
        self::ensureModerationSchema($config);
        $pdo = Database::pdo($config);
        // Only questions can be approved
        $st = $pdo->prepare("UPDATE forum_posts SET is_approved = 1, moderation_status = 'approved', approved_by = :a, approved_at = NOW() WHERE post_id = :p AND post_type = 'question'");
        $st->execute([':a' => $adminUserId, ':p' => $postId]);
    }

    public static function reject(Config $config, int $postId, int $adminUserId, ?string $reason = null): void
    {
        self::ensureModerationSchema($config);
        $pdo = Database::pdo($config);
        // Only questions can be rejected
        $st = $pdo->prepare("UPDATE forum_posts SET moderation_status = 'rejected', is_approved = 0, rejected_by = :a, rejected_at = NOW(), reject_reason = :r WHERE post_id = :p AND post_type = 'question'");
        $st->execute([':a' => $adminUserId, ':r' => ($reason !== '' ? $reason : null), ':p' => $postId]);
    }

    public static function countRejected(Config $config, ?int $userId = null): int
    {
        self::ensureModerationSchema($config);
        $pdo = Database::pdo($config);
        if ($userId) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM forum_posts WHERE post_type = 'question' AND moderation_status = 'rejected' AND author_id = :u");
            $st->execute([':u' => $userId]);
            return (int)($st->fetchColumn() ?: 0);
        } else {
            return (int)($pdo->query("SELECT COUNT(*) FROM forum_posts WHERE post_type = 'question' AND moderation_status = 'rejected'")->fetchColumn() ?: 0);
        }
    }

    public static function listRejected(Config $config, ?int $userId = null, int $limit = 50, int $offset = 0): array
    {
        self::ensureModerationSchema($config);
        $pdo = Database::pdo($config);
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        if ($userId) {
            $sql = "SELECT p.*, c.category_name,
                COALESCE(NULLIF(CONCAT_WS(' ', up.first_name, up.last_name), ''), u.email) AS author_name,
                u.email AS author_email
                FROM forum_posts p
                INNER JOIN forum_categories c ON c.category_id = p.category_id
                INNER JOIN users u ON u.user_id = p.author_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE p.post_type = 'question' AND p.moderation_status = 'rejected' AND p.author_id = :u
                ORDER BY p.rejected_at DESC, p.created_at DESC
                LIMIT $limit OFFSET $offset";
            $st = $pdo->prepare($sql);
            $st->execute([':u' => $userId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } else {
            $sql = "SELECT p.*, c.category_name,
                COALESCE(NULLIF(CONCAT_WS(' ', up.first_name, up.last_name), ''), u.email) AS author_name,
                u.email AS author_email
                FROM forum_posts p
                INNER JOIN forum_categories c ON c.category_id = p.category_id
                INNER JOIN users u ON u.user_id = p.author_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE p.post_type = 'question' AND p.moderation_status = 'rejected'
                ORDER BY p.rejected_at DESC, p.created_at DESC
                LIMIT $limit OFFSET $offset";
            $st = $pdo->query($sql);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }

    public static function isReported(Config $config, int $postId): bool
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare("SELECT 1 FROM reports WHERE target_type='post' AND target_id = :p LIMIT 1");
        $st->execute([':p' => $postId]);
        return (bool)$st->fetchColumn();
    }

    public static function delete(Config $config, int $postId): void
    {
        $pdo = Database::pdo($config);
        // delete votes tied to this post
        $pdo->prepare('DELETE FROM post_votes WHERE post_id = ?')->execute([$postId]);
        // delete answers under this question (and their votes)
        $ansIds = $pdo->prepare("SELECT post_id FROM forum_posts WHERE parent_post_id = ?");
        $ansIds->execute([$postId]);
        $ids = array_map('intval', array_column($ansIds->fetchAll(PDO::FETCH_ASSOC) ?: [], 'post_id'));
        if (!empty($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM post_votes WHERE post_id IN ($in)")->execute($ids);
            $pdo->prepare("DELETE FROM forum_posts WHERE post_id IN ($in)")->execute($ids);
        }
        // finally delete the post itself
        $pdo->prepare('DELETE FROM forum_posts WHERE post_id = ?')->execute([$postId]);
    }

    public static function countApproved(Config $config, ?int $userId = null): int
    {
        self::ensureModerationSchema($config);
        $pdo = Database::pdo($config);
        // Only questions are moderated/approved
        $cond = "post_type = 'question' AND (moderation_status = 'approved' OR (moderation_status IS NULL AND is_approved = 1))";
        if ($userId) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM forum_posts WHERE $cond AND author_id = :u");
            $st->execute([':u' => $userId]);
            return (int)($st->fetchColumn() ?: 0);
        }
        return (int)($pdo->query("SELECT COUNT(*) FROM forum_posts WHERE $cond")->fetchColumn() ?: 0);
    }

    public static function listApproved(Config $config, ?int $userId = null, int $limit = 50, int $offset = 0): array
    {
        self::ensureModerationSchema($config);
        $pdo = Database::pdo($config);
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        // Only questions are moderated/approved
        $cond = "p.post_type = 'question' AND (p.moderation_status = 'approved' OR (p.moderation_status IS NULL AND p.is_approved = 1))";
        if ($userId) {
            $sql = "SELECT p.*, c.category_name,
                COALESCE(NULLIF(CONCAT_WS(' ', up.first_name, up.last_name), ''), u.email) AS author_name,
                u.email AS author_email
                FROM forum_posts p
                INNER JOIN forum_categories c ON c.category_id = p.category_id
                INNER JOIN users u ON u.user_id = p.author_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE $cond AND p.author_id = :u
                ORDER BY p.created_at DESC
                LIMIT $limit OFFSET $offset";
            $st = $pdo->prepare($sql);
            $st->execute([':u' => $userId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } else {
            $sql = "SELECT p.*, c.category_name,
                COALESCE(NULLIF(CONCAT_WS(' ', up.first_name, up.last_name), ''), u.email) AS author_name,
                u.email AS author_email
                FROM forum_posts p
                INNER JOIN forum_categories c ON c.category_id = p.category_id
                INNER JOIN users u ON u.user_id = p.author_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE $cond
                ORDER BY p.created_at DESC
                LIMIT $limit OFFSET $offset";
            $st = $pdo->query($sql);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    }
}
