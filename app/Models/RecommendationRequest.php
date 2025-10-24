<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class RecommendationRequest
{
    public static function create(Config $config, int $studentId, int $alumniId, ?string $message): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO recommendation_requests (student_id, alumni_id, message) VALUES (:s, :a, :m)');
        $stmt->execute([':s' => $studentId, ':a' => $alumniId, ':m' => $message]);
        return (int)$pdo->lastInsertId();
    }

    public static function forStudent(Config $config, int $studentId): array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT rr.*, ua.user_id AS alumni_user_id, upa.first_name AS alumni_first_name, upa.last_name AS alumni_last_name
                FROM recommendation_requests rr
                JOIN alumni a ON a.alumni_id = rr.alumni_id
                JOIN users ua ON ua.user_id = a.user_id
                LEFT JOIN user_profiles upa ON upa.user_id = ua.user_id
                WHERE rr.student_id = :s ORDER BY rr.created_at DESC';
        $st = $pdo->prepare($sql);
        $st->execute([':s' => $studentId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function forMentor(Config $config, int $alumniId): array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT rr.*, us.user_id AS student_user_id, ups.first_name AS student_first_name, ups.last_name AS student_last_name
                FROM recommendation_requests rr
                JOIN students s ON s.student_id = rr.student_id
                JOIN users us ON us.user_id = s.user_id
                LEFT JOIN user_profiles ups ON ups.user_id = us.user_id
                WHERE rr.alumni_id = :a ORDER BY rr.created_at DESC';
        $st = $pdo->prepare($sql);
        $st->execute([':a' => $alumniId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(Config $config, int $requestId): ?array
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare('SELECT * FROM recommendation_requests WHERE request_id = :id');
        $st->execute([':id' => $requestId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findWithUsers(Config $config, int $requestId): ?array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT rr.*, s.student_id, s.user_id AS student_user_id, a.alumni_id, a.user_id AS alumni_user_id
                FROM recommendation_requests rr
                JOIN students s ON s.student_id = rr.student_id
                JOIN alumni a ON a.alumni_id = rr.alumni_id
                WHERE rr.request_id = :id';
        $st = $pdo->prepare($sql);
        $st->execute([':id' => $requestId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function accept(Config $config, int $requestId, array $mentorSnapshot, ?string $note): bool
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare('UPDATE recommendation_requests SET status = "accepted", responded_at = CURRENT_TIMESTAMP, mentor_snapshot = :snap, mentor_note = :note WHERE request_id = :id AND status = "pending"');
        $st->execute([':snap' => json_encode($mentorSnapshot), ':note' => $note, ':id' => $requestId]);
        return $st->rowCount() === 1;
    }

    public static function reject(Config $config, int $requestId): bool
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare('UPDATE recommendation_requests SET status = "rejected", responded_at = CURRENT_TIMESTAMP WHERE request_id = :id AND status = "pending"');
        $st->execute([':id' => $requestId]);
        return $st->rowCount() === 1;
    }

    public static function revoke(Config $config, int $requestId, int $alumniId): bool
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare('UPDATE recommendation_requests rr JOIN alumni a ON a.alumni_id = rr.alumni_id SET rr.status = "revoked", rr.revoked_at = CURRENT_TIMESTAMP WHERE rr.request_id = :id AND a.alumni_id = :aid AND rr.status = "accepted"');
        $st->execute([':id' => $requestId, ':aid' => $alumniId]);
        return $st->rowCount() === 1;
    }

    public static function hasActiveOrPending(Config $config, int $studentId, int $alumniId): bool
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare("SELECT 1 FROM recommendation_requests WHERE student_id = :s AND alumni_id = :a AND status IN ('pending','accepted') LIMIT 1");
        $st->execute([':s' => $studentId, ':a' => $alumniId]);
        return (bool)$st->fetch(PDO::FETCH_NUM);
    }

    public static function buildMentorSnapshot(Config $config, int $alumniId): array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT a.alumni_id, a.department, a.company, a.job_title, a.years_of_experience, a.industry, a.university_id,
                       u.user_id, u.email, up.first_name, up.last_name, up.profile_picture_url, up.linkedin_url, up.portfolio_url
                FROM alumni a
                JOIN users u ON u.user_id = a.user_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE a.alumni_id = :aid';
        $st = $pdo->prepare($sql);
        $st->execute([':aid' => $alumniId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        return $row;
    }
}
