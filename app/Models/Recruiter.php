<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Recruiter
{
    public static function findByUserId(Config $config, int $userId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM recruiters WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function upsert(Config $config, int $userId, array $data): bool
    {
        $pdo = Database::pdo($config);
        $allowedSizes = ['1-10','11-50','51-200','201-500','500+'];
        $size = $data['company_size'] ?? null;
        // company_name is NOT NULL in schema; default to empty string if not provided
        $companyName = trim((string)($data['company_name'] ?? ''));
        if ($size !== null && !in_array($size, $allowedSizes, true)) {
            $size = null; // avoid enum constraint error
        }
    $sql = 'INSERT INTO recruiters (user_id, company_name, company_email, company_description, company_website, company_logo_url, company_size, industry, hr_contact_name, hr_contact_email, company_location, hr_contact_role, hr_contact_phone, career_page_url, company_linkedin, social_links)
        VALUES (:uid, :name, :cemail, :desc, :site, :logo, :size, :industry, :hrname, :hremail, :location, :hrrole, :hrphone, :career, :clinkedin, :social)
        ON DUPLICATE KEY UPDATE company_name=VALUES(company_name), company_email=VALUES(company_email), company_description=VALUES(company_description), company_website=VALUES(company_website), company_logo_url=VALUES(company_logo_url), company_size=VALUES(company_size), industry=VALUES(industry), hr_contact_name=VALUES(hr_contact_name), hr_contact_email=VALUES(hr_contact_email), company_location=VALUES(company_location), hr_contact_role=VALUES(hr_contact_role), hr_contact_phone=VALUES(hr_contact_phone), career_page_url=VALUES(career_page_url), company_linkedin=VALUES(company_linkedin), social_links=VALUES(social_links)';
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':uid' => $userId,
            ':name' => $companyName,
            ':cemail' => $data['company_email'] ?? null,
            ':desc' => $data['company_description'] ?? null,
            ':site' => $data['company_website'] ?? null,
            ':logo' => $data['company_logo_url'] ?? null,
            ':size' => $size,
            ':industry' => $data['industry'] ?? null,
            ':hrname' => $data['hr_contact_name'] ?? null,
            ':hremail' => $data['hr_contact_email'] ?? null,
            ':location' => $data['company_location'] ?? null,
            ':hrrole' => $data['hr_contact_role'] ?? null,
            ':hrphone' => $data['hr_contact_phone'] ?? null,
            ':career' => $data['career_page_url'] ?? null,
            ':clinkedin' => $data['company_linkedin'] ?? null,
            ':social' => isset($data['social_links']) ? json_encode($data['social_links']) : null,
        ]);
    }
}
