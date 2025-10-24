<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Badge
{
    public static function eligibleByPoints(Config $config, int $points): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT * FROM badges WHERE required_points <= :p ORDER BY required_points ASC');
        $stmt->execute([':p' => $points]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Return badges eligible for a user role based on reputation points.
     * Only badges under the role's category are returned.
     *
     * Role mapping:
     *  - student => category_name 'Students'
     *  - alumni  => category_name 'Alumni'
     * Other roles return an empty set (no auto-awards).
     */
    public static function eligibleByPointsForRole(Config $config, int $points, string $role): array
    {
        $role = strtolower($role);
        $category = null;
        if ($role === 'student') {
            $category = 'Students';
        } elseif ($role === 'alumni') {
            $category = 'Alumni';
        } else {
            return [];
        }

        $pdo = Database::pdo($config);
        $sql = 'SELECT b.* FROM badges b
                JOIN badge_categories c ON c.category_id = b.category_id
                WHERE c.category_name = :cat AND b.required_points <= :p
                ORDER BY b.required_points ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cat' => $category, ':p' => $points]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Ensure role-specific badge categories and ladders exist (idempotent).
     * This is a runtime safety net if DB seeds haven't been applied.
     */
    public static function ensureRoleLaddersSeeded(Config $config): void
    {
        $pdo = Database::pdo($config);
        // Categories
        $pdo->exec("INSERT INTO badge_categories (category_name, description)
                    SELECT 'Students','Badges for student contributors'
                    WHERE NOT EXISTS (SELECT 1 FROM badge_categories WHERE category_name='Students')");
        $pdo->exec("INSERT INTO badge_categories (category_name, description)
                    SELECT 'Alumni','Badges for alumni contributors'
                    WHERE NOT EXISTS (SELECT 1 FROM badge_categories WHERE category_name='Alumni')");

        // Helper to insert a badge if missing
        $ins = $pdo->prepare(
            'INSERT INTO badges (badge_name, category_id, description, level, required_points)
             SELECT :name, c.category_id, :desc, :level, :rp FROM badge_categories c
             WHERE c.category_name = :cat AND NOT EXISTS (SELECT 1 FROM badges WHERE badge_name = :name)'
        );

        // Students ladder
        $studentBadges = [
            ['Newcomer','Students','Welcome badge for signing up and starting out','bronze',10],
            ['Active Learner','Students','Regularly engages: asks, comments, and participates','bronze',50],
            ['Helpful Peer','Students','Provides useful answers and feedback','silver',150],
            ['Problem Solver','Students','Consistently earns upvotes for solutions','silver',350],
            ['Rising Scholar','Students','High-quality answers across topics','gold',700],
            ['Campus Leader','Students','Standout contributor and discussion starter','gold',1200],
        ];
        foreach ($studentBadges as [$name,$cat,$desc,$level,$rp]) {
            $ins->execute([':name'=>$name, ':desc'=>$desc, ':level'=>$level, ':rp'=>$rp, ':cat'=>$cat]);
        }

        // Alumni ladder
        $alumniBadges = [
            ['Supportive Alum','Alumni','Returns to guide and support students','bronze',25],
            ['Career Guide','Alumni','Gives practical, career-focused advice','bronze',100],
            ['Trusted Mentor','Alumni','Frequent, well-received mentorship and answers','silver',300],
            ['Senior Mentor','Alumni','Broad impact across threads and sessions','silver',600],
            ['Community Champion','Alumni','Go-to voice for guidance','gold',1000],
            ['Industry Expert','Alumni','Elite, consistently top-rated contributions','gold',1600],
        ];
        foreach ($alumniBadges as [$name,$cat,$desc,$level,$rp]) {
            $ins->execute([':name'=>$name, ':desc'=>$desc, ':level'=>$level, ':rp'=>$rp, ':cat'=>$cat]);
        }
    }
}
