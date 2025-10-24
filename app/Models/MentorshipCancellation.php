<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class MentorshipCancellation
{
    public static function request(Config $config, int $requestId, int $userId, string $role, ?string $reason = null): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO mentorship_cancellation_requests (request_id, requested_by_user_id, requested_by_role, reason) VALUES (:r, :u, :role, :reason)');
        $stmt->execute([':r' => $requestId, ':u' => $userId, ':role' => $role, ':reason' => $reason !== '' ? $reason : null]);
        return (int)$pdo->lastInsertId();
    }

    public static function existsPendingForRequest(Config $config, int $requestId): bool
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare("SELECT COUNT(*) FROM mentorship_cancellation_requests WHERE request_id = :r AND status = 'pending'");
        $st->execute([':r' => $requestId]);
        return ((int)$st->fetchColumn()) > 0;
    }

    public static function listPending(Config $config, int $limit = 200): array
    {
        $pdo = Database::pdo($config);
        $limit = max(1, $limit);
    $sql = "SELECT mcr.*, mr.student_id, mr.listing_id, mr.end_date, mr.status AS request_status, u.email AS requester_email
        FROM mentorship_cancellation_requests mcr
        JOIN mentorship_requests mr ON mr.request_id = mcr.request_id
        JOIN users u ON u.user_id = mcr.requested_by_user_id
                WHERE mcr.status = 'pending'
                ORDER BY mcr.created_at DESC
                LIMIT $limit";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function approve(Config $config, int $cancellationId, int $adminUserId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare("UPDATE mentorship_cancellation_requests SET status = 'approved', decided_by = :by, decided_at = CURRENT_TIMESTAMP WHERE cancellation_id = :id AND status = 'pending'");
        $stmt->execute([':by' => $adminUserId, ':id' => $cancellationId]);
        return $stmt->rowCount() === 1;
    }

    public static function reject(Config $config, int $cancellationId, int $adminUserId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare("UPDATE mentorship_cancellation_requests SET status = 'rejected', decided_by = :by, decided_at = CURRENT_TIMESTAMP WHERE cancellation_id = :id AND status = 'pending'");
        $stmt->execute([':by' => $adminUserId, ':id' => $cancellationId]);
        return $stmt->rowCount() === 1;
    }

    public static function find(Config $config, int $cancellationId): ?array
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare('SELECT * FROM mentorship_cancellation_requests WHERE cancellation_id = :id');
        $st->execute([':id' => $cancellationId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
