<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Interview
{
    public static function create(Config $config, int $applicationId, string $scheduledDate, int $durationMinutes, ?string $meetingLink, ?string $interviewerName): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO interviews (application_id, scheduled_date, duration_minutes, meeting_link, interviewer_name, status) VALUES (:a,:d,:m,:l,:n,\'scheduled\')');
        $stmt->execute([':a' => $applicationId, ':d' => $scheduledDate, ':m' => $durationMinutes, ':l' => $meetingLink, ':n' => $interviewerName]);
        return (int)$pdo->lastInsertId();
    }

    public static function forApplication(Config $config, int $applicationId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM interviews WHERE application_id = :a ORDER BY scheduled_date DESC');
        $stmt->execute([':a' => $applicationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
