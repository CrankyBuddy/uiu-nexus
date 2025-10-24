<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class SystemSetting
{
    public static function set(Config $config, string $key, string $value, string $dataType = 'string', ?string $description = null, ?int $updatedBy = null): bool
    {
        $pdo = Database::pdo($config);
        // Be robust even if a unique index on setting_key is missing:
        // 1) delete any existing rows for this key
        try {
            $del = $pdo->prepare('DELETE FROM system_settings WHERE setting_key = :k');
            $del->execute([':k' => $key]);
        } catch (\Throwable $e) { /* ignore */ }
        // 2) insert fresh row
        $sql = 'INSERT INTO system_settings (setting_key, setting_value, data_type, description, updated_by) VALUES (:k, :v, :t, :d, :u)';
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':k' => $key,
            ':v' => $value,
            ':t' => $dataType,
            ':d' => $description,
            ':u' => $updatedBy,
        ]);
    }

    public static function get(Config $config, string $key): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM system_settings WHERE setting_key = :k LIMIT 1');
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function delete(Config $config, string $key): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('DELETE FROM system_settings WHERE setting_key = :k');
        return $stmt->execute([':k' => $key]);
    }
}
