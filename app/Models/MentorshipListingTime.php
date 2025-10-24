<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class MentorshipListingTime
{
    public static function upsertForListing(Config $config, int $listingId, array $slots): void
    {
        $pdo = Database::pdo($config);
        $pdo->prepare('DELETE FROM mentorship_listing_times WHERE listing_id = ?')->execute([$listingId]);
        if (!$slots) return;
        $stmt = $pdo->prepare('INSERT INTO mentorship_listing_times (listing_id, day_of_week, start_time, end_time, timezone) VALUES (?,?,?,?,?)');
        foreach ($slots as $s) {
            $dow = (int)($s['day_of_week'] ?? 0);
            $start = $s['start_time'] ?? null;
            $end = $s['end_time'] ?? null;
            $tz = $s['timezone'] ?? null;
            if ($dow < 1 || $dow > 7 || !$start || !$end) continue;
            $stmt->execute([$listingId, $dow, $start, $end, $tz]);
        }
    }

    public static function forListing(Config $config, int $listingId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM mentorship_listing_times WHERE listing_id = ? ORDER BY day_of_week, start_time');
        $stmt->execute([$listingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
