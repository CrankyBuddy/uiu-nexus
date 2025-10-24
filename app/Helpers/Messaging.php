<?php
declare(strict_types=1);

namespace Nexus\Helpers;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class Messaging
{
    public static function hasDmQuota(Config $config, int $userId): bool
    {
        // Count messages sent today by this user in direct conversations
        $pdo = Database::pdo($config);
        $st = $pdo->prepare("SELECT COUNT(*) FROM messages m JOIN conversations c ON c.conversation_id = m.conversation_id WHERE c.conversation_type = 'direct' AND m.sender_id = :u AND DATE(m.created_at) = CURRENT_DATE()");
        $st->execute([':u' => $userId]);
        $cnt = (int)($st->fetchColumn() ?: 0);
        $quota = (int)($config->get('app.chat.daily_quota') ?? 20);
        return $cnt < $quota;
    }
    /**
     * Determine if $fromUserId can start a conversation with $toUserId based on role rules.
     */
    public static function canMessage(Config $config, int $fromUserId, int $toUserId): bool
    {
        if ($fromUserId === $toUserId) return false;
        $pdo = Database::pdo($config);

        // Load roles for both users
        $stmt = $pdo->prepare('SELECT user_id, role, is_active FROM users WHERE user_id IN (:a, :b)');
        $stmt->execute([':a' => $fromUserId, ':b' => $toUserId]);
        $roles = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $roles[(int)$r['user_id']] = [
                'role' => (string)$r['role'],
                'is_active' => (int)($r['is_active'] ?? 0),
            ];
        }
        $fromRole = $roles[$fromUserId]['role'] ?? '';
        $toRole = $roles[$toUserId]['role'] ?? '';
        $toActive = (int)($roles[$toUserId]['is_active'] ?? 0) === 1;

        if ($fromRole === 'admin') {
            return true; // Admins can message anyone
        }

        // Enforce simple daily DM quota for non-admins (soft gate for Phase 2)
        if (!self::hasDmQuota($config, $fromUserId)) {
            return false;
        }

