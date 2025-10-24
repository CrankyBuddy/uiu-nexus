<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class JobListing
{
    public static function create(
        Config $config,
        int $recruiterId,
        string $title,
        string $description,
        int $categoryId,
        int $typeId,
        int $locationId,
        ?string $duration,
        ?int $salaryMin,
        ?int $salaryMax,
        ?int $stipend,
        ?string $deadline,
        ?string $requiredSkillsJson,
        bool $isActive,
        bool $isApproved,
        bool $isPremium
    ): int {
        $pdo = Database::pdo($config);
        $sql = 'INSERT INTO job_listings (recruiter_id, job_title, job_description, category_id, type_id, location_id, duration, salary_range_min, salary_range_max, stipend_amount, application_deadline, required_skills, is_active, is_approved, is_premium)
                VALUES (:rid, :title, :descr, :cat, :type, :loc, :dur, :smin, :smax, :stip, :deadline, :skills, :active, :approved, :premium)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':rid' => $recruiterId,
            ':title' => $title,
            ':descr' => $description,
            ':cat' => $categoryId,
            ':type' => $typeId,
            ':loc' => $locationId,
            ':dur' => $duration,
            ':smin' => $salaryMin,
            ':smax' => $salaryMax,
            ':stip' => $stipend,
            ':deadline' => $deadline !== '' ? $deadline : null,
            ':skills' => $requiredSkillsJson,
            ':active' => $isActive ? 1 : 0,
            ':approved' => $isApproved ? 1 : 0,
            ':premium' => $isPremium ? 1 : 0,
        ]);
        $jobId = (int)$pdo->lastInsertId();

        // Also populate companion 3NF table job_listing_skills if skills JSON provided
        if ($requiredSkillsJson) {
            $skills = json_decode($requiredSkillsJson, true);
            if (is_array($skills)) {
                $ins = $pdo->prepare('INSERT IGNORE INTO job_listing_skills (job_id, skill_id) VALUES (:j,:s)');
                foreach ($skills as $sname) {
                    $sname = trim((string)$sname);
                    if ($sname === '') continue;
                    $sid = \Nexus\Models\Skill::ensure($config, $sname, null);
                    $ins->execute([':j' => $jobId, ':s' => $sid]);
                }
            }
        }

        return $jobId;
    }

    public static function listActive(Config $config, int $limit = 20): array
    {
        $pdo = Database::pdo($config);
        $limit = max(1, (int)$limit);
        $sql = "SELECT j.*, c.category_name, t.type_name, l.location_name
                FROM job_listings j
                INNER JOIN job_categories c ON c.category_id = j.category_id
                INNER JOIN job_types t ON t.type_id = j.type_id
                INNER JOIN locations l ON l.location_id = j.location_id
                WHERE j.is_active = 1 AND j.is_approved = 1
                ORDER BY j.created_at DESC
                LIMIT $limit";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function byRecruiter(Config $config, int $recruiterId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM job_listings WHERE recruiter_id = :r ORDER BY created_at DESC');
        $stmt->execute([':r' => $recruiterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(Config $config, int $jobId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM job_listings WHERE job_id = :id');
        $stmt->execute([':id' => $jobId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findWithJoins(Config $config, int $jobId): ?array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT j.*, c.category_name, t.type_name, l.location_name
                FROM job_listings j
                INNER JOIN job_categories c ON c.category_id = j.category_id
                INNER JOIN job_types t ON t.type_id = j.type_id
                INNER JOIN locations l ON l.location_id = j.location_id
                WHERE j.job_id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $jobId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function setApproval(Config $config, int $jobId, bool $approved): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('UPDATE job_listings SET is_approved = :a WHERE job_id = :id');
        return $stmt->execute([':a' => $approved ? 1 : 0, ':id' => $jobId]);
    }

    public static function setActive(Config $config, int $jobId, bool $active): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('UPDATE job_listings SET is_active = :a WHERE job_id = :id');
        return $stmt->execute([':a' => $active ? 1 : 0, ':id' => $jobId]);
    }

    public static function delete(Config $config, int $jobId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('DELETE FROM job_listings WHERE job_id = :id');
        return $stmt->execute([':id' => $jobId]);
    }
}
