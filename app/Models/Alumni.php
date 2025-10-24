<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Alumni
{
    public static function findByUserId(Config $config, int $userId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM alumni WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function upsert(Config $config, int $userId, array $data): bool
    {
        $pdo = Database::pdo($config);
    $sql = 'INSERT INTO alumni (user_id, department, company, job_title, years_of_experience, graduation_year, cgpa, mentorship_availability, max_mentorship_slots, industry, university_id, student_id_number, program_level)
        VALUES (:uid, :dept, :company, :job, :yoe, :grad, :cgpa, :avail, :slots, :industry, :univid, :student_id_number, :program_level)
        ON DUPLICATE KEY UPDATE department=VALUES(department), company=VALUES(company), job_title=VALUES(job_title), years_of_experience=VALUES(years_of_experience), graduation_year=VALUES(graduation_year), cgpa=VALUES(cgpa), mentorship_availability=VALUES(mentorship_availability), max_mentorship_slots=VALUES(max_mentorship_slots), industry=VALUES(industry), university_id=VALUES(university_id), student_id_number=VALUES(student_id_number), program_level=VALUES(program_level)';
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':uid' => $userId,
            ':dept' => $data['department'] ?? null,
            ':company' => $data['company'] ?? null,
            ':job' => $data['job_title'] ?? null,
            ':yoe' => $data['years_of_experience'] ?? null,
            ':grad' => $data['graduation_year'] ?? null,
            ':cgpa' => $data['cgpa'] ?? null,
            ':avail' => isset($data['mentorship_availability']) ? (int)(bool)$data['mentorship_availability'] : 0,
            ':slots' => $data['max_mentorship_slots'] ?? 5,
            ':industry' => $data['industry'] ?? null,
            ':univid' => $data['university_id'] ?? null,
            ':student_id_number' => $data['student_id_number'] ?? null,
            ':program_level' => $data['program_level'] ?? null,
        ]);
    }
}
