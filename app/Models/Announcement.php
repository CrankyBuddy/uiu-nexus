<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Announcement
{
    public static function create(
        Config $config,
        string $title,
        string $content,
        int $authorId,
        array $targetRoles,
        bool $publish,
        ?string $publishAt = null,
        ?string $expiresAt = null
    ): int {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO announcements (title, content, author_id, target_roles, is_published, publish_at, expires_at) VALUES (:t,:c,:a,:tr,:p,:pa,:ea)');
        $stmt->execute([
            ':t' => $title,
            ':c' => $content,
            ':a' => $authorId,
            ':tr' => json_encode(array_values($targetRoles), JSON_THROW_ON_ERROR),
            ':p' => $publish ? 1 : 0,
            ':pa' => $publishAt,
            ':ea' => $expiresAt,
        ]);
        $annId = (int)$pdo->lastInsertId();
        // companion roles table
        if (!empty($targetRoles)) {
            $ins = $pdo->prepare('INSERT IGNORE INTO announcement_target_roles (announcement_id, role) VALUES (:id, :r)');
            foreach ($targetRoles as $role) {
                $ins->execute([':id' => $annId, ':r' => (string)$role]);
            }
        }
        return $annId;
    }

    public static function listPublished(Config $config, ?string $roleFilter = null, int $limit = 20): array
    {
        $pdo = Database::pdo($config);
        $limit = max(1, (int)$limit);
        if ($roleFilter) {
            $sql = "SELECT a.* FROM announcements a INNER JOIN announcement_target_roles atr ON atr.announcement_id = a.announcement_id AND atr.role = :r WHERE a.is_published = 1 AND (a.publish_at IS NULL OR a.publish_at <= NOW()) AND (a.expires_at IS NULL OR a.expires_at >= NOW()) ORDER BY a.created_at DESC LIMIT $limit";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':r' => $roleFilter]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        $sql = "SELECT * FROM announcements WHERE is_published = 1 AND (publish_at IS NULL OR publish_at <= NOW()) AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY created_at DESC LIMIT $limit";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(Config $config, int $id): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM announcements WHERE announcement_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
