<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Notification
{
    public static function send(Config $config, int $userId, string $title, string $message, string $type, ?string $entityType = null, ?int $entityId = null, ?string $actionUrl = null): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, notification_type, entity_type, entity_id, action_url) VALUES (:u,:t,:m,:ty,:et,:ei,:au)');
        return $stmt->execute([':u' => $userId, ':t' => $title, ':m' => $message, ':ty' => $type, ':et' => $entityType, ':ei' => $entityId, ':au' => $actionUrl]);
    }
}
