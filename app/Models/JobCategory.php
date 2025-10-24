<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class JobCategory
{
    public static function all(Config $config): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->query('SELECT category_id, category_name FROM job_categories ORDER BY category_name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
