<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class UserBadge
{
    public static function has(Config $config, int $userId, int $badgeId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT 1 FROM user_badges WHERE user_id = :u AND badge_id = :b LIMIT 1');
        $stmt->execute([':u' => $userId, ':b' => $badgeId]);
        return (bool)$stmt->fetch(PDO::FETCH_NUM);
    }

    public static function award(Config $config, int $userId, int $badgeId, ?string $entityType = null, ?int $entityId = null): bool
    {
        $pdo = Database::pdo($config);
        // Unique constraint on (user_id, badge_id) will prevent duplicates
        $stmt = $pdo->prepare('INSERT IGNORE INTO user_badges (user_id, badge_id, awarded_for_entity_type, awarded_for_entity_id) VALUES (:u, :b, :t, :i)');
        $ok = $stmt->execute([':u' => $userId, ':b' => $badgeId, ':t' => $entityType, ':i' => $entityId]);
        if (!$ok) { return false; }
        // INSERT IGNORE returns rowCount() = 1 if inserted, 0 if ignored (already had it)
        return $stmt->rowCount() === 1;
    }

    public static function listByUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT ub.*, b.badge_name, b.description FROM user_badges ub JOIN badges b ON b.badge_id = ub.badge_id WHERE ub.user_id = :u ORDER BY ub.awarded_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':u' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
