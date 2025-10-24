<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class RecruiterPosition
{
    public static function listByUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM recruiter_positions WHERE user_id = :u ORDER BY position_id DESC');
        $stmt->execute([':u' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function upsert(Config $config, ?int $positionId, int $userId, array $data, array $skillNames): int
    {
        $pdo = Database::pdo($config);
        if ($positionId) {
            $stmt = $pdo->prepare('UPDATE recruiter_positions SET title=:t, description=:d, deadline=:dl, type=:ty, qualifications=:q WHERE position_id=:id AND user_id=:u');
            $stmt->execute([':t'=>$data['title'],':d'=>$data['description'],':dl'=>$data['deadline'],':ty'=>$data['type'],':q'=>$data['qualifications'],':id'=>$positionId,':u'=>$userId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO recruiter_positions (user_id, title, description, deadline, type, qualifications) VALUES (:u,:t,:d,:dl,:ty,:q)');
            $stmt->execute([':u'=>$userId,':t'=>$data['title'],':d'=>$data['description'],':dl'=>$data['deadline'],':ty'=>$data['type'],':q'=>$data['qualifications']]);
            $positionId = (int)$pdo->lastInsertId();
        }
        // Sync skills
        $stmt = $pdo->prepare('DELETE rps FROM recruiter_position_skills rps JOIN skills s ON s.skill_id = rps.skill_id WHERE rps.position_id = :pid');
        $stmt->execute([':pid'=>$positionId]);
        foreach (array_values(array_unique(array_filter(array_map('trim', $skillNames)))) as $name) {
            $sid = Skill::ensure($config, $name, null);
            $stmt = $pdo->prepare('INSERT INTO recruiter_position_skills (position_id, skill_id) VALUES (:p,:s)');
            $stmt->execute([':p'=>$positionId, ':s'=>$sid]);
        }
        return $positionId;
    }

    public static function delete(Config $config, int $userId, int $positionId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('DELETE FROM recruiter_positions WHERE position_id = :id AND user_id = :u');
        $stmt->execute([':id'=>$positionId, ':u'=>$userId]);
        return $stmt->rowCount() > 0;
    }
}
