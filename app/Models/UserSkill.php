<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class UserSkill
{
    public static function getNamesByUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT s.skill_name FROM user_skills us JOIN skills s ON s.skill_id = us.skill_id WHERE us.user_id = :u ORDER BY s.skill_name');
        $stmt->execute([':u' => $userId]);
        return array_map(static fn($r) => $r['skill_name'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Sync user skills to the provided list of names (case-sensitive by default).
     * Creates missing skills via Skill::ensure.
     */
    public static function syncNames(Config $config, int $userId, array $skillNames): void
    {
        $names = array_values(array_unique(array_filter(array_map(static fn($n) => trim((string)$n), $skillNames), static fn($n) => $n !== '')));
        $pdo = Database::pdo($config);
        $pdo->beginTransaction();
        try {
            // Fetch current mapping
            $stmt = $pdo->prepare('SELECT s.skill_name FROM user_skills us JOIN skills s ON s.skill_id = us.skill_id WHERE us.user_id = :u');
            $stmt->execute([':u' => $userId]);
            $current = array_map(static fn($r) => $r['skill_name'], $stmt->fetchAll(PDO::FETCH_ASSOC));

            $toAdd = array_diff($names, $current);
            $toRemove = array_diff($current, $names);

            // Removes
            if ($toRemove) {
                $in = implode(',', array_fill(0, count($toRemove), '?'));
                $params = array_values($toRemove);
                // Map names -> ids
                $stmt = $pdo->prepare('SELECT skill_id, skill_name FROM skills WHERE skill_name IN (' . $in . ')');
                $stmt->execute($params);
                $ids = array_map(static fn($r) => (int)$r['skill_id'], $stmt->fetchAll(PDO::FETCH_ASSOC));
                if ($ids) {
                    $in2 = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare('DELETE FROM user_skills WHERE user_id = ? AND skill_id IN (' . $in2 . ')');
                    $stmt->execute(array_merge([$userId], $ids));
                }
            }

            // Adds
            foreach ($toAdd as $name) {
                $skillId = Skill::ensure($config, $name, null);
                // insert if not exists
                $stmt = $pdo->prepare('SELECT 1 FROM user_skills WHERE user_id = :u AND skill_id = :s');
                $stmt->execute([':u' => $userId, ':s' => $skillId]);
                if (!$stmt->fetchColumn()) {
                    $stmt = $pdo->prepare('INSERT INTO user_skills (user_id, skill_id, proficiency_level) VALUES (:u, :s, NULL)');
                    $stmt->execute([':u' => $userId, ':s' => $skillId]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
