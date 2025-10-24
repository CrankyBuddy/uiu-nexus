<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class ReportExport
{
    public static function log(Config $config, int $exportedBy, string $exportType, array $filters, string $fileFormat, string $fileUrl, int $recordCount): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO report_exports (exported_by, export_type, filters, file_format, file_url, record_count) VALUES (:by,:t,:f,:fmt,:url,:c)');
        $stmt->execute([
            ':by' => $exportedBy,
            ':t' => $exportType,
            ':f' => json_encode($filters),
            ':fmt' => $fileFormat,
            ':url' => $fileUrl,
            ':c' => $recordCount,
        ]);
        return (int)$pdo->lastInsertId();
    }
}
