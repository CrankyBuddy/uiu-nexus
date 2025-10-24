<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Referral
{
    public static function create(Config $config, int $jobId, int $alumniId, int $studentId, string $message): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO referrals (job_id, alumni_id, student_id, message, status, reward_coins) VALUES (:j,:a,:s,:m,\'pending\',0)');
        $stmt->execute([':j' => $jobId, ':a' => $alumniId, ':s' => $studentId, ':m' => $message]);
        return (int)$pdo->lastInsertId();
    }

    public static function forJob(Config $config, int $jobId): array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT r.*, a.alumni_id, s.student_id FROM referrals r INNER JOIN alumni a ON a.alumni_id = r.alumni_id INNER JOIN students s ON s.student_id = r.student_id WHERE r.job_id = :j ORDER BY r.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':j' => $jobId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function forAlumni(Config $config, int $alumniId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT r.*, j.job_title FROM referrals r INNER JOIN job_listings j ON j.job_id = r.job_id WHERE r.alumni_id = :a ORDER BY r.created_at DESC');
        $stmt->execute([':a' => $alumniId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function updateStatus(Config $config, int $referralId, string $status, int $rewardCoins = 0): bool
    {
        if (!in_array($status, ['pending','accepted','declined'], true)) return false;
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('UPDATE referrals SET status = :s, reward_coins = :r WHERE referral_id = :id');
        return $stmt->execute([':s' => $status, ':r' => $rewardCoins, ':id' => $referralId]);
    }
}
