<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class StudentProject
{
    public static function listByUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
    // Strict insertion order (oldest first), so new items appear last
    $stmt = $pdo->prepare('SELECT * FROM student_projects WHERE user_id = :u ORDER BY project_id ASC');
        $stmt->execute([':u' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listIdsByUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT project_id FROM student_projects WHERE user_id = :u');
        $stmt->execute([':u' => $userId]);
        return array_map(fn($r) => (int)$r['project_id'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function upsert(Config $config, ?int $projectId, int $userId, array $data): int
    {
        $pdo = Database::pdo($config);
        if ($projectId) {
            $stmt = $pdo->prepare('UPDATE student_projects SET title=:t, short_description=:d, github_url=:g, portfolio_url=:p, certificate_url=:c WHERE project_id = :id AND user_id = :u');
            $stmt->execute([':t'=>$data['title'],':d'=>$data['short_description'],':g'=>$data['github_url'],':p'=>$data['portfolio_url'],':c'=>$data['certificate_url'],':id'=>$projectId,':u'=>$userId]);
            return $projectId;
        }
        $stmt = $pdo->prepare('INSERT INTO student_projects (user_id, title, short_description, github_url, portfolio_url, certificate_url) VALUES (:u,:t,:d,:g,:p,:c)');
        $stmt->execute([':u'=>$userId,':t'=>$data['title'],':d'=>$data['short_description'],':g'=>$data['github_url'],':p'=>$data['portfolio_url'],':c'=>$data['certificate_url']]);
        return (int)$pdo->lastInsertId();
    }

    public static function delete(Config $config, int $userId, int $projectId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('DELETE FROM student_projects WHERE project_id = :id AND user_id = :u');
        $stmt->execute([':id'=>$projectId, ':u'=>$userId]);
        return $stmt->rowCount() > 0;
    }

    public static function deleteAllForUser(Config $config, int $userId): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('DELETE FROM student_projects WHERE user_id = :u');
        $stmt->execute([':u' => $userId]);
        return $stmt->rowCount();
    }
}
