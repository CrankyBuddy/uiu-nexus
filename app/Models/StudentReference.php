<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class StudentReference
{
    public static function create(Config $config, int $studentId, int $alumniId, int $createdBy, string $createdByRole, ?string $text): int
    {
        $pdo = Database::pdo($config);
        $sql = 'INSERT INTO student_references (student_id, alumni_id, reference_text, status, created_by, created_by_role) VALUES (:s,:a,:t,\'active\',:cb,:role)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':s' => $studentId, ':a' => $alumniId, ':t' => $text, ':cb' => $createdBy, ':role' => $createdByRole]);
        return (int)$pdo->lastInsertId();
    }

    public static function revoke(Config $config, int $referenceId, int $actorUserId, ?string $reason): bool
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare("UPDATE student_references SET status='revoked', revoked_by=:u, revoked_at=NOW(), revoke_reason=:r WHERE reference_id=:id AND status='active'");
        $st->execute([':u' => $actorUserId, ':r' => $reason, ':id' => $referenceId]);
        return $st->rowCount() === 1;
    }

    public static function delete(Config $config, int $referenceId): bool
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare('DELETE FROM student_references WHERE reference_id = :id');
        $st->execute([':id' => $referenceId]);
        return $st->rowCount() === 1;
    }

    public static function forStudent(Config $config, int $studentId): array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT sr.*, a.alumni_id, ua.user_id AS alumni_user_id, up.first_name AS alumni_first_name, up.last_name AS alumni_last_name
                FROM student_references sr
                JOIN alumni a ON a.alumni_id = sr.alumni_id
                JOIN users ua ON ua.user_id = a.user_id
                LEFT JOIN user_profiles up ON up.user_id = ua.user_id
                WHERE sr.student_id = :s ORDER BY sr.created_at DESC';
        $st = $pdo->prepare($sql);
        $st->execute([':s' => $studentId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(Config $config, int $referenceId): ?array
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare('SELECT * FROM student_references WHERE reference_id = :id');
        $st->execute([':id' => $referenceId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
