<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class AdminAssignment
{
    public static function listByUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT assignment FROM admin_assignments WHERE user_id = :u ORDER BY assignment');
        $stmt->execute([':u'=>$userId]);
        return array_map(static fn($r)=>$r['assignment'], $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public static function sync(Config $config, int $userId, array $assignments): void
    {
        $pdo = Database::pdo($config);
        $assignments = array_values(array_unique(array_filter(array_map('trim', $assignments), static fn($a)=>$a!=='')));
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT assignment FROM admin_assignments WHERE user_id = :u');
            $stmt->execute([':u'=>$userId]);
            $current = array_map(static fn($r)=>$r['assignment'], $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
            $toAdd = array_diff($assignments, $current);
            $toRemove = array_diff($current, $assignments);
            if ($toRemove) {
                $in = implode(',', array_fill(0, count($toRemove), '?'));
                $stmt = $pdo->prepare('DELETE FROM admin_assignments WHERE user_id = ? AND assignment IN (' . $in . ')');
                $stmt->execute(array_merge([$userId], array_values($toRemove)));
            }
            foreach ($toAdd as $a) {
                $stmt = $pdo->prepare('INSERT INTO admin_assignments (user_id, assignment) VALUES (:u,:a)');
                $stmt->execute([':u'=>$userId, ':a'=>$a]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
