<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class MentorshipRequest
{
    public static function hasAnyForListing(Config $config, int $studentId, int $listingId): bool
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare('SELECT COUNT(*) FROM mentorship_requests WHERE student_id = :sid AND listing_id = :lid');
        $st->execute([':sid' => $studentId, ':lid' => $listingId]);
        return ((int)$st->fetchColumn()) > 0;
    }
    public static function hasPendingForListing(Config $config, int $studentId, int $listingId): bool
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare('SELECT COUNT(*) FROM mentorship_requests WHERE student_id = :sid AND listing_id = :lid AND status = "pending"');
        $st->execute([':sid' => $studentId, ':lid' => $listingId]);
        return ((int)$st->fetchColumn()) > 0;
    }

    public static function hasActiveWithAlumni(Config $config, int $studentId, int $alumniId): bool
    {
        $pdo = Database::pdo($config);
    // Block if there is an accepted request whose window is still active
    // OR if the window ended less than 1 calendar month ago
    $sql = 'SELECT COUNT(*) FROM mentorship_requests r
        JOIN mentorship_listings l ON l.listing_id = r.listing_id
        WHERE r.student_id = :sid AND l.alumni_id = :aid AND r.status = "accepted"
          AND (
            r.end_date IS NULL
            OR r.end_date >= CURRENT_DATE()
            OR DATE_ADD(r.end_date, INTERVAL 1 MONTH) > CURRENT_DATE()
          )';
        $st = $pdo->prepare($sql);
        $st->execute([':sid' => $studentId, ':aid' => $alumniId]);
        return ((int)$st->fetchColumn()) > 0;
    }

    
    public static function create(Config $config, int $studentId, int $listingId, int $bidAmount, string $message, bool $isFree): int
    {
        $pdo = Database::pdo($config);
        // Priority = free_baseline + bid_amount * weight
        $weight = \Nexus\Helpers\Setting::get($config, 'app.coins.priority_weight', 1, 'integer');
        $freeBase = \Nexus\Helpers\Setting::get($config, 'app.coins.free_priority_baseline', 1, 'integer');
        $priority = $isFree ? $freeBase : max(1, ($bidAmount * $weight));
        $stmt = $pdo->prepare('INSERT INTO mentorship_requests (student_id, listing_id, bid_amount, priority_score, message, is_free_request) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$studentId, $listingId, $bidAmount, $priority, $message, $isFree ? 1 : 0]);
        return (int)$pdo->lastInsertId();
    }

    public static function find(Config $config, int $requestId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM mentorship_requests WHERE request_id = ?');
        $stmt->execute([$requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findWithUsers(Config $config, int $requestId): ?array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT r.*, s.student_id, s.user_id AS student_user_id, s.cgpa AS student_cgpa, l.listing_id, l.alumni_id, a.user_id AS alumni_user_id
                FROM mentorship_requests r
                JOIN students s ON s.student_id = r.student_id
                JOIN mentorship_listings l ON l.listing_id = r.listing_id
                JOIN alumni a ON a.alumni_id = l.alumni_id
                WHERE r.request_id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Check if a request can be accepted under the 1-month pair window.
     * Disallows accepting if the same (student, alumni) pair has an active accepted request
     * whose end_date is in the future (or not set yet for legacy rows).
     */
    public static function canAccept(Config $config, int $studentId, int $alumniId): bool
    {
        $pdo = Database::pdo($config);
    $sql = 'SELECT COUNT(*) FROM mentorship_requests r
        JOIN mentorship_listings l ON l.listing_id = r.listing_id
        WHERE r.student_id = :sid AND l.alumni_id = :aid
          AND r.status = "accepted"
          AND (
            r.end_date IS NULL
            OR r.end_date >= CURRENT_DATE()
            OR DATE_ADD(r.end_date, INTERVAL 1 MONTH) > CURRENT_DATE()
          )';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':sid' => $studentId, ':aid' => $alumniId]);
        return ((int)$stmt->fetchColumn()) === 0;
    }

    /**
     * Accept request and set window start/end dates.
     */
    public static function accept(Config $config, int $requestId, ?string $startDate = null, ?string $endDate = null): void
    {
        $pdo = Database::pdo($config);
        // Default: today -> +1 month
        $start = $startDate ?: date('Y-m-d');
        $end = $endDate ?: date('Y-m-d', strtotime('+1 month'));
        $stmt = $pdo->prepare('UPDATE mentorship_requests
            SET status = "accepted",
                responded_at = CURRENT_TIMESTAMP,
                start_date = :start,
                end_date = :end
            WHERE request_id = :id');
        $stmt->execute([':id' => $requestId, ':start' => $start, ':end' => $end]);
    }

    public static function listForListing(Config $config, int $listingId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT mr.*, s.student_id, s.user_id AS student_user_id, s.cgpa AS student_cgpa, up.first_name, up.last_name FROM mentorship_requests mr
            JOIN students s ON s.student_id = mr.student_id
            JOIN users u ON u.user_id = s.user_id
            LEFT JOIN user_profiles up ON up.user_id = u.user_id
            WHERE mr.listing_id = ? ORDER BY mr.priority_score DESC, mr.created_at DESC');
        $stmt->execute([$listingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function reserve(Config $config, int $requestId, int $minutes = 10): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('UPDATE mentorship_requests SET reserved_until = DATE_ADD(NOW(), INTERVAL :m MINUTE) WHERE request_id = :id');
        $stmt->execute([':id' => $requestId, ':m' => max(1, $minutes)]);
        return $stmt->rowCount() === 1;
    }

    public static function clearReservation(Config $config, int $requestId): void
    {
        $pdo = Database::pdo($config);
        $pdo->prepare('UPDATE mentorship_requests SET reserved_until = NULL WHERE request_id = ?')->execute([$requestId]);
    }

    public static function reserveForMentor(Config $config, int $requestId, int $alumniId, int $minutes): bool
    {
        $pdo = Database::pdo($config);
        // Ensure this request belongs to a listing of this alumni and is pending
        $sql = 'UPDATE mentorship_requests r
                JOIN mentorship_listings l ON l.listing_id = r.listing_id
                SET r.reserved_until = DATE_ADD(NOW(), INTERVAL :m MINUTE)
                WHERE r.request_id = :id AND l.alumni_id = :aid AND r.status = "pending"';
        $st = $pdo->prepare($sql);
        $st->execute([':m' => max(1, $minutes), ':id' => $requestId, ':aid' => $alumniId]);
        return $st->rowCount() === 1;
    }

    public static function releaseReservation(Config $config, int $requestId, int $alumniId): bool
    {
        $pdo = Database::pdo($config);
        $sql = 'UPDATE mentorship_requests r
                JOIN mentorship_listings l ON l.listing_id = r.listing_id
                SET r.reserved_until = NULL
                WHERE r.request_id = :id AND l.alumni_id = :aid';
        $st = $pdo->prepare($sql);
        $st->execute([':id' => $requestId, ':aid' => $alumniId]);
        return $st->rowCount() === 1;
    }

    public static function extendReservation(Config $config, int $requestId, int $alumniId, int $minutes, int $maxExtensions): bool
    {
        $pdo = Database::pdo($config);
        $sql = 'UPDATE mentorship_requests r
                JOIN mentorship_listings l ON l.listing_id = r.listing_id
                SET r.reserved_until = DATE_ADD(COALESCE(r.reserved_until, NOW()), INTERVAL :m MINUTE),
                    r.reservation_extensions = r.reservation_extensions + 1
                WHERE r.request_id = :id AND l.alumni_id = :aid AND r.reservation_extensions < :maxext';
        $st = $pdo->prepare($sql);
        $st->execute([':m' => max(1, $minutes), ':id' => $requestId, ':aid' => $alumniId, ':maxext' => max(0, $maxExtensions)]);
        return $st->rowCount() === 1;
    }

    public static function forStudent(Config $config, int $studentId): array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT mr.*, ea.area_name FROM mentorship_requests mr
                JOIN mentorship_listings ml ON ml.listing_id = mr.listing_id
                JOIN expertise_areas ea ON ea.expertise_id = ml.expertise_id
                WHERE mr.student_id = ? ORDER BY mr.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function updateStatus(Config $config, int $requestId, string $status): void
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('UPDATE mentorship_requests SET status = ?, responded_at = CASE WHEN ? IN ("accepted","declined") THEN CURRENT_TIMESTAMP ELSE responded_at END WHERE request_id = ?');
        $stmt->execute([$status, $status, $requestId]);
    }

    public static function markCompleted(Config $config, int $requestId): void
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare("UPDATE mentorship_requests SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE request_id = :id AND status = 'accepted'");
        $stmt->execute([':id' => $requestId]);
    }
}
