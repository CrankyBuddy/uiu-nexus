<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class AdminPermission
{
    public static function listByUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM admin_permissions WHERE user_id = :u ORDER BY permission_key');
        $stmt->execute([':u'=>$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function set(Config $config, int $userId, string $key, bool $allowed): void
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO admin_permissions (user_id, permission_key, allowed) VALUES (:u,:k,:a) ON DUPLICATE KEY UPDATE allowed = VALUES(allowed)');
        $stmt->execute([':u'=>$userId, ':k'=>$key, ':a'=>$allowed ? 1 : 0]);
    }

    public static function delete(Config $config, int $userId, string $key): void
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('DELETE FROM admin_permissions WHERE user_id = :u AND permission_key = :k');
        $stmt->execute([':u'=>$userId, ':k'=>$key]);
    }
}
