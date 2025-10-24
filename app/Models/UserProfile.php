<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class UserProfile
{
    public static function findByUserId(Config $config, int $userId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM user_profiles WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function upsert(Config $config, int $userId, array $data): bool
    {
        $pdo = Database::pdo($config);
        // Insert or update by unique user_id
    $sql = 'INSERT INTO user_profiles (user_id, first_name, last_name, bio, portfolio_url, linkedin_url, profile_picture_url, privacy_settings, phone, address, region, resume_url)
        VALUES (:user_id, :first_name, :last_name, :bio, :portfolio, :linkedin, :picture, :privacy, :phone, :address, :region, :resume)
        ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), last_name=VALUES(last_name), bio=VALUES(bio), portfolio_url=VALUES(portfolio_url), linkedin_url=VALUES(linkedin_url), profile_picture_url=VALUES(profile_picture_url), privacy_settings=VALUES(privacy_settings), phone=VALUES(phone), address=VALUES(address), region=VALUES(region), resume_url=VALUES(resume_url), updated_at=CURRENT_TIMESTAMP';
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':first_name' => $data['first_name'] ?? '',
            ':last_name' => $data['last_name'] ?? '',
            ':bio' => $data['bio'] ?? null,
            ':portfolio' => $data['portfolio_url'] ?? null,
            ':linkedin' => $data['linkedin_url'] ?? null,
            ':picture' => $data['profile_picture_url'] ?? null,
            ':privacy' => $data['privacy_settings'] ?? json_encode(['contact_visible' => false, 'cgpa_visible' => false], JSON_THROW_ON_ERROR),
            ':phone' => $data['phone'] ?? null,
            ':address' => $data['address'] ?? null,
            ':region' => $data['region'] ?? null,
            ':resume' => $data['resume_url'] ?? null,
        ]);
    }
}
