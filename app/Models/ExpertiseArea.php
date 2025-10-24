<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class ExpertiseArea
{
    public static function all(Config $config): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->query('SELECT expertise_id, area_name FROM expertise_areas ORDER BY area_name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
