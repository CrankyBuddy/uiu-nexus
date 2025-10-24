<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Location
{
    public static function all(Config $config): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->query('SELECT location_id, location_name FROM locations ORDER BY location_name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
