<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Skill
{
    public static function findByName(Config $config, string $name): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM skills WHERE skill_name = :n LIMIT 1');
        $stmt->execute([':n' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function ensure(Config $config, string $name, ?string $category = null): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT skill_id FROM skills WHERE skill_name = :n LIMIT 1');
        $stmt->execute([':n' => $name]);
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;
        $stmt = $pdo->prepare('INSERT INTO skills (skill_name, category) VALUES (:n, :c)');
        $stmt->execute([':n' => $name, ':c' => $category]);
        return (int)$pdo->lastInsertId();
    }
}
