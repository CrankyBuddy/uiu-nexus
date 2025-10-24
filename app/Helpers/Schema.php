<?php
declare(strict_types=1);

namespace Nexus\Helpers;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Schema
{
    private static ?bool $isV2 = null;

    public static function isV2(Config $config): bool
    {
        if (self::$isV2 !== null) {
            return self::$isV2;
        }
        // Preferred: system_settings.schema_version >= 2
        try {
            $pdo = Database::pdo($config);
            $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'schema_version' LIMIT 1");
            $stmt->execute();
            $val = (string)($stmt->fetchColumn() ?: '');
            if ($val !== '') {
                $num = (int)preg_replace('/[^0-9]/', '', $val);
                if ($num >= 2) {
                    return self::$isV2 = true;
                }
            }
        } catch (\Throwable $e) {
            // ignore and try fallback detection
        }
        // Fallback heuristic: check if forum_posts.moderation_status column exists
        try {
            $pdo = Database::pdo($config);
            $sql = "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'forum_posts' AND COLUMN_NAME = 'moderation_status'";
            $c = (int)$pdo->query($sql)->fetchColumn();
            return self::$isV2 = ($c > 0);
        } catch (\Throwable $e) {
            return self::$isV2 = false;
        }
    }
}
