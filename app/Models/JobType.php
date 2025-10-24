<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class JobType
{
    public static function all(Config $config): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->query('SELECT type_id, type_name FROM job_types ORDER BY type_name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
