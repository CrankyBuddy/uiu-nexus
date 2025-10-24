<?php
declare(strict_types=1);

namespace Nexus\Helpers;

use Nexus\Core\Config;
use Nexus\Core\Database;
use Nexus\Models\Badge;
use Nexus\Models\UserBadge;
use Nexus\Models\UserWallet;
use Nexus\Models\Notification;
use PDO;

final class Gamify
{
    public static function addReputation(Config $config, int $userId, int $delta, string $source = 'system', ?string $referenceType = null, ?int $referenceId = null): void
    {
        $pdo = Database::pdo($config);
        // Ignore reputation for recruiters
        try {
            $r = $pdo->prepare('SELECT role FROM users WHERE user_id = :u');
            $r->execute([':u' => $userId]);
            $role = (string)($r->fetchColumn() ?: '');
            if (in_array($role, ['recruiter','admin'], true)) {
                return;
            }
        } catch (\Throwable $e) { /* ignore */ }
        // Ensure wallet exists so the update always affects a row
        UserWallet::ensureExists($config, $userId);
        $stmt = $pdo->prepare('UPDATE user_wallets SET reputation_score = GREATEST(0, reputation_score + :d), updated_at = CURRENT_TIMESTAMP WHERE user_id = :u');
        $stmt->execute([':d' => $delta, ':u' => $userId]);
        // Record event for time-window leaderboards
        try {
            $ev = $pdo->prepare('INSERT INTO reputation_events (user_id, delta, source, reference_entity_type, reference_entity_id) VALUES (:u, :d, :s, :t, :rid)');
            $ev->execute([
                ':u' => $userId,
                ':d' => $delta,
                ':s' => $source,
                ':t' => $referenceType,
                ':rid' => $referenceId,
            ]);
        } catch (\Throwable $e) { /* ignore non-fatal */ }
        // Auto-award any newly eligible badges
        self::autoAwardBadges($config, $userId);
    }

    public static function autoAwardBadges(Config $config, int $userId): void
    {
        $pdo = Database::pdo($config);
        // Ensure a wallet row exists so points lookup always works
        UserWallet::ensureExists($config, $userId);

    // Ensure role-specific ladders exist in case seeds weren't applied
    try { Badge::ensureRoleLaddersSeeded($config); } catch (\Throwable $e) { /* non-fatal */ }

    // Fetch role
        $rs = $pdo->prepare('SELECT role FROM users WHERE user_id = :u');
        $rs->execute([':u' => $userId]);
        $role = strtolower((string)($rs->fetchColumn() ?: ''));
        // Skip non-participating roles
        if ($role === '' || in_array($role, ['recruiter','admin'], true)) {
            return;
        }

        // Fetch reputation points
        $ps = $pdo->prepare('SELECT reputation_score FROM user_wallets WHERE user_id = :u');
        $ps->execute([':u' => $userId]);
        $points = (int)($ps->fetchColumn() ?: 0);

        // Only award badges relevant to the user's role category
        $badges = Badge::eligibleByPointsForRole($config, $points, $role);
        foreach ($badges as $b) {
            $inserted = UserBadge::award($config, $userId, (int)$b['badge_id'], 'reputation', $points);
            if ($inserted) {
                // Send a notification to the user about the newly awarded badge
                try {
                    $title = 'New Badge Earned';
                    $msg = 'You earned the "' . (string)($b['badge_name'] ?? 'Badge') . '" badge.';
                    Notification::send($config, $userId, $title, $msg, 'badge', 'badge', (int)$b['badge_id'], '/wallet');
                } catch (\Throwable $e) { /* non-fatal */ }
            }
        }
    }
}
