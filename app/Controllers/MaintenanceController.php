<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Core\Database;
use Nexus\Models\TransactionType;
use Nexus\Models\Badge;
use Nexus\Models\MentorshipListing;
use Nexus\Models\MentorshipRequest;
use Nexus\Models\UserWallet;
use PDO;

final class MaintenanceController extends Controller
{
    private function authorize(): void
    {
        $provided = (string)($_GET['key'] ?? '');
        $expected = (string)$this->config->get('app.maintenance_key');
        if ($expected === '' || !hash_equals($expected, $provided)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    // Seed basic transaction types and badges
    public function seed(): string
    {
        $this->authorize();
        $pdo = Database::pdo($this->config);
        $pdo->beginTransaction();
        try {
            // Transaction Types (idempotent via unique name)
            TransactionType::ensure($this->config, 'Registration Bonus', 100, true, 'system');
            TransactionType::ensure($this->config, 'Forum Upvote', 5, true, 'forum');
            TransactionType::ensure($this->config, 'Forum Downvote', 2, false, 'forum');
            TransactionType::ensure($this->config, 'Mentorship Earn', 50, true, 'mentorship');
            TransactionType::ensure($this->config, 'Mentorship Bid', 20, false, 'mentorship');

            // Phase 10: Seed default permissions and grant to admin role (idempotent)
            $permissions = [
                ['manage.users', 'Manage users (activate/deactivate, role changes)', 'admin'],
                ['manage.permissions', 'Manage role and user permissions', 'admin'],
                ['view.audit_logs', 'View audit logs', 'admin'],
                ['manage.reports', 'Triage and resolve user reports', 'admin'],
                ['manage.restrictions', 'Apply or remove user feature restrictions', 'admin'],
            ];
            $stmtPerm = $pdo->prepare("INSERT IGNORE INTO permissions (permission_key, description, module) VALUES (:k, :d, :m)");
            foreach ($permissions as [$key, $desc, $module]) {
                $stmtPerm->execute([':k' => $key, ':d' => $desc, ':m' => $module]);
            }
            // Grant all seeded permissions to admin role
            $permIds = $pdo->query("SELECT permission_id, permission_key FROM permissions WHERE permission_key IN ('manage.users','manage.permissions','view.audit_logs','manage.reports','manage.restrictions')")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            $insRole = $pdo->prepare('INSERT IGNORE INTO role_permissions (role, permission_id) VALUES (\'admin\', :pid)');
            foreach ($permIds as $pid => $pkey) {
                $insRole->execute([':pid' => (int)$pid]);
            }

            // Badge Categories and Badges minimal seed
            // Insert categories if not exist
            $pdo->exec("INSERT IGNORE INTO badge_categories (category_name, description) VALUES ('Reputation', 'Based on reputation points')");
            $catId = (int)($pdo->query("SELECT category_id FROM badge_categories WHERE category_name='Reputation' LIMIT 1")->fetchColumn() ?: 0);
            if ($catId > 0) {
                $stmt = $pdo->prepare('INSERT IGNORE INTO badges (badge_name, category_id, description, required_points, level) VALUES (:n, :c, :d, :p, :l)');
                $badges = [
                    ['Rookie', 0, 'Reached 100 reputation', 100, 'bronze'],
                    ['Seasoned', 0, 'Reached 250 reputation', 250, 'silver'],
                    ['Veteran', 0, 'Reached 500 reputation', 500, 'gold'],
                    ['Legend', 0, 'Reached 1000 reputation', 1000, 'platinum'],
                ];
                foreach ($badges as $b) {
                    $stmt->execute([':n' => $b[0], ':c' => $catId, ':d' => $b[2], ':p' => $b[3], ':l' => $b[4]]);
                }
            }

            // Seed default forum categories
            $pdo->exec("INSERT IGNORE INTO forum_categories (category_name, description) VALUES
                ('General', 'General Q&A'),
                ('Career', 'Career advice and experiences'),
                ('Academics', 'Courses, projects, and research'),
                ('Technology', 'Programming, tools, and stacks')");

            // Seed basic job data
            $pdo->exec("INSERT IGNORE INTO job_categories (category_name) VALUES
                ('Software Engineering'),
                ('Data Science'),
                ('Design'),
                ('Business'),
                ('Marketing')");
            $pdo->exec("INSERT IGNORE INTO job_types (type_name) VALUES
                ('Full-time'),
                ('Part-time'),
                ('Internship'),
                ('Contract'),
                ('Remote')");
            $pdo->exec("INSERT IGNORE INTO locations (location_name, country, state, city) VALUES
                ('Dhaka, Bangladesh','Bangladesh','Dhaka','Dhaka'),
                ('Chattogram, Bangladesh','Bangladesh','Chattogram','Chattogram'),
                ('Remote','Bangladesh',NULL,NULL)");

            // Seed a sample upcoming event if none exists
            $exists = (int)($pdo->query("SELECT COUNT(*) FROM events")->fetchColumn() ?: 0);
            if ($exists === 0) {
                $stmt = $pdo->prepare('INSERT INTO events (title, description, event_type, event_date, location, venue_details, is_active) VALUES (:t,:d,:ty,:dt,:loc,:vd,1)');
                $stmt->execute([
                    ':t' => 'Welcome Networking Session',
                    ':d' => 'Kick off the semester with peers and alumni networking.',
                    ':ty' => 'networking',
                    ':dt' => date('Y-m-d H:i:s', strtotime('+7 days')),
                    ':loc' => 'UIU Campus',
                    ':vd' => 'Auditorium',
                ]);
            }

            // Seed a sample announcement if none exists and at least one user exists
            $aexists = (int)($pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn() ?: 0);
            if ($aexists === 0) {
                $authorId = (int)($pdo->query("SELECT user_id FROM users WHERE role='admin' ORDER BY user_id ASC LIMIT 1")->fetchColumn() ?: 0);
                if ($authorId === 0) {
                    $authorId = (int)($pdo->query("SELECT user_id FROM users ORDER BY user_id ASC LIMIT 1")->fetchColumn() ?: 0);
                }
                if ($authorId > 0) {
                    $stmt = $pdo->prepare("INSERT INTO announcements (title, content, author_id, target_roles, is_published) VALUES (:t,:c,:a,:tr,1)");
                    $stmt->execute([':t' => 'Welcome to UIU NEXUS', ':c' => 'Stay tuned for updates!', ':a' => $authorId, ':tr' => '[\"student\",\"alumni\",\"recruiter\"]']);
                    $annId = (int)$pdo->lastInsertId();
                    if ($annId > 0) {
                        $ins = $pdo->prepare('INSERT IGNORE INTO announcement_target_roles (announcement_id, role) VALUES (:id, :r)');
                        foreach (['student','alumni','recruiter'] as $r) {
                            $ins->execute([':id' => $annId, ':r' => $r]);
                        }
                    }
                }
            }

            $pdo->commit();
            return 'Seed completed';
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            return 'Seed failed';
        }
    }

    // Rebuild current weekly and monthly leaderboards
    public function rebuildLeaderboards(): string
    {
        $this->authorize();
        $pdo = Database::pdo($this->config);
        $today = new \DateTimeImmutable('today');
        // Compute week: Monday..Sunday as an example
        $dow = (int)$today->format('N'); // 1..7
        $weekStart = $today->modify('-' . ($dow - 1) . ' days');
        $weekEnd = $weekStart->modify('+6 days');
        $monthStart = $today->modify('first day of this month');
        $monthEnd = $today->modify('last day of this month');

        // Score definition: weekly/monthly use SUM(reputation_events.delta) within period; all-time uses current reputation_score snapshot
    $roles = ['student','alumni'];
        $periods = [
            ['weekly', $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')],
            ['monthly', $monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')],
            ['all', '1970-01-01', '2099-12-31'],
        ];
        try {
            foreach ($periods as [$ptype, $pstart, $pend]) {
                foreach ($roles as $role) {
                    if ($ptype === 'all') {
            $sql = "SELECT u.user_id, uw.reputation_score AS score
                FROM users u
                INNER JOIN user_wallets uw ON uw.user_id = u.user_id
                WHERE u.role = :role AND u.is_active = 1 AND u.role IN ('student','alumni')
                ORDER BY uw.reputation_score DESC, u.user_id ASC
                LIMIT 50";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([':role' => $role]);
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    } else {
            $sql = "SELECT u.user_id, COALESCE(SUM(re.delta),0) AS score
                FROM users u
                LEFT JOIN reputation_events re ON re.user_id = u.user_id AND re.created_at BETWEEN :ps AND :pe
                WHERE u.role = :role AND u.is_active = 1 AND u.role IN ('student','alumni')
                GROUP BY u.user_id
                HAVING score <> 0
                ORDER BY score DESC, u.user_id ASC
                LIMIT 50";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([':role' => $role, ':ps' => $pstart . ' 00:00:00', ':pe' => $pend . ' 23:59:59']);
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    }

                    // Clear existing rows for this period/role
                    $del = $pdo->prepare('DELETE l FROM leaderboards l INNER JOIN users u ON u.user_id = l.user_id WHERE l.period_type = :p AND u.role = :r AND l.period_start = :ps AND l.period_end = :pe');
                    $del->execute([':p' => $ptype, ':r' => $role, ':ps' => $pstart, ':pe' => $pend]);

                    // Insert with rank
                    $ins = $pdo->prepare('INSERT INTO leaderboards (user_id, period_type, period_start, period_end, score, rank) VALUES (:u, :p, :ps, :pe, :s, :rk)');
                    $rank = 1;
                    foreach ($rows as $row) {
                        $ins->execute([
                            ':u' => (int)$row['user_id'],
                            ':p' => $ptype,
                            ':ps' => $pstart,
                            ':pe' => $pend,
                            ':s' => (int)$row['score'],
                            ':rk' => $rank++,
                        ]);
                    }
                }
            }
            return 'Leaderboards rebuilt';
        } catch (\Throwable $e) {
            http_response_code(500);
            return 'Rebuild failed';
        }
    }

    public function resetFreeRequests(): string
    {
        $this->authorize();
        $pdo = Database::pdo($this->config);
        $pdo->exec('UPDATE students SET free_mentorship_requests = 3, free_mentorship_reset_at = NOW()');
        return 'OK';
    }

    // Auto-complete mentorship requests whose 1-month window has ended
    public function mentorshipAutoComplete(): string
    {
        $this->authorize();
        $pdo = Database::pdo($this->config);
        $sql = "SELECT r.request_id, r.is_free_request, r.bid_amount, r.listing_id, a.user_id AS alumni_user_id
                FROM mentorship_requests r
                INNER JOIN mentorship_listings l ON l.listing_id = r.listing_id
                INNER JOIN alumni a ON a.alumni_id = l.alumni_id
                WHERE r.status = 'accepted' AND r.end_date IS NOT NULL AND r.end_date < CURRENT_DATE()";
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $completed = 0;
        foreach ($rows as $row) {
            try {
                // Release escrow to alumni if paid
                if (!(bool)$row['is_free_request']) {
                    $alumniUserId = (int)$row['alumni_user_id'];
                    $amount = (int)$row['bid_amount'];
                    UserWallet::ensureExists($this->config, $alumniUserId);
                    // Best-effort payout; if it fails, skip to next (do not mark completed)
                    $ok = UserWallet::transact($this->config, $alumniUserId, 'Mentorship Escrow Release', $amount, true, 'Auto-complete mentorship payout', 'mentorship_request', (int)$row['request_id']);
                    if (!$ok) {
                        continue;
                    }
                }
                // Mark completed and free a slot
                MentorshipRequest::markCompleted($this->config, (int)$row['request_id']);
                MentorshipListing::decrementSlot($this->config, (int)$row['listing_id']);
                $completed++;
            } catch (\Throwable $e) {
                // continue with next
                continue;
            }
        }
        return 'Completed: ' . $completed;
    }
}
