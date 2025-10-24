<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class JobApplicationReference
{
    public static function setForApplication(Config $config, int $applicationId, array $referenceIds): void
    {
        $pdo = Database::pdo($config);
        // Clear existing first
        $pdo->prepare('DELETE FROM job_application_references WHERE application_id = :a')->execute([':a' => $applicationId]);
        if (!$referenceIds) return;
        $ins = $pdo->prepare('INSERT IGNORE INTO job_application_references (application_id, reference_id) VALUES (:a,:r)');
        $count = 0;
        foreach ($referenceIds as $rid) {
            $count++;
            if ($count > 2) break; // enforce max 2 at app layer
            $ins->execute([':a' => $applicationId, ':r' => (int)$rid]);
        }
    }

    public static function forApplication(Config $config, int $applicationId): array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT jar.reference_id, sr.student_id, sr.alumni_id, sr.status, sr.reference_text,
                       up.first_name AS alumni_first_name, up.last_name AS alumni_last_name, ua.user_id AS alumni_user_id
                FROM job_application_references jar
                JOIN student_references sr ON sr.reference_id = jar.reference_id
                JOIN alumni a ON a.alumni_id = sr.alumni_id
                JOIN users ua ON ua.user_id = a.user_id
                LEFT JOIN user_profiles up ON up.user_id = ua.user_id
                WHERE jar.application_id = :a';
        $st = $pdo->prepare($sql);
        $st->execute([':a' => $applicationId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
