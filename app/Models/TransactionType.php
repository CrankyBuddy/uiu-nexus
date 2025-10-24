<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class TransactionType
{
    public static function ensure(Config $config, string $typeName, int $defaultAmount, bool $isEarning, string $module): int
    {
        $pdo = Database::pdo($config);
        // Try find
        $stmt = $pdo->prepare('SELECT type_id FROM transaction_types WHERE type_name = :n LIMIT 1');
        $stmt->execute([':n' => $typeName]);
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;

        // Create
        try {
            $stmt = $pdo->prepare('INSERT INTO transaction_types (type_name, description, default_amount, is_earning, module) VALUES (:n, :d, :a, :e, :m)');
            $stmt->execute([
                ':n' => $typeName,
                ':d' => $typeName,
                ':a' => $defaultAmount,
                ':e' => $isEarning ? 1 : 0,
                ':m' => $module,
            ]);
            return (int)$pdo->lastInsertId();
        } catch (\PDOException $e) {
            // Handle race: another process inserted it after our initial SELECT
            if ((string)$e->getCode() === '23000') {
                $stmt = $pdo->prepare('SELECT type_id FROM transaction_types WHERE type_name = :n LIMIT 1');
                $stmt->execute([':n' => $typeName]);
                $id = $stmt->fetchColumn();
                if ($id) { return (int)$id; }
            }
            throw $e;
        }
    }
}
