<?php
declare(strict_types=1);

namespace Nexus\Helpers;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Audit
{
    public static function log(Config $config, ?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, $oldValues = null, $newValues = null): void
    {
        try {
            $pdo = Database::pdo($config);
            $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) VALUES (:uid, :act, :etype, :eid, :oldv, :newv, :ip, :ua)');
            $stmt->execute([
                ':uid' => $userId,
                ':act' => $action,
                ':etype' => $entityType,
                ':eid' => $entityId,
                ':oldv' => is_null($oldValues) ? null : json_encode($oldValues),
                ':newv' => is_null($newValues) ? null : json_encode($newValues),
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (\Throwable $e) {
            // Swallow audit failures to not impact UX
        }
    }
}
