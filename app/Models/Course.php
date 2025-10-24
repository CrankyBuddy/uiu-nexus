<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Course
{
    public static function ensure(Config $config, ?string $code, string $name): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT course_id FROM courses WHERE name = :n LIMIT 1');
        $stmt->execute([':n' => $name]);
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;
        $stmt = $pdo->prepare('INSERT INTO courses (code, name) VALUES (:c, :n)');
        $stmt->execute([':c' => $code, ':n' => $name]);
        return (int)$pdo->lastInsertId();
    }
}
