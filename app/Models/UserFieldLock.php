<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class UserFieldLock
{
    /**
     * Return a list of active locked field keys for a user.
     * Active = locked_until IS NULL or locked_until > NOW().
     */
    public static function activeKeysForUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
        try {
            $stmt = $pdo->prepare('SELECT field_key FROM user_field_locks WHERE user_id = :u AND (locked_until IS NULL OR locked_until > CURRENT_TIMESTAMP)');
            $stmt->execute([':u' => $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            return array_values(array_unique(array_map(static fn($k) => (string)$k, $rows)));
        } catch (\Throwable $e) {
            // Table may not exist yet
            return [];
        }
    }

    /** Get all locks for a specific user (for admin UI). */
    public static function forUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
        try {
            $stmt = $pdo->prepare('SELECT id, field_key, locked_by, locked_at, locked_until, reason FROM user_field_locks WHERE user_id = :u ORDER BY locked_at DESC');
            $stmt->execute([':u' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** List recent locks across users (admin index). */
    public static function recent(Config $config, int $limit = 200): array
    {
        $pdo = Database::pdo($config);
        $limit = max(1, min(1000, $limit));
        try {
            $stmt = $pdo->prepare('SELECT l.*, u.email AS user_email, a.email AS admin_email
                                    FROM user_field_locks l
                                    INNER JOIN users u ON u.user_id = l.user_id
                                    INNER JOIN users a ON a.user_id = l.locked_by
                                    ORDER BY l.locked_at DESC
                                    LIMIT ' . $limit);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Add a lock; returns new id or 0 on failure. */
    public static function add(Config $config, int $userId, string $fieldKey, int $lockedBy, ?string $lockedUntil, ?string $reason): int
    {
        $pdo = Database::pdo($config);
        try {
            $stmt = $pdo->prepare('INSERT INTO user_field_locks (user_id, field_key, locked_by, locked_at, locked_until, reason) VALUES (:u,:k,:by,CURRENT_TIMESTAMP,:until,:r)');
            $stmt->execute([
                ':u' => $userId,
                ':k' => $fieldKey,
                ':by' => $lockedBy,
                ':until' => $lockedUntil,
                ':r' => $reason !== '' ? $reason : null,
            ]);
            return (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Remove a lock by id. */
    public static function remove(Config $config, int $id): bool
    {
        $pdo = Database::pdo($config);
        try {
            $stmt = $pdo->prepare('DELETE FROM user_field_locks WHERE id = :id');
            return $stmt->execute([':id' => $id]);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
