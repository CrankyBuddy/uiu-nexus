<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Core\Database;
use Nexus\Helpers\Auth;
use Nexus\Models\UserSkill;
use PDO;

final class PeopleController extends Controller
{
    public function index(): string
    {
        Auth::enforceAuth();
        $me = Auth::user();
        $isAdmin = (($me['role'] ?? '') === 'admin');

        $q = trim((string)($_GET['q'] ?? ''));
        $role = (string)($_GET['role'] ?? '');
    $skill = trim((string)($_GET['skill'] ?? ''));
    // Multi-skill: comma-separated; partial matching
    $skills = array_values(array_filter(array_map(static fn($s) => trim($s), explode(',', $skill)), static fn($s) => $s !== ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $pdo = Database::pdo($this->config);

        // Build conditions
        $where = ['u.is_active = 1'];
        $params = [];
        if ($q !== '') {
            if ($isAdmin) {
                // Admins can search by email or name
                $where[] = '(u.email LIKE :q1 OR up.first_name LIKE :q2 OR up.last_name LIKE :q3)';
                $params[':q1'] = '%' . $q . '%';
                $params[':q2'] = '%' . $q . '%';
                $params[':q3'] = '%' . $q . '%';
            } else {
                // Non-admins: name-only search (no email)
                $where[] = '(up.first_name LIKE :q2 OR up.last_name LIKE :q3)';
                $params[':q2'] = '%' . $q . '%';
                $params[':q3'] = '%' . $q . '%';
            }
        }
        if (in_array($role, ['student','alumni','recruiter','admin'], true)) {
            $where[] = 'u.role = :role';
            $params[':role'] = $role;
        }

        $skillJoin = '';
        if (!empty($skills)) {
            // For multi-skill partial matches, build JOINs that ensure user has all listed skills (AND semantics)
            // We will use a single JOIN and HAVING to count distinct matched skills
            $skillJoin = 'LEFT JOIN user_skills us ON us.user_id = u.user_id LEFT JOIN skills s ON s.skill_id = us.skill_id';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Count
        $having = '';
        if (!empty($skills)) {
            // Build OR conditions for partial matches, e.g., s.skill_name LIKE :s0 OR :s1 ... and ensure count >= number of filters
            $likeConds = [];
            foreach ($skills as $i => $sn) {
                $key = ':s' . $i;
                $params[$key] = '%' . $sn . '%';
                $likeConds[] = 's.skill_name LIKE ' . $key;
            }
            $having = ' HAVING COUNT(DISTINCT CASE WHEN ' . implode(' OR ', $likeConds) . ' THEN s.skill_id END) >= ' . count($skills) . ' ';
        }

    $countSql = "SELECT COUNT(*) FROM (
        SELECT u.user_id
        FROM users u
        LEFT JOIN user_profiles up ON up.user_id = u.user_id
        LEFT JOIN user_wallets w ON w.user_id = u.user_id
                $skillJoin
                $whereSql
                GROUP BY u.user_id
                $having
            ) t";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)($stmt->fetchColumn() ?: 0);
        $totalPages = (int)max(1, ceil($total / $perPage));
        if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

        // Data
    $sql = "SELECT u.user_id, u.email, u.role, up.first_name, up.last_name, up.bio, up.profile_picture_url,
        COALESCE(w.balance, 0) AS wallet_balance,
        COALESCE(w.total_earned, 0) AS wallet_total_earned,
        COALESCE(w.total_spent, 0) AS wallet_total_spent
        FROM users u
        LEFT JOIN user_profiles up ON up.user_id = u.user_id
        LEFT JOIN user_wallets w ON w.user_id = u.user_id
        $skillJoin
        $whereSql
        GROUP BY u.user_id, u.email, u.role, up.first_name, up.last_name, up.bio, up.profile_picture_url, w.balance, w.total_earned, w.total_spent
        $having
        ORDER BY up.first_name, up.last_name
        LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
        $people = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach skills per user (names)
        $peopleWithSkills = [];
        $userIds = [];
        foreach ($people as $p) {
            $skills = UserSkill::getNamesByUser($this->config, (int)$p['user_id']);
            $p['skills'] = $skills;
            $userIds[] = (int)$p['user_id'];
            $peopleWithSkills[] = $p;
        }

        // Fetch badges for listed users in batch
        $badgesByUser = [];
        if (!empty($userIds)) {
            $pdo = Database::pdo($this->config);
            $in = implode(',', array_fill(0, count($userIds), '?'));
            $sqlB = "SELECT ub.user_id, b.badge_name, b.level FROM user_badges ub JOIN badges b ON b.badge_id = ub.badge_id WHERE ub.user_id IN ($in) ORDER BY ub.awarded_at DESC";
            $stB = $pdo->prepare($sqlB);
            foreach ($userIds as $i => $uid) { $stB->bindValue($i+1, $uid, PDO::PARAM_INT); }
            $stB->execute();
            foreach ($stB->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $uid = (int)$row['user_id'];
                if (!isset($badgesByUser[$uid])) { $badgesByUser[$uid] = []; }
                $badgesByUser[$uid][] = [ 'badge_name' => $row['badge_name'], 'level' => $row['level'] ];
            }
            // attach to people
            foreach ($peopleWithSkills as &$pp) {
                $uid = (int)($pp['user_id'] ?? 0);
                $pp['badges'] = $badgesByUser[$uid] ?? [];
            }
            unset($pp);
        }

        // Fetch active restrictions for listed users (current or permanent)
        $restrictionsByUser = [];
        $restrictionCounts = [];
        if (!empty($userIds)) {
            $pdo = Database::pdo($this->config);
            $in = implode(',', array_fill(0, count($userIds), '?'));
            $sqlR = "SELECT user_id, feature_key FROM user_feature_restrictions WHERE user_id IN ($in) AND (restricted_until IS NULL OR restricted_until > NOW())";
            $stR = $pdo->prepare($sqlR);
            foreach ($userIds as $i => $uid) { $stR->bindValue($i+1, $uid, PDO::PARAM_INT); }
            $stR->execute();
            foreach ($stR->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $uid = (int)$row['user_id'];
                $k = (string)$row['feature_key'];
                if (!isset($restrictionsByUser[$uid])) $restrictionsByUser[$uid] = [];
                $restrictionsByUser[$uid][$k] = true;
            }

            // Fetch historical counts from events table; tolerate missing table during migration
            try {
                $sqlC = "SELECT user_id, event_type, COUNT(*) as c FROM user_feature_restriction_events WHERE user_id IN ($in) GROUP BY user_id, event_type";
                $stC = $pdo->prepare($sqlC);
                foreach ($userIds as $i => $uid) { $stC->bindValue($i+1, $uid, PDO::PARAM_INT); }
                $stC->execute();
                foreach ($stC->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                    $uid = (int)$row['user_id'];
                    $etype = (string)$row['event_type'];
                    $cnt = (int)$row['c'];
                    if (!isset($restrictionCounts[$uid])) { $restrictionCounts[$uid] = ['ban'=>0,'suspend'=>0,'lift'=>0]; }
                    if (isset($restrictionCounts[$uid][$etype])) { $restrictionCounts[$uid][$etype] = $cnt; }
                }
            } catch (\PDOException $e) {
                // If table doesn't exist, leave counts empty; UI will default to zeros.
            }
        }

        return $this->view('people.index', [
            'people' => $peopleWithSkills,
            'q' => $q,
            'role' => $role,
            'skill' => $skill,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'restrictions' => $restrictionsByUser,
            'restrictionCounts' => $restrictionCounts,
            'isAdmin' => $isAdmin,
        ]);
    }
}
