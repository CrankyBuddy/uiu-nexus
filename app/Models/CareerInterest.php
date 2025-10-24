<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class CareerInterest
{
    public static function listNamesByUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT ci.name FROM user_career_interests uci JOIN career_interests ci ON ci.interest_id = uci.interest_id WHERE uci.user_id = :u ORDER BY ci.name ASC');
        $stmt->execute([':u' => $userId]);
        return array_map(static fn($r) => (string)$r['name'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    public static function syncByNames(Config $config, int $userId, array $names): void
    {
        $pdo = Database::pdo($config);
        $pdo->beginTransaction();
        try {
            $names = array_values(array_unique(array_filter(array_map('trim', $names), static fn($n) => $n !== '')));
            // fetch current
            $stmt = $pdo->prepare('SELECT ci.name FROM user_career_interests uci JOIN career_interests ci ON ci.interest_id = uci.interest_id WHERE uci.user_id = :u');
            $stmt->execute([':u' => $userId]);
            $current = array_map(static fn($r) => $r['name'], $stmt->fetchAll(PDO::FETCH_ASSOC));
            $toAdd = array_diff($names, $current);
            $toRemove = array_diff($current, $names);

            if ($toRemove) {
                $in = implode(',', array_fill(0, count($toRemove), '?'));
                $stmt = $pdo->prepare('SELECT interest_id FROM career_interests WHERE name IN (' . $in . ')');
                $stmt->execute(array_values($toRemove));
                $ids = array_map(static fn($r) => (int)$r['interest_id'], $stmt->fetchAll(PDO::FETCH_ASSOC));
                if ($ids) {
                    $in2 = implode(',', array_fill(0, count($ids), '?'));
                    $q = 'DELETE FROM user_career_interests WHERE user_id = ? AND interest_id IN (' . $in2 . ')';
                    $stmt = $pdo->prepare($q);
                    $stmt->execute(array_merge([$userId], $ids));
                }
            }

            foreach ($toAdd as $name) {
                // ensure interest
                $stmt = $pdo->prepare('SELECT interest_id FROM career_interests WHERE name = :n LIMIT 1');
                $stmt->execute([':n' => $name]);
                $iid = $stmt->fetchColumn();
                if (!$iid) {
                    $stmt = $pdo->prepare('INSERT INTO career_interests (name) VALUES (:n)');
                    $stmt->execute([':n' => $name]);
                    $iid = (int)$pdo->lastInsertId();
                }
                // map
                $stmt = $pdo->prepare('SELECT 1 FROM user_career_interests WHERE user_id = :u AND interest_id = :i');
                $stmt->execute([':u' => $userId, ':i' => $iid]);
                if (!$stmt->fetchColumn()) {
                    $stmt = $pdo->prepare('INSERT INTO user_career_interests (user_id, interest_id) VALUES (:u, :i)');
                    $stmt->execute([':u' => $userId, ':i' => $iid]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
