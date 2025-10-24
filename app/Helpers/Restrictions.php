<?php
declare(strict_types=1);

namespace Nexus\Helpers;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Restrictions
{
    /**
     * Check if a user has an active restriction for a given feature key.
     * Feature keys are exact (e.g., 'platform', 'social', 'msg.block.recruiter:123').
     */
    public static function isRestricted(Config $config, int $userId, string $featureKey): bool
    {
        $pdo = Database::pdo($config);
        $sql = "SELECT 1 FROM user_feature_restrictions WHERE user_id = :u AND feature_key = :k AND (restricted_until IS NULL OR restricted_until > NOW()) LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([':u' => $userId, ':k' => $featureKey]);
        return (bool)$st->fetch(PDO::FETCH_NUM);
    }

    /**
     * Convenience: platform-wide suspension/ban.
     */
    public static function isPlatformSuspended(Config $config, int $userId): bool
    {
        return self::isRestricted($config, $userId, 'platform');
    }

    /**
     * Convenience: social features suspension (messaging, forum interactions, etc.).
     */
    public static function isSocialSuspended(Config $config, int $userId): bool
    {
        return self::isRestricted($config, $userId, 'social');
    }

    /**
     * Convenience: chat-only suspension.
     */
    public static function isChatSuspended(Config $config, int $userId): bool
    {
        return self::isRestricted($config, $userId, 'chat');
    }

    /**
     * Convenience: mentorship features suspension.
     */
    public static function isMentorshipSuspended(Config $config, int $userId): bool
    {
        return self::isRestricted($config, $userId, 'mentorship');
    }
}
