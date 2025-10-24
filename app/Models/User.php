<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class User
{
    public static function findByEmail(Config $config, string $email): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findById(Config $config, int $id): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(Config $config, string $email, string $passwordHash, string $role): ?int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, is_verified, is_active) VALUES (:email, :hash, :role, 1, 1)');
        try {
            $stmt->execute([':email' => $email, ':hash' => $passwordHash, ':role' => $role]);
            return (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function touchLastLogin(Config $config, int $id): void
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE user_id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function verify(Config $config, int $id): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE user_id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // admin_level removed; setAdminLevel deprecated and deleted

    public static function updatePassword(Config $config, int $id, string $passwordHash): bool
    {
        $pdo = Database::pdo($config);
        // Note: Some deployments may not have an updated_at column on users; only update the password hash.
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :h WHERE user_id = :id');
        return $stmt->execute([':h' => $passwordHash, ':id' => $id]);
    }
}
