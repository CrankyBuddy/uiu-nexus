<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class AlumniFocusSkill
{
    public static function syncSkillNames(Config $config, int $userId, array $skillNames): void
    {
        $pdo = Database::pdo($config);
        $skillNames = array_values(array_unique(array_filter(array_map('trim', $skillNames), static fn($n) => $n !== '')));
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT s.skill_name FROM alumni_focus_skills afs JOIN skills s ON s.skill_id = afs.skill_id WHERE afs.user_id = :u');
            $stmt->execute([':u'=>$userId]);
            $current = array_map(static fn($r)=>$r['skill_name'], $stmt->fetchAll(PDO::FETCH_ASSOC));
            $toAdd = array_diff($skillNames, $current);
            $toRemove = array_diff($current, $skillNames);
            if ($toRemove) {
                $in = implode(',', array_fill(0, count($toRemove), '?'));
                $stmt = $pdo->prepare('SELECT skill_id FROM skills WHERE skill_name IN (' . $in . ')');
                $stmt->execute(array_values($toRemove));
                $ids = array_map(static fn($r)=>(int)$r['skill_id'], $stmt->fetchAll(PDO::FETCH_ASSOC));
                if ($ids) {
                    $in2 = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare('DELETE FROM alumni_focus_skills WHERE user_id = ? AND skill_id IN (' . $in2 . ')');
                    $stmt->execute(array_merge([$userId], $ids));
                }
            }
            foreach ($toAdd as $name) {
                $sid = Skill::ensure($config, $name, null);
                $stmt = $pdo->prepare('INSERT IGNORE INTO alumni_focus_skills (user_id, skill_id) VALUES (:u,:s)');
                $stmt->execute([':u'=>$userId, ':s'=>$sid]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
