<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Student
{
    // If the 4-month window elapsed since last reset, restore tickets to 3 and move reset marker
    public static function refreshFreeTicketsIfWindowElapsed(Config $config, int $studentId): void
    {
        $pdo = Database::pdo($config);
                $sql = 'UPDATE students
                                SET free_mentorship_requests = free_mentorship_requests + 3,
                                        free_mentorship_reset_at = NOW()
                                WHERE student_id = :id
                                    AND (free_mentorship_reset_at IS NULL OR free_mentorship_reset_at < DATE_SUB(NOW(), INTERVAL 4 MONTH))';
        $st = $pdo->prepare($sql);
        $st->execute([':id' => $studentId]);
    }

    public static function findByUserId(Config $config, int $userId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM students WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function upsert(Config $config, int $userId, array $data): bool
    {
        $pdo = Database::pdo($config);
    $sql = 'INSERT INTO students (user_id, department, program_level, cgpa, university_id, admission_year, admission_trimester, current_semester)
        VALUES (:uid, :dept, :plevel, :cgpa, :univid, :admy, :atrim, :semester)
        ON DUPLICATE KEY UPDATE department=VALUES(department), program_level=VALUES(program_level), cgpa=VALUES(cgpa), university_id=VALUES(university_id), admission_year=VALUES(admission_year), admission_trimester=VALUES(admission_trimester), current_semester=VALUES(current_semester)';
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':uid' => $userId,
            ':dept' => $data['department'] ?? null,
            ':plevel' => $data['program_level'] ?? null,
            ':cgpa' => $data['cgpa'] ?? null,
            ':univid' => $data['university_id'] ?? null,
            ':admy' => $data['admission_year'] ?? null,
            ':atrim' => $data['admission_trimester'] ?? null,
            ':semester' => $data['current_semester'] ?? null,
        ]);
    }

    public static function consumeFreeRequest(Config $config, int $studentId): bool
    {
        $pdo = Database::pdo($config);
        // Ensure the 4-month window is applied before consuming
        self::refreshFreeTicketsIfWindowElapsed($config, $studentId);
        $stmt = $pdo->prepare('UPDATE students SET free_mentorship_requests = GREATEST(free_mentorship_requests - 1, 0) WHERE student_id = :id AND free_mentorship_requests > 0');
        $stmt->execute([':id' => $studentId]);
        return $stmt->rowCount() === 1;
    }

    public static function applyCooldown(Config $config, int $studentId, int $days): void
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('UPDATE students SET mentorship_cooldown_until = DATE_ADD(NOW(), INTERVAL :d DAY) WHERE student_id = :id');
        $stmt->execute([':id' => $studentId, ':d' => $days]);
    }

    public static function isInCooldown(Config $config, int $studentId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT mentorship_cooldown_until FROM students WHERE student_id = :id');
        $stmt->execute([':id' => $studentId]);
        $ts = $stmt->fetchColumn();
        return $ts && strtotime((string)$ts) > time();
    }
}
