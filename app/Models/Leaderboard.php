<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Leaderboard
{
    /**
     * Fetch top users from materialized leaderboards for a period and role.
     * periodType: 'weekly' | 'monthly' | 'all'
     */
    public static function top(Config $config, string $periodType, string $role, int $limit = 10): array
    {
        $pdo = Database::pdo($config);
        $limit = max(1, min(100, (int)$limit));
        if ($periodType === 'all') {
            $sql = 'SELECT l.* FROM leaderboards l INNER JOIN users u ON u.user_id = l.user_id WHERE l.period_type = :p AND u.role = :r ORDER BY l.rank ASC LIMIT ' . $limit;
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':p' => 'all', ':r' => $role]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            // Fallback: compute on the fly from current reputation snapshot if no materialized rows
            if (!$rows) {
                $q = "SELECT u.user_id, uw.reputation_score AS score\n                        FROM users u\n                        INNER JOIN user_wallets uw ON uw.user_id = u.user_id\n                        WHERE u.role = :role AND u.is_active = 1\n                        ORDER BY uw.reputation_score DESC, u.user_id ASC\n                        LIMIT $limit";
                $st2 = $pdo->prepare($q);
                $st2->execute([':role' => $role]);
                $tmp = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $rank = 1;
                $rows = array_map(function ($r) use (&$rank) {
                    return [
                        'user_id' => (int)$r['user_id'],
                        'rank' => $rank++,
                        'score' => (int)$r['score'],
                    ];
                }, $tmp);
            }
            return $rows;
        }
    $sql = 'SELECT l.* FROM leaderboards l INNER JOIN users u ON u.user_id = l.user_id WHERE l.period_type = :p AND u.role = :r AND l.period_start <= CURDATE() AND l.period_end >= CURDATE() ORDER BY l.rank ASC LIMIT ' . $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':p' => $periodType, ':r' => $role]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        // Fallback: compute on the fly from reputation_events window when no materialized rows exist
        if (!$rows) {
            [$start, $end] = self::periodWindow($periodType);
            if ($start !== null && $end !== null) {
                $q = "SELECT u.user_id, COALESCE(SUM(re.delta),0) AS score\n                        FROM users u\n                        LEFT JOIN reputation_events re ON re.user_id = u.user_id AND re.created_at BETWEEN :start AND :end\n                        WHERE u.role = :role AND u.is_active = 1 AND u.role IN ('student','alumni')\n                        GROUP BY u.user_id\n                        HAVING score <> 0\n                        ORDER BY score DESC, u.user_id ASC\n                        LIMIT $limit";
                $st2 = $pdo->prepare($q);
                $st2->execute([':role' => $role, ':start' => $start, ':end' => $end]);
                $tmp = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $rank = 1;
                $rows = array_map(function ($r) use (&$rank) {
                    return [
                        'user_id' => (int)$r['user_id'],
                        'rank' => $rank++,
                        'score' => (int)$r['score'],
                    ];
                }, $tmp);
            }
        }
        return $rows;
    }

    /**
     * Compute current weekly and monthly windows.
     * Returns ['weekly' => [start,end], 'monthly' => [start,end]]
     */
    public static function currentWindows(): array
    {
        $today = new \DateTimeImmutable('today');
        $dow = (int)$today->format('N');
        $weekStart = $today->modify('-' . ($dow - 1) . ' days');
        $weekEnd = $weekStart->modify('+6 days');
        $monthStart = $today->modify('first day of this month');
        $monthEnd = $today->modify('last day of this month');
        return [
            'weekly' => [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')],
            'monthly' => [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')],
        ];
    }

    /**
     * Compute a [startDateTime, endDateTime] window for periodType.
     * Returns [null, null] for 'all'. Dates are in 'Y-m-d H:i:s'.
     */
    public static function periodWindow(string $periodType): array
    {
        $periodType = strtolower($periodType);
        if ($periodType === 'all') { return [null, null]; }
        $today = new \DateTimeImmutable('today');
        if ($periodType === 'daily') {
            $start = $today->format('Y-m-d') . ' 00:00:00';
            $end = $today->format('Y-m-d') . ' 23:59:59';
            return [$start, $end];
        }
        if ($periodType === 'weekly') {
            $dow = (int)$today->format('N');
            $weekStart = $today->modify('-' . ($dow - 1) . ' days');
            $weekEnd = $weekStart->modify('+6 days');
            return [$weekStart->format('Y-m-d') . ' 00:00:00', $weekEnd->format('Y-m-d') . ' 23:59:59'];
        }
        if ($periodType === 'monthly') {
            $monthStart = $today->modify('first day of this month');
            $monthEnd = $today->modify('last day of this month');
            return [$monthStart->format('Y-m-d') . ' 00:00:00', $monthEnd->format('Y-m-d') . ' 23:59:59'];
        }
        // default fallback: all
        return [null, null];
    }

    /**
     * Top coins by period and role, derived from transactions join transaction_types.
     * $kind: 'earned' or 'spent'.
     */
    public static function topCoins(Config $config, string $periodType, string $role, string $kind, int $limit = 10): array
    {
        $pdo = Database::pdo($config);
        $limit = max(1, min(100, (int)$limit));
        $isEarning = strtolower($kind) === 'earned' ? 1 : 0;
        [$start, $end] = self::periodWindow($periodType);
        $params = [
            ':role' => $role,
            ':ie' => $isEarning,
        ];
        $dateFilter = '';
        if ($start !== null && $end !== null) {
            $dateFilter = ' AND tr.created_at BETWEEN :start AND :end ';
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
    $sql = "SELECT u.user_id, COALESCE(SUM(tr.amount),0) AS score
                FROM users u
                INNER JOIN transactions tr ON tr.user_id = u.user_id
                INNER JOIN transaction_types tt ON tt.type_id = tr.type_id
        WHERE u.role = :role AND u.is_active = 1 AND tt.is_earning = :ie $dateFilter
                GROUP BY u.user_id
                ORDER BY score DESC, u.user_id ASC
                LIMIT $limit";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Paginated materialized leaderboard for reputation.
     * Returns ['rows'=>array, 'hasNext'=>bool, 'page'=>int, 'perPage'=>int]
     */
    public static function page(Config $config, string $periodType, string $role, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo($config);
        $page = max(1, (int)$page);
        $perPage = max(5, min(100, (int)$perPage));
        $offset = ($page - 1) * $perPage;

        if ($periodType === 'all') {
            $sql = 'SELECT u.user_id, l.rank, l.score, COALESCE(NULLIF(TRIM(CONCAT(up.first_name, " ", up.last_name)), ""), u.email) AS name
                    FROM leaderboards l
                    INNER JOIN users u ON u.user_id = l.user_id
                    LEFT JOIN user_profiles up ON up.user_id = u.user_id
                    WHERE l.period_type = :p AND u.role = :r
                    ORDER BY l.rank ASC
                    LIMIT ' . ($perPage + 1) . ' OFFSET ' . $offset;
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':p' => 'all', ':r' => $role]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (!$rows) {
                // Fallback: derive from current reputation snapshot
                $q = "SELECT u.user_id, uw.reputation_score AS score, COALESCE(NULLIF(TRIM(CONCAT(up.first_name, ' ', up.last_name)), ''), u.email) AS name\n                        FROM users u\n                        INNER JOIN user_wallets uw ON uw.user_id = u.user_id\n                        LEFT JOIN user_profiles up ON up.user_id = u.user_id\n                        WHERE u.role = :role AND u.is_active = 1\n                        ORDER BY uw.reputation_score DESC, u.user_id ASC\n                        LIMIT " . ($perPage + 1) . ' OFFSET ' . $offset;
                $st2 = $pdo->prepare($q);
                $st2->execute([':role' => $role]);
                $tmp = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $startRank = $offset + 1;
                $rows = array_map(function ($r) use (&$startRank) {
                    return [
                        'user_id' => (int)$r['user_id'],
                        'rank' => $startRank++,
                        'score' => (int)$r['score'],
                        'name' => (string)($r['name'] ?? ''),
                    ];
                }, $tmp);
            }
        } else {
            $windows = self::currentWindows();
            [$ps, $pe] = $windows[$periodType] ?? [null, null];
            if ($ps === null || $pe === null) { return ['rows' => [], 'hasNext' => false, 'page' => $page, 'perPage' => $perPage]; }
            $sql = 'SELECT u.user_id, l.rank, l.score, COALESCE(NULLIF(TRIM(CONCAT(up.first_name, " ", up.last_name)), ""), u.email) AS name
                    FROM leaderboards l
                    INNER JOIN users u ON u.user_id = l.user_id
                    LEFT JOIN user_profiles up ON up.user_id = u.user_id
                    WHERE l.period_type = :p AND u.role = :r AND l.period_start = :ps AND l.period_end = :pe
                    ORDER BY l.rank ASC
                    LIMIT ' . ($perPage + 1) . ' OFFSET ' . $offset;
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':p' => $periodType, ':r' => $role, ':ps' => $ps, ':pe' => $pe]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (!$rows) {
                // Fallback: compute from reputation_events in the window
                [$start, $end] = self::periodWindow($periodType);
                if ($start !== null && $end !== null) {
                    $q = "SELECT u.user_id, COALESCE(SUM(re.delta),0) AS score, COALESCE(NULLIF(TRIM(CONCAT(up.first_name, ' ', up.last_name)), ''), u.email) AS name\n                            FROM users u\n                            LEFT JOIN reputation_events re ON re.user_id = u.user_id AND re.created_at BETWEEN :start AND :end\n                            LEFT JOIN user_profiles up ON up.user_id = u.user_id\n                            WHERE u.role = :role AND u.is_active = 1 AND u.role IN ('student','alumni')\n                            GROUP BY u.user_id\n                            HAVING score <> 0\n                            ORDER BY score DESC, u.user_id ASC\n                            LIMIT " . ($perPage + 1) . ' OFFSET ' . $offset;
                    $st2 = $pdo->prepare($q);
                    $st2->execute([':role' => $role, ':start' => $start, ':end' => $end]);
                    $tmp = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    $startRank = $offset + 1;
                    $rows = array_map(function ($r) use (&$startRank) {
                        return [
                            'user_id' => (int)$r['user_id'],
                            'rank' => $startRank++,
                            'score' => (int)$r['score'],
                            'name' => (string)($r['name'] ?? ''),
                        ];
                    }, $tmp);
                }
            }
        }
        $hasNext = count($rows) > $perPage;
        if ($hasNext) { array_pop($rows); }
        return ['rows' => $rows, 'hasNext' => $hasNext, 'page' => $page, 'perPage' => $perPage];
    }

    /**
     * Paginated coin leaderboard computed from transactions.
     * $kind: 'earned' | 'spent'
     */
    public static function pageCoins(Config $config, string $periodType, string $role, string $kind, int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::pdo($config);
        $page = max(1, (int)$page);
        $perPage = max(5, min(100, (int)$perPage));
        $offset = ($page - 1) * $perPage;
        $isEarning = strtolower($kind) === 'earned' ? 1 : 0;
        [$start, $end] = self::periodWindow($periodType);
        $params = [
            ':role' => $role,
            ':ie' => $isEarning,
        ];
        $dateFilter = '';
        if ($start !== null && $end !== null) {
            $dateFilter = ' AND tr.created_at BETWEEN :start AND :end ';
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
        $sql = "SELECT u.user_id, COALESCE(SUM(tr.amount),0) AS score, COALESCE(NULLIF(TRIM(CONCAT(up.first_name, ' ', up.last_name)), ''), u.email) AS name
                FROM users u
                INNER JOIN transactions tr ON tr.user_id = u.user_id
                INNER JOIN transaction_types tt ON tt.type_id = tr.type_id
                LEFT JOIN user_profiles up ON up.user_id = u.user_id
                WHERE u.role = :role AND u.is_active = 1 AND tt.is_earning = :ie $dateFilter
                GROUP BY u.user_id
                ORDER BY score DESC, u.user_id ASC
                LIMIT " . ($perPage + 1) . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        // Attach rank based on offset and add name passthrough
        $rank = $offset + 1;
        $rows = array_map(function ($r) use (&$rank) {
            return [
                'user_id' => (int)$r['user_id'],
                'rank' => $rank++,
                'score' => (int)$r['score'],
                'name' => (string)($r['name'] ?? ''),
            ];
        }, $rows);
        $hasNext = count($rows) > $perPage;
        if ($hasNext) { array_pop($rows); }
        return ['rows' => $rows, 'hasNext' => $hasNext, 'page' => $page, 'perPage' => $perPage];
    }
}
