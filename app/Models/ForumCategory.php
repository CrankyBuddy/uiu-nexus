<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class ForumCategory
{
    public static function all(Config $config): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->query('SELECT category_id, category_name, description, is_active FROM forum_categories WHERE is_active = 1 ORDER BY category_name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(Config $config, int $id): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM forum_categories WHERE category_id = ? AND is_active = 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
