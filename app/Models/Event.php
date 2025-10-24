<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Event
{
    public static function create(
        Config $config,
        string $title,
        string $description,
        string $eventType,
        string $eventDateTime,
        ?string $location,
        ?string $venueDetails,
        ?int $organizerUserId,
        ?int $maxParticipants,
        bool $isActive
    ): int {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO events (title, description, event_type, event_date, location, venue_details, organizer_id, max_participants, is_active) VALUES (:t,:d,:ty,:dt,:loc,:vd,:org,:maxp,:act)');
        $stmt->execute([
            ':t' => $title,
            ':d' => $description,
            ':ty' => $eventType,
            ':dt' => $eventDateTime,
            ':loc' => $location,
            ':vd' => $venueDetails,
            ':org' => $organizerUserId,
            ':maxp' => $maxParticipants,
            ':act' => $isActive ? 1 : 0,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function listUpcoming(Config $config, int $limit = 20): array
    {
        $pdo = Database::pdo($config);
        $limit = max(1, (int)$limit);
        $sql = "SELECT * FROM events WHERE is_active = 1 AND event_date >= NOW() ORDER BY event_date ASC LIMIT $limit";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function find(Config $config, int $eventId): ?array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM events WHERE event_id = :id');
        $stmt->execute([':id' => $eventId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    //How many have registered

    public static function countRegistrations(Config $config, int $eventId): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM event_registrations WHERE event_id = :e');
        $stmt->execute([':e' => $eventId]);
        return (int)($stmt->fetchColumn() ?: 0);
    }
// checking Registration
    public static function isRegistered(Config $config, int $eventId, int $userId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT 1 FROM event_registrations WHERE event_id = :e AND user_id = :u LIMIT 1');
        $stmt->execute([':e' => $eventId, ':u' => $userId]);
        return (bool)$stmt->fetch(PDO::FETCH_NUM);
    }

    public static function register(Config $config, int $eventId, int $userId): bool
    {
        $pdo = Database::pdo($config);
        // Check capacity if set
        $ev = self::find($config, $eventId);
        if (!$ev || !(bool)$ev['is_active']) return false;
        if (!empty($ev['max_participants'])) {
            $count = self::countRegistrations($config, $eventId);
            if ($count >= (int)$ev['max_participants']) return false;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO event_registrations (event_id, user_id) VALUES (:e,:u)');
            return $stmt->execute([':e' => $eventId, ':u' => $userId]);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
