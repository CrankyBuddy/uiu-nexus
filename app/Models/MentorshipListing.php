<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class MentorshipListing
{
    public static function create(Config $config, int $alumniId, int $expertiseId, string $description, int $minCoinBid, int $maxSlots, int $sessionDuration, bool $isActive = true, ?float $minCgpa = null, ?int $minProjects = null, ?int $minWalletCoins = null): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO mentorship_listings (alumni_id, expertise_id, description, min_coin_bid, max_slots, session_duration, available_times, min_cgpa, min_projects, min_wallet_coins, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        // available_times left null by default from this path
        $stmt->execute([$alumniId, $expertiseId, $description, $minCoinBid, $maxSlots, $sessionDuration, null, $minCgpa, $minProjects, $minWalletCoins, $isActive ? 1 : 0]);
        return (int)$pdo->lastInsertId();
    }

    public static function byAlumni(Config $config, int $alumniId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT ml.*, ea.area_name FROM mentorship_listings ml JOIN expertise_areas ea ON ea.expertise_id = ml.expertise_id WHERE ml.alumni_id = ? ORDER BY ml.created_at DESC');
        $stmt->execute([$alumniId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function activeAll(Config $config): array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT ml.*, ea.area_name, a.alumni_id, up.first_name, up.last_name FROM mentorship_listings ml 
                JOIN expertise_areas ea ON ea.expertise_id = ml.expertise_id
                JOIN alumni a ON a.alumni_id = ml.alumni_id
                JOIN users u ON u.user_id = a.user_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE ml.is_active = 1 ORDER BY ml.created_at DESC';
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(Config $config, int $listingId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM mentorship_listings WHERE listing_id = ?');
        $stmt->execute([$listingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function incrementSlot(Config $config, int $listingId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('UPDATE mentorship_listings SET current_slots = current_slots + 1 WHERE listing_id = ? AND current_slots < max_slots');
        $stmt->execute([$listingId]);
        return $stmt->rowCount() === 1;
    }

    public static function decrementSlot(Config $config, int $listingId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('UPDATE mentorship_listings SET current_slots = GREATEST(current_slots - 1, 0) WHERE listing_id = ?');
        $stmt->execute([$listingId]);
        return $stmt->rowCount() === 1;
    }

    public static function update(Config $config, int $listingId, array $data): bool
    {
        $pdo = Database::pdo($config);
        $sql = 'UPDATE mentorship_listings SET expertise_id = :eid, description = :d, min_coin_bid = :minb, max_slots = :maxs, session_duration = :dur, is_active = :act,
                    min_cgpa = :mcgpa, min_projects = :mproj, min_wallet_coins = :mcoin
                WHERE listing_id = :id';
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':eid' => (int)$data['expertise_id'],
            ':d' => (string)$data['description'],
            ':minb' => (int)$data['min_coin_bid'],
            ':maxs' => (int)$data['max_slots'],
            ':dur' => (int)$data['session_duration'],
            ':act' => !empty($data['is_active']) ? 1 : 0,
            ':mcgpa' => $data['min_cgpa'] ?? null,
            ':mproj' => $data['min_projects'] ?? null,
            ':mcoin' => $data['min_wallet_coins'] ?? null,
            ':id' => (int)$listingId,
        ]);
    }

    public static function hasActiveRequests(Config $config, int $listingId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mentorship_requests WHERE listing_id = :id AND status IN ('pending','accepted')");
        $stmt->execute([':id' => $listingId]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    public static function delete(Config $config, int $listingId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('DELETE FROM mentorship_listings WHERE listing_id = ?');
        $stmt->execute([$listingId]);
        return $stmt->rowCount() === 1;
    }
}
