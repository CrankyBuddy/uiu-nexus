<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class AlumniPreference
{
    public static function get(Config $config, int $userId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM alumni_preferences WHERE user_id = :u');
        $stmt->execute([':u'=>$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function upsert(Config $config, int $userId, array $data): void
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO alumni_preferences (user_id, mentees_allowed, meeting_type, specific_requirements, timezone, preferred_hours)
                               VALUES (:u,:m,:mt,:req,:tz,:ph)
                               ON DUPLICATE KEY UPDATE mentees_allowed=VALUES(mentees_allowed), meeting_type=VALUES(meeting_type), specific_requirements=VALUES(specific_requirements), timezone=VALUES(timezone), preferred_hours=VALUES(preferred_hours)');
        $stmt->execute([
            ':u'=>$userId,
            ':m'=>$data['mentees_allowed'] ?? null,
            ':mt'=>$data['meeting_type'] ?? null,
            ':req'=>$data['specific_requirements'] ?? null,
            ':tz'=>$data['timezone'] ?? null,
            ':ph'=>$data['preferred_hours'] ?? null,
        ]);
    }
}
