<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Admin
{
    public static function findByUserId(Config $config, int $userId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function upsert(Config $config, int $userId, array $data): bool
    {
        $pdo = Database::pdo($config);
        $sql = 'INSERT INTO admins (user_id, role_title)
                VALUES (:uid, :role)
                ON DUPLICATE KEY UPDATE role_title=VALUES(role_title)';
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':uid' => $userId,
            ':role' => $data['role_title'] ?? 'Admin',
        ]);
    }
}
