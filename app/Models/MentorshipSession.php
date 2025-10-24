<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class MentorshipSession
{
    public static function create(Config $config, int $requestId, string $date, string $time, int $durationMinutes, ?string $meetingLink = null): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO mentorship_sessions (request_id, session_date, session_time, duration_minutes, meeting_link) VALUES (?,?,?,?,?)');
        $stmt->execute([$requestId, $date, $time, $durationMinutes, $meetingLink]);
        return (int)$pdo->lastInsertId();
    }

    public static function forRequest(Config $config, int $requestId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM mentorship_sessions WHERE request_id = ? ORDER BY session_date, session_time');
        $stmt->execute([$requestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(Config $config, int $sessionId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM mentorship_sessions WHERE session_id = ?');
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function markCompleted(Config $config, int $sessionId): void
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare("UPDATE mentorship_sessions SET status = 'completed' WHERE session_id = :id");
        $stmt->execute([':id' => $sessionId]);
    }

    public static function setStudentFeedback(Config $config, int $sessionId, int $rating, string $feedback): void
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('UPDATE mentorship_sessions SET student_rating = :r, student_feedback = :f WHERE session_id = :id');
        $stmt->execute([':r' => $rating, ':f' => $feedback, ':id' => $sessionId]);
    }

    public static function setMentorFeedback(Config $config, int $sessionId, int $rating, string $feedback): void
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('UPDATE mentorship_sessions SET mentor_rating = :r, mentor_feedback = :f WHERE session_id = :id');
        $stmt->execute([':r' => $rating, ':f' => $feedback, ':id' => $sessionId]);
    }
}