        if ($fromRole === 'alumni') {
            // Alumni to recruiter: only if recruiter initiated and not blocked by recruiter
            if ($toRole === 'recruiter') {
                if (!self::hasRecruiterInitiated($config, $toUserId, $fromUserId)) return false;
                if (self::isRecruiterRepliesBlocked($config, $toUserId, $fromUserId)) return false;
                return true;
            }
            if ($toRole === 'alumni') return true; // Alumni ↔ Alumni allowed
            if ($toRole === 'student') {
                // Alumni can message students they mentor(ed) (accepted or completed requests)
                $sql = 'SELECT 1
                        FROM mentorship_requests mr
                        JOIN mentorship_listings ml ON ml.listing_id = mr.listing_id
                        JOIN alumni a ON a.alumni_id = ml.alumni_id
                        JOIN students s ON s.student_id = mr.student_id
                        JOIN users ua ON ua.user_id = a.user_id
                        JOIN users us ON us.user_id = s.user_id
                        WHERE ua.user_id = :alumni
                          AND us.user_id = :student
                          AND mr.status IN (\'accepted\', \'completed\')
                        LIMIT 1';
                $st = $pdo->prepare($sql);
                $st->execute([':alumni' => $fromUserId, ':student' => $toUserId]);
                return (bool)$st->fetch(PDO::FETCH_NUM);
            }
            return false; // alumni -> others not allowed by rules
        }

        if ($fromRole === 'student') {
            // Student to recruiter: only if recruiter initiated and not blocked
            if ($toRole === 'recruiter') {
                if (!self::hasRecruiterInitiated($config, $toUserId, $fromUserId)) return false;
                if (self::isRecruiterRepliesBlocked($config, $toUserId, $fromUserId)) return false;
                return true;
            }
            // Students can message:
            // 1) Alumni they have mentorship with (accepted/completed)
            // 2) Other active students (peer messaging)
            if ($toRole === 'student') {
                return $toActive; // allow active peer students
            }
            if ($toRole !== 'alumni') return false;
            $sql = 'SELECT 1
                    FROM mentorship_requests mr
                    JOIN mentorship_listings ml ON ml.listing_id = mr.listing_id
                    JOIN alumni a ON a.alumni_id = ml.alumni_id
                    JOIN students s ON s.student_id = mr.student_id
                    JOIN users ua ON ua.user_id = a.user_id
                    JOIN users us ON us.user_id = s.user_id
                    WHERE us.user_id = :student
                      AND ua.user_id = :alumni
                      AND mr.status IN (\'accepted\', \'completed\')
                    LIMIT 1';
            $st = $pdo->prepare($sql);
            $st->execute([':student' => $fromUserId, ':alumni' => $toUserId]);
            return (bool)$st->fetch(PDO::FETCH_NUM);
        }

        // Recruiter: can initiate to students or alumni; cannot initiate to recruiters unless requirements change
        if ($fromRole === 'recruiter') {
            return in_array($toRole, ['student','alumni'], true);
        }
        // Default: others cannot start chats unless admin
        return false;
    }

    /**
     * List eligible recipients for $userId. Optional search $q (matches name/email). Paged.
     * Returns array of [user_id, email, role, first_name, last_name].
     */
    public static function eligibleRecipients(Config $config, int $userId, ?string $q = null, int $page = 1, int $perPage = 20, string $roleFilter = ''): array
    {
        $pdo = Database::pdo($config);
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        // Find caller role
        $st = $pdo->prepare('SELECT role FROM users WHERE user_id = :u');
        $st->execute([':u' => $userId]);
        $role = (string)($st->fetchColumn() ?: '');

        if ($role === 'admin') {
            // Use unique placeholders for repeated binds to avoid HY093
            $params = [':me1' => $userId, ':me2' => $userId];
            $whereSearch = '';
            if ($q !== null && $q !== '') {
                $whereSearch = ' AND (u.email LIKE :q1a OR up.first_name LIKE :q1b OR up.last_name LIKE :q1c) ';
                $params[':q1a'] = '%' . $q . '%';
                $params[':q1b'] = '%' . $q . '%';
                $params[':q1c'] = '%' . $q . '%';
            }
                        $sql = "SELECT u.user_id, u.email, u.role, up.first_name, up.last_name, up.profile_picture_url
                                        FROM users u
                                        LEFT JOIN user_profiles up ON up.user_id = u.user_id
                    WHERE u.user_id <> :me1 $whereSearch
                                            AND NOT EXISTS (
                                                SELECT 1 FROM conversations c
                        JOIN conversation_participants p1 ON p1.conversation_id = c.conversation_id AND p1.user_id = :me2
                                                JOIN conversation_participants p2 ON p2.conversation_id = c.conversation_id AND p2.user_id = u.user_id
                                                WHERE c.conversation_type = 'direct'
                                            )
                                        ORDER BY up.first_name, up.last_name, u.email
                                        LIMIT $perPage OFFSET $offset";
                        // Filter params to those actually present in SQL to avoid HY093
                        if (preg_match_all('/:[a-zA-Z_][a-zA-Z0-9_]*/', $sql, $m)) {
                            $used = array_unique($m[0]);
                            $params = array_intersect_key($params, array_flip($used));
                        }
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }

        if ($role === 'alumni') {
            // All other alumni
                        // Unique placeholders per query and repeated usage
                        $params = [
                            ':meA1' => $userId, // alumni list outer
                            ':meA2' => $userId, // alumni list subquery
                            ':meS1' => $userId, // students (mentees) outer
                            ':meS2' => $userId, // students (mentees) subquery
                        ];
                        $wAlumni = '';
                        if ($q !== null && $q !== '') { $wAlumni = ' AND (u.email LIKE :qAa OR up.first_name LIKE :qAb OR up.last_name LIKE :qAc) '; $params[':qAa'] = '%' . $q . '%'; $params[':qAb'] = '%' . $q . '%'; $params[':qAc'] = '%' . $q . '%'; }
            $sqlAlumni = "SELECT u.user_id, u.email, u.role, up.first_name, up.last_name, up.profile_picture_url
                          FROM alumni a2
                          JOIN users u ON u.user_id = a2.user_id AND u.user_id <> :meA1
                          LEFT JOIN user_profiles up ON up.user_id = u.user_id
                                                    WHERE 1=1 $wAlumni
                                                        AND NOT EXISTS (
                                                            SELECT 1 FROM conversations c
                                                            JOIN conversation_participants p1 ON p1.conversation_id = c.conversation_id AND p1.user_id = :meA2
                                                            JOIN conversation_participants p2 ON p2.conversation_id = c.conversation_id AND p2.user_id = u.user_id
                                                            WHERE c.conversation_type = 'direct'
                                                        )";
            // Students mentored (accepted/completed)
                        $wStudentMentees = '';
                        if ($q !== null && $q !== '') { $wStudentMentees = ' AND (us.email LIKE :qSa OR ups.first_name LIKE :qSb OR ups.last_name LIKE :qSc) '; $params[':qSa'] = '%' . $q . '%'; $params[':qSb'] = '%' . $q . '%'; $params[':qSc'] = '%' . $q . '%'; }
            $sqlStudents = "SELECT us.user_id, us.email, us.role, ups.first_name, ups.last_name, ups.profile_picture_url
                            FROM mentorship_requests mr
                            JOIN mentorship_listings ml ON ml.listing_id = mr.listing_id
                            JOIN alumni a ON a.alumni_id = ml.alumni_id
                            JOIN users ua ON ua.user_id = a.user_id
                            JOIN students s ON s.student_id = mr.student_id
                            JOIN users us ON us.user_id = s.user_id
                            LEFT JOIN user_profiles ups ON ups.user_id = us.user_id
                                                        WHERE ua.user_id = :meS1 AND mr.status IN ('accepted','completed') $wStudentMentees
                                                            AND NOT EXISTS (
                                                                SELECT 1 FROM conversations c
                                                                JOIN conversation_participants p1 ON p1.conversation_id = c.conversation_id AND p1.user_id = :meS2
                                                                JOIN conversation_participants p2 ON p2.conversation_id = c.conversation_id AND p2.user_id = us.user_id
                                                                WHERE c.conversation_type = 'direct'
                                                            )";
            $sql = "($sqlAlumni) UNION DISTINCT ($sqlStudents) ORDER BY first_name, last_name, email LIMIT $perPage OFFSET $offset";
            if (preg_match_all('/:[a-zA-Z_][a-zA-Z0-9_]*/', $sql, $m)) {
                $used = array_unique($m[0]);
                $params = array_intersect_key($params, array_flip($used));
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        if ($role === 'student') {
            // Alumni with mentorship relation (accepted/completed)
                                    // Unique placeholders for repeated use and per subquery
                                    $params = [
                                        ':meA1' => $userId, // alumni outer
                                        ':meA2' => $userId, // alumni subquery
                                        ':meP1' => $userId, // peers outer
                                        ':meP2' => $userId, // peers subquery
                                    ];
                                    $wAlumni = '';
                                    $includeAlumni = ($roleFilter !== 'student');
                                    $includePeers = ($roleFilter !== 'alumni');
                                    if ($q !== null && $q !== '' && $includeAlumni) { $wAlumni = ' AND (ua.email LIKE :qAa OR up.first_name LIKE :qAb OR up.last_name LIKE :qAc) '; $params[':qAa'] = '%' . $q . '%'; $params[':qAb'] = '%' . $q . '%'; $params[':qAc'] = '%' . $q . '%'; }
            $sqlAlumni = "SELECT ua.user_id, ua.email, ua.role, up.first_name, up.last_name, up.profile_picture_url
                          FROM mentorship_requests mr
                          JOIN mentorship_listings ml ON ml.listing_id = mr.listing_id
                          JOIN alumni a ON a.alumni_id = ml.alumni_id
                          JOIN users ua ON ua.user_id = a.user_id
                          LEFT JOIN user_profiles up ON up.user_id = ua.user_id
                          JOIN students s ON s.student_id = mr.student_id
                          JOIN users us ON us.user_id = s.user_id
                                                    WHERE us.user_id = :meA1 AND mr.status IN ('accepted','completed') $wAlumni
                                                        AND NOT EXISTS (
                                                            SELECT 1 FROM conversations c
                                                            JOIN conversation_participants p1 ON p1.conversation_id = c.conversation_id AND p1.user_id = :meA2
                                                            JOIN conversation_participants p2 ON p2.conversation_id = c.conversation_id AND p2.user_id = ua.user_id
                                                            WHERE c.conversation_type = 'direct'
                                                        )";
            // Peer students (active)
                                    $wPeers = '';
                                    if ($q !== null && $q !== '' && $includePeers) { $wPeers = ' AND (us.email LIKE :qSa OR ups.first_name LIKE :qSb OR ups.last_name LIKE :qSc) '; $params[':qSa'] = '%' . $q . '%'; $params[':qSb'] = '%' . $q . '%'; $params[':qSc'] = '%' . $q . '%'; }
            $sqlPeers = "SELECT us.user_id, us.email, us.role, ups.first_name, ups.last_name, ups.profile_picture_url
                         FROM users us
                         LEFT JOIN user_profiles ups ON ups.user_id = us.user_id
                                                WHERE us.role = 'student' AND us.is_active = 1 AND us.user_id <> :meP1 $wPeers
                                                     AND NOT EXISTS (
                                                         SELECT 1 FROM conversations c
                                                         JOIN conversation_participants p1 ON p1.conversation_id = c.conversation_id AND p1.user_id = :meP2
                                                         JOIN conversation_participants p2 ON p2.conversation_id = c.conversation_id AND p2.user_id = us.user_id
                                                         WHERE c.conversation_type = 'direct'
                                                     )";
            if ($roleFilter === 'alumni') {
                                            $sql = "($sqlAlumni) ORDER BY first_name, last_name, email LIMIT $perPage OFFSET $offset";
            } elseif ($roleFilter === 'student') {
                                            $sql = "($sqlPeers) ORDER BY first_name, last_name, email LIMIT $perPage OFFSET $offset";
            } else {
                $sql = "($sqlAlumni) UNION DISTINCT ($sqlPeers) ORDER BY first_name, last_name, email LIMIT $perPage OFFSET $offset";
            }
            if (preg_match_all('/:[a-zA-Z_][a-zA-Z0-9_]*/', $sql, $m)) {
                $used = array_unique($m[0]);
                $params = array_intersect_key($params, array_flip($used));
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        if ($role === 'recruiter') {
            // Recruiters can initiate to students or alumni only
                                    $params = [':me1' => $userId, ':me2' => $userId];
                                    $w = '';
                                    if ($q !== null && $q !== '') { $w = ' AND (u.email LIKE :q1a OR up.first_name LIKE :q1b OR up.last_name LIKE :q1c) '; $params[':q1a'] = '%' . $q . '%'; $params[':q1b'] = '%' . $q . '%'; $params[':q1c'] = '%' . $q . '%'; }
            $roleCond = "u.role IN ('student','alumni')";
            if ($roleFilter === 'alumni') $roleCond = "u.role = 'alumni'";
            if ($roleFilter === 'student') $roleCond = "u.role = 'student'";
            $sql = "SELECT u.user_id, u.email, u.role, up.first_name, up.last_name, up.profile_picture_url
                    FROM users u
                    LEFT JOIN user_profiles up ON up.user_id = u.user_id
                                        WHERE u.user_id <> :me1 AND $roleCond $w
                                            AND NOT EXISTS (
                                                SELECT 1 FROM conversations c
                                                JOIN conversation_participants p1 ON p1.conversation_id = c.conversation_id AND p1.user_id = :me2
                                                JOIN conversation_participants p2 ON p2.conversation_id = c.conversation_id AND p2.user_id = u.user_id
                                                WHERE c.conversation_type = 'direct'
                                            )
                    ORDER BY up.first_name, up.last_name, u.email
                    LIMIT $perPage OFFSET $offset";
            if (preg_match_all('/:[a-zA-Z_][a-zA-Z0-9_]*/', $sql, $m)) {
                $used = array_unique($m[0]);
                $params = array_intersect_key($params, array_flip($used));
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        return [];
    }

    /**
     * Return counts used by the New picker chips: ['all'=>int, 'alumni'=>int, 'student'=>int]
     */
    public static function eligibleCounts(Config $config, int $userId, ?string $q = null): array
    {
        // We can robustly compute counts by getting the full eligible list (bounded reasonably) and grouping by role.
        // This keeps logic in one place and respects ACL/search nuances per role.
        $all = self::eligibleRecipients($config, $userId, $q, 1, 5000, '');
        $counts = ['all' => 0, 'alumni' => 0, 'student' => 0];
        $counts['all'] = count($all);
        foreach ($all as $row) {
            $r = (string)($row['role'] ?? '');
            if ($r === 'alumni') $counts['alumni']++;
            if ($r === 'student') $counts['student']++;
        }
        return $counts;
    }

    // Recruiter-replies block toggles using user_feature_restrictions with namespaced feature keys
    public static function isRecruiterRepliesBlocked(Config $config, int $recruiterUserId, int $otherUserId): bool
    {
        $pdo = Database::pdo($config);
        $key = 'msg.block.recruiter:' . $recruiterUserId;
        $st = $pdo->prepare('SELECT 1 FROM user_feature_restrictions WHERE user_id = :u AND feature_key = :k LIMIT 1');
        $st->execute([':u' => $otherUserId, ':k' => $key]);
        return (bool)$st->fetch(PDO::FETCH_NUM);
    }

    public static function setRecruiterRepliesBlocked(Config $config, int $recruiterUserId, int $otherUserId, bool $blocked, int $byUserId): void
    {
        $pdo = Database::pdo($config);
        $key = 'msg.block.recruiter:' . $recruiterUserId;
        if ($blocked) {
            $st = $pdo->prepare('INSERT INTO user_feature_restrictions (user_id, feature_key, restricted_until, reason, restricted_by) VALUES (:u, :k, NULL, :r, :by)');
            $st->execute([':u' => $otherUserId, ':k' => $key, ':r' => 'Recruiter disabled replies', ':by' => $byUserId]);
        } else {
            $st = $pdo->prepare('DELETE FROM user_feature_restrictions WHERE user_id = :u AND feature_key = :k');
            $st->execute([':u' => $otherUserId, ':k' => $key]);
        }
    }

    // True if recruiter has previously sent at least one message in a direct conversation with other user
    public static function hasRecruiterInitiated(Config $config, int $recruiterUserId, int $otherUserId): bool
    {
        $pdo = Database::pdo($config);
        $sql = "SELECT 1
                FROM conversations c
                JOIN conversation_participants p1 ON p1.conversation_id = c.conversation_id AND p1.user_id = :r1
                JOIN conversation_participants p2 ON p2.conversation_id = c.conversation_id AND p2.user_id = :o
                JOIN messages m ON m.conversation_id = c.conversation_id AND m.sender_id = :r2
                WHERE c.conversation_type = 'direct'
                LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([':r1' => $recruiterUserId, ':r2' => $recruiterUserId, ':o' => $otherUserId]);
        return (bool)$st->fetch(PDO::FETCH_NUM);
    }
}
