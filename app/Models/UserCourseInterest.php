<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class UserCourseInterest
{
    public static function listByUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
        // We now only track 'struggling' courses; interest_type is ignored if present
        $stmt = $pdo->prepare('SELECT c.name FROM user_course_interests uci JOIN courses c ON c.course_id = uci.course_id WHERE uci.user_id = :u ORDER BY c.name ASC');
        $stmt->execute([':u' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = ['interested' => [], 'struggling' => []];
        foreach ($rows as $r) {
            $name = (string)($r['name'] ?? '');
            if ($name === '') continue;
            $out['struggling'][] = $name;
        }
        return $out;
    }
    public static function sync(Config $config, int $userId, array $coursesInterested, array $coursesStruggling): void
    {
        $pdo = Database::pdo($config);
        $pdo->beginTransaction();
        try {
            $getNames = static function(array $pairs): array {
                $out = [];
                foreach ($pairs as $name) {
                    $n = trim((string)$name);
                    if ($n !== '') $out[] = $n;
                }
                return array_values(array_unique($out));
            };
            // We ignore $coursesInterested and only track struggling courses
            $struggling = $getNames($coursesStruggling);

            // current struggling set (interest_type ignored)
            $stmt = $pdo->prepare('SELECT c.name FROM user_course_interests uci JOIN courses c ON c.course_id = uci.course_id WHERE uci.user_id = :u');
            $stmt->execute([':u' => $userId]);
            $current = array_map(static fn($r) => (string)$r['name'], $stmt->fetchAll(PDO::FETCH_ASSOC));

            $toRemove = array_diff($current, $struggling);
            $toAdd = array_diff($struggling, $current);

            // Removes
            if ($toRemove) {
                $in = implode(',', array_fill(0, count($toRemove), '?'));
                $stmt = $pdo->prepare('SELECT course_id FROM courses WHERE name IN (' . $in . ')');
                $stmt->execute(array_values($toRemove));
                $ids = array_map(static fn($r) => (int)$r['course_id'], $stmt->fetchAll(PDO::FETCH_ASSOC));
                foreach ($ids as $cid) {
                    $stmt = $pdo->prepare('DELETE FROM user_course_interests WHERE user_id = :u AND course_id = :c');
                    $stmt->execute([':u' => $userId, ':c' => $cid]);
                }
            }

            // Adds
            foreach ($toAdd as $name) {
                $stmt = $pdo->prepare('SELECT course_id FROM courses WHERE name = :n LIMIT 1');
                $stmt->execute([':n' => $name]);
                $cid = $stmt->fetchColumn();
                if (!$cid) {
                    $stmt = $pdo->prepare('INSERT INTO courses (name) VALUES (:n)');
                    $stmt->execute([':n' => $name]);
                    $cid = (int)$pdo->lastInsertId();
                }
                // insert mapping if not exists
                $stmt = $pdo->prepare('SELECT 1 FROM user_course_interests WHERE user_id = :u AND course_id = :c');
                $stmt->execute([':u' => $userId, ':c' => $cid]);
                if (!$stmt->fetchColumn()) {
                    // interest_type column is ignored; if it exists, default should allow NULL
                    try {
                        $stmt = $pdo->prepare('INSERT INTO user_course_interests (user_id, course_id) VALUES (:u, :c)');
                        $stmt->execute([':u' => $userId, ':c' => $cid]);
                    } catch (\Throwable $e) {
                        // Fallback for schemas still requiring interest_type
                        $stmt = $pdo->prepare('INSERT INTO user_course_interests (user_id, course_id, interest_type) VALUES (:u, :c, :t)');
                        $stmt->execute([':u' => $userId, ':c' => $cid, ':t' => 'struggling']);
                    }
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
