<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class JobApplication
{
    public static function create(Config $config, int $jobId, int $studentId, string $coverLetter)
    {
        $pdo = Database::pdo($config);
        $sql = 'INSERT INTO job_applications (job_id, student_id, cover_letter) VALUES (:j, :s, :c)';
        try {
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([':j' => $jobId, ':s' => $studentId, ':c' => $coverLetter]);
            if ($ok) { return (int)$pdo->lastInsertId(); }
            return false;
        } catch (\Throwable $e) {
            return false; // likely duplicate application
        }
    }

    public static function forJob(Config $config, int $jobId, ?string $status = null, ?string $query = null): array
    {
        $pdo = Database::pdo($config);
        $where = ['a.job_id = :j'];
        $params = [':j' => $jobId];
        if ($status && in_array($status, ['applied','under_review','shortlisted','interview','accepted','rejected'], true)) {
            $where[] = 'a.status = :s';
            $params[':s'] = $status;
        }
        if ($query) {
            $where[] = '(up.first_name LIKE :q OR up.last_name LIKE :q OR u.email LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }
        $sql = 'SELECT a.*, up.first_name, up.last_name, s.student_id
                FROM job_applications a
                INNER JOIN students s ON s.student_id = a.student_id
                INNER JOIN users u ON u.user_id = s.user_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY a.applied_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function forStudent(Config $config, int $studentId): array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT a.*, j.job_title, c.category_name, t.type_name
                FROM job_applications a
                INNER JOIN job_listings j ON j.job_id = a.job_id
                INNER JOIN job_categories c ON c.category_id = j.category_id
                INNER JOIN job_types t ON t.type_id = j.type_id
                WHERE a.student_id = :s
                ORDER BY a.applied_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':s' => $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
