<?php
declare(strict_types=1);

namespace Nexus\Helpers;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Gate
{
    public static function has(Config $config, int $userId, string $permissionKey): bool
    {
        $pdo = Database::pdo($config);

        // Direct user special permission within validity window
        $sqlUser = <<<SQL
            SELECT 1
            FROM user_permissions up
            INNER JOIN permissions p ON p.permission_id = up.permission_id
            WHERE up.user_id = :uid
              AND p.permission_key = :pkey
              AND up.valid_from <= NOW()
              AND (up.valid_to IS NULL OR up.valid_to >= NOW())
            LIMIT 1
        SQL;
        $stmt = $pdo->prepare($sqlUser);
        $stmt->execute([':uid' => $userId, ':pkey' => $permissionKey]);
        if ($stmt->fetch(PDO::FETCH_NUM)) {
            return true;
        }

        // Role-based permission
        $stmt = $pdo->prepare('SELECT role FROM users WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $role = $stmt->fetchColumn();
        if (!$role) {
            return false;
        }

        $sqlRole = <<<SQL
            SELECT 1
            FROM role_permissions rp
            INNER JOIN permissions p ON p.permission_id = rp.permission_id
            WHERE rp.role = :role
              AND p.permission_key = :pkey
            LIMIT 1
        SQL;
        $stmt = $pdo->prepare($sqlRole);
        $stmt->execute([':role' => $role, ':pkey' => $permissionKey]);
        return (bool) $stmt->fetch(PDO::FETCH_NUM);
    }

    public static function require(Config $config, int $userId, string $permissionKey): void
    {
        if (!self::has($config, $userId, $permissionKey)) {
            http_response_code(403);
            echo '403 Forbidden';
            exit;
        }
    }
}
