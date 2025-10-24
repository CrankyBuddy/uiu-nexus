<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Config;
use Nexus\Core\Controller;
use Nexus\Core\Database;
use Nexus\Helpers\Auth;
use Nexus\Helpers\Csrf;
use Nexus\Helpers\Gamify;
use Nexus\Models\Alumni;
use Nexus\Models\ExpertiseArea;
use Nexus\Models\MentorshipListing;
use Nexus\Models\MentorshipListingTime;
use Nexus\Models\MentorshipRequest;
use Nexus\Models\MentorshipSession;
use Nexus\Models\MentorshipCancellation;
use Nexus\Models\Student;
use Nexus\Models\UserWallet;
use Nexus\Models\Notification;
use PDO;

final class MentorshipController extends Controller
{
    public function index(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        // RBAC: recruiters cannot view mentorship listings
        if ((string)($user['role'] ?? '') === 'recruiter') {
            http_response_code(403);
            echo 'Recruiters are not allowed to view mentorship listings.';
            return '';
        }
        $listings = MentorshipListing::activeAll($this->config);
        return $this->view('mentorship/index', ['listings' => $listings]);
    }

    public function create(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if ($user['role'] !== 'alumni') {
            http_response_code(403);
            echo 'Only alumni can create listings';
            return '';
        }
        // Suspend check: mentorship feature
        if (\Nexus\Helpers\Restrictions::isMentorshipSuspended($this->config, (int)$user['user_id'])) {
            http_response_code(403);
            echo 'Your mentorship features are currently suspended.';
            return '';
        }
        $expertise = ExpertiseArea::all($this->config);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
            $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
            if (!$alumni) {
                // Ensure alumni role row exists with defaults
                Alumni::upsert($this->config, (int)$user['user_id'], []);
                $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
            }
            $expertiseId = (int)($_POST['expertise_id'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $minBid = max(0, (int)($_POST['min_coin_bid'] ?? 0));
            $maxSlots = max(1, (int)($_POST['max_slots'] ?? 1));
            $duration = max(15, (int)($_POST['session_duration'] ?? 60));
            $minCgpa = isset($_POST['min_cgpa']) && $_POST['min_cgpa'] !== '' ? (float)$_POST['min_cgpa'] : null;
            $minProjects = isset($_POST['min_projects']) && $_POST['min_projects'] !== '' ? (int)$_POST['min_projects'] : null;
            $minWalletCoins = isset($_POST['min_wallet_coins']) && $_POST['min_wallet_coins'] !== '' ? (int)$_POST['min_wallet_coins'] : null;
            $listingId = MentorshipListing::create($this->config, (int)$alumni['alumni_id'], $expertiseId, $description, $minBid, $maxSlots, $duration, true, $minCgpa, $minProjects, $minWalletCoins);

            // Optional normalized slot entries
            $slots = [];
            if (!empty($_POST['slots']) && is_array($_POST['slots'])) {
                foreach ($_POST['slots'] as $slot) {
                    $slots[] = [
                        'day_of_week' => (int)($slot['day_of_week'] ?? 0),
                        'start_time' => $slot['start_time'] ?? null,
                        'end_time' => $slot['end_time'] ?? null,
                        'timezone' => $slot['timezone'] ?? null,
                    ];
                }
            }
            MentorshipListingTime::upsertForListing($this->config, $listingId, $slots);
            $this->redirect('/mentorship/my-listings');
            return '';
        }
        return $this->view('mentorship/create', ['expertise' => $expertise]);
    }

    public function myListings(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if ($user['role'] !== 'alumni') {
            http_response_code(403);
            echo 'Only alumni can view their listings';
            return '';
        }
        if (\Nexus\Helpers\Restrictions::isMentorshipSuspended($this->config, (int)$user['user_id'])) { http_response_code(403); echo 'Your mentorship features are currently suspended.'; return ''; }
        $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
        if (!$alumni) {
            Alumni::upsert($this->config, (int)$user['user_id'], []);
            $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
        }
        $listings = MentorshipListing::byAlumni($this->config, (int)$alumni['alumni_id']);
        return $this->view('mentorship/my_listings', ['listings' => $listings]);
    }

    public function edit(int $listingId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        $listing = MentorshipListing::find($this->config, $listingId);
        if (!$listing) { http_response_code(404); echo 'Listing not found'; return ''; }
        $isAdmin = (($user['role'] ?? '') === 'admin');
        $isOwner = false;
        if (($user['role'] ?? '') === 'alumni') {
            $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
            $isOwner = $alumni && (int)$alumni['alumni_id'] === (int)$listing['alumni_id'];
        }
        if (!$isAdmin && !$isOwner) { http_response_code(403); echo 'Forbidden'; return ''; }
        $expertise = ExpertiseArea::all($this->config);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
            $data = [
                'expertise_id' => (int)($_POST['expertise_id'] ?? $listing['expertise_id']),
                'description' => trim((string)($_POST['description'] ?? $listing['description'])),
                'min_coin_bid' => max(0, (int)($_POST['min_coin_bid'] ?? $listing['min_coin_bid'])),
                'max_slots' => max(1, (int)($_POST['max_slots'] ?? $listing['max_slots'])),
                'session_duration' => max(15, (int)($_POST['session_duration'] ?? $listing['session_duration'])),
                'is_active' => (int)($_POST['is_active'] ?? 1) === 1,
                'min_cgpa' => isset($_POST['min_cgpa']) && $_POST['min_cgpa'] !== '' ? (float)$_POST['min_cgpa'] : null,
                'min_projects' => isset($_POST['min_projects']) && $_POST['min_projects'] !== '' ? (int)$_POST['min_projects'] : null,
                'min_wallet_coins' => isset($_POST['min_wallet_coins']) && $_POST['min_wallet_coins'] !== '' ? (int)$_POST['min_wallet_coins'] : null,
            ];
            MentorshipListing::update($this->config, $listingId, $data);
                $this->redirect('/mentorship/listing/' . (int)$listingId);
            return '';
        }
        return $this->view('mentorship/edit', ['listing' => $listing, 'expertise' => $expertise, 'isAdmin' => $isAdmin]);
    }

    public function delete(int $listingId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $listing = MentorshipListing::find($this->config, $listingId);
        if (!$listing) { http_response_code(404); echo 'Listing not found'; return ''; }
        $isAdmin = (($user['role'] ?? '') === 'admin');
        $canDelete = $isAdmin;
        if (($user['role'] ?? '') === 'alumni') {
            $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
            $canDelete = $canDelete || ($alumni && (int)$alumni['alumni_id'] === (int)$listing['alumni_id']);
        }
        if (!$canDelete) { http_response_code(403); echo 'Forbidden'; return ''; }
        // Prevent deleting if there are pending/accepted requests; redirect back to listing page instead of echoing
        if (MentorshipListing::hasActiveRequests($this->config, $listingId)) {
            try { \Nexus\Helpers\Flash::add('warning', 'Cannot delete: listing has active or pending requests.'); } catch (\Throwable $e) {}
            $this->redirect('/mentorship/listing/' . (int)$listingId);
            return '';
        }
        MentorshipListing::delete($this->config, $listingId);
        try { \Nexus\Helpers\Flash::add('success', 'Listing deleted.'); } catch (\Throwable $e) {}
        $this->redirect('/mentorship');
        return '';
    }

    public function show(int $listingId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        // RBAC: recruiters cannot view mentorship listings
        if ((string)($user['role'] ?? '') === 'recruiter') {
            http_response_code(403);
            echo 'Recruiters are not allowed to view mentorship listings.';
            return '';
        }
        $listing = MentorshipListing::find($this->config, $listingId);
        if (!$listing || !$listing['is_active']) {
            http_response_code(404);
            echo 'Listing not found';
            return '';
        }
        $slots = MentorshipListingTime::forListing($this->config, $listingId);
        return $this->view('mentorship/show', ['listing' => $listing, 'slots' => $slots]);
    }

    public function request(int $listingId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
    if ($user['role'] !== 'student') { http_response_code(403); echo 'Only students can request mentorship'; return ''; }
        // Suspend check: mentorship feature
        if (\Nexus\Helpers\Restrictions::isMentorshipSuspended($this->config, (int)$user['user_id'])) { http_response_code(403); echo 'Your mentorship features are currently suspended.'; return ''; }
        $listing = MentorshipListing::find($this->config, $listingId);
    if (!$listing || !$listing['is_active']) { http_response_code(404); echo 'Listing not found'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
            $student = Student::findByUserId($this->config, (int)$user['user_id']);
            if (!$student) {
                // Ensure student role row exists
                Student::upsert($this->config, (int)$user['user_id'], []);
                $student = Student::findByUserId($this->config, (int)$user['user_id']);
            }
            // Enforce cooldown
            if (Student::isInCooldown($this->config, (int)$student['student_id'])) {
                return $this->view('mentorship/request', ['listing' => $listing, 'error' => 'You are in cooldown period. Please try again later.']);
            }
            // Enforce mentor listing constraints for Slot 1 fairness
            $violations = [];
            if (!empty($listing['min_cgpa'])) {
                $studentCgpa = (float)($student['cgpa'] ?? 0);
                if ($studentCgpa <= 0 || $studentCgpa + 1e-6 < (float)$listing['min_cgpa']) { $violations[] = 'Your CGPA does not meet the minimum requirement.'; }
            }
            if (!empty($listing['min_projects'])) {
                $pdoX = Database::pdo($this->config);
                $projCount = (int)($pdoX->query('SELECT COUNT(*) FROM student_projects sp JOIN students s ON s.user_id = sp.user_id WHERE s.student_id = ' . (int)$student['student_id'])->fetchColumn() ?: 0);
                if ($projCount < (int)$listing['min_projects']) { $violations[] = 'You need more projects to apply for this slot.'; }
            }
            if (!empty($listing['min_wallet_coins'])) {
                UserWallet::ensureExists($this->config, (int)$user['user_id']);
                $wallet = UserWallet::getByUserId($this->config, (int)$user['user_id']);
                if ((int)$wallet['balance'] < (int)$listing['min_wallet_coins']) { $violations[] = 'Your wallet balance is below the required minimum for this slot.'; }
            }
            // required_badge_id constraint removed per policy
            if ($violations) {
                return $this->view('mentorship/request', ['listing' => $listing, 'error' => implode(' ', $violations)]);
            }
            // Prevent more than one application to the same listing (any status)
            if (MentorshipRequest::hasAnyForListing($this->config, (int)$student['student_id'], $listingId)) {
                return $this->view('mentorship/request', ['listing' => $listing, 'error' => 'You have already applied to this mentorship offer. Multiple applications to the same offer are not allowed.']);
            }
            // Prevent new requests when an active mentorship window exists with the same mentor
            // and for 1 calendar month after the mentorship window ends
            $alumniId = (int)$listing['alumni_id'];
            if (MentorshipRequest::hasActiveWithAlumni($this->config, (int)$student['student_id'], $alumniId)) {
                return $this->view('mentorship/request', ['listing' => $listing, 'error' => 'You are currently in (or recently completed) a mentorship window with this mentor. You can apply again one calendar month after the last mentorship window ends.']);
            }

            // Removed calendar-month throttle per policy

            $bid = max($listing['min_coin_bid'], (int)($_POST['bid_amount'] ?? $listing['min_coin_bid']));
            $maxBid = \Nexus\Helpers\Setting::get($this->config, 'app.coins.max_bid_per_request', 1000, 'integer');
            $bid = min($bid, $maxBid);
            $message = trim($_POST['message'] ?? '');
            $isFree = (bool)($_POST['is_free_request'] ?? false);

            // Use free quota if available and requested
            if ($isFree) {
                // check remaining quota and consume immediately upon submission
                $pdo = Database::pdo($this->config);
                // Refresh free tickets if 4-month window elapsed
                Student::refreshFreeTicketsIfWindowElapsed($this->config, (int)$student['student_id']);
                $st = $pdo->prepare('SELECT free_mentorship_requests FROM students WHERE student_id = ?');
                $st->execute([(int)$student['student_id']]);
                $quota = (int)($st->fetchColumn() ?: 0);
                if ($quota <= 0) {
                    $isFree = false;
                } else {
                    // consume now so the header and UI reflect immediately
                    if (!Student::consumeFreeRequest($this->config, (int)$student['student_id'])) {
                        $isFree = false;
                    }
                }
            }

            // If not free, ensure sufficient balance
            if (!$isFree) {
                UserWallet::ensureExists($this->config, (int)$user['user_id']);
                $wallet = UserWallet::getByUserId($this->config, (int)$user['user_id']);
                if ((int)$wallet['balance'] < $bid) {
                    return $this->view('mentorship/request', ['listing' => $listing, 'error' => 'Insufficient balance', 'bid' => $bid, 'message' => $message]);
                }
                // Daily spend cap
                $pdo = Database::pdo($this->config);
                $spentToday = (int)($pdo->query('SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id = ' . (int)$user['user_id'] . " AND DATE(created_at) = CURRENT_DATE() AND type_id IN (SELECT type_id FROM transaction_types WHERE is_earning = 0)")->fetchColumn() ?: 0);
                $maxDaily = (int)($this->config->get('app.coins.max_daily_spend') ?? 2000);
                $maxDaily = \Nexus\Helpers\Setting::get($this->config, 'app.coins.max_daily_spend', 2000, 'integer');
                if ($spentToday + $bid > $maxDaily) {
                    return $this->view('mentorship/request', ['listing' => $listing, 'error' => 'Daily spend limit reached', 'bid' => $bid, 'message' => $message]);
                }
            }

            $requestId = MentorshipRequest::create($this->config, (int)$student['student_id'], $listingId, $bid, $message, $isFree);
            $this->redirect('/mentorship/requests/mine');
            return '';
        }
        // For GET: show remaining free tickets info and whether student meets min_cgpa (if set)
        $student = Student::findByUserId($this->config, (int)$user['user_id']);
        if (!$student) {
            Student::upsert($this->config, (int)$user['user_id'], []);
            $student = Student::findByUserId($this->config, (int)$user['user_id']);
        }
        Student::refreshFreeTicketsIfWindowElapsed($this->config, (int)$student['student_id']);
        $pdo = Database::pdo($this->config);
        $st = $pdo->prepare('SELECT free_mentorship_requests FROM students WHERE student_id = ?');
        $st->execute([(int)$student['student_id']]);
        $quota = (int)($st->fetchColumn() ?: 0);
        $eligError = null;
        if (!empty($listing['min_cgpa'])) {
            $studentCgpa = (float)($student['cgpa'] ?? 0);
            if ($studentCgpa <= 0 || $studentCgpa + 1e-6 < (float)$listing['min_cgpa']) {
                $eligError = 'You do not meet the minimum CGPA requirement for this listing.';
            }
        }
        // Also block if student already applied to this listing
        if ($eligError === null) {
            if (\Nexus\Models\MentorshipRequest::hasAnyForListing($this->config, (int)$student['student_id'], $listingId)) {
                $eligError = 'You have already applied to this mentorship offer. Multiple applications to the same offer are not allowed.';
            }
        }
        // Also block if active/recent mentorship with this mentor
        if ($eligError === null) {
            $alumniId = (int)$listing['alumni_id'];
            if (\Nexus\Models\MentorshipRequest::hasActiveWithAlumni($this->config, (int)$student['student_id'], $alumniId)) {
                $eligError = 'You can apply to this mentor again one calendar month after your last mentorship window ends.';
            }
        }
        return $this->view('mentorship/request', ['listing' => $listing, 'quota' => $quota, 'eligibility_error' => $eligError]);
    }

    public function myRequests(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
    if ($user['role'] !== 'student') { http_response_code(403); echo 'Only students can view their requests'; return ''; }
        if (\Nexus\Helpers\Restrictions::isMentorshipSuspended($this->config, (int)$user['user_id'])) { http_response_code(403); echo 'Your mentorship features are currently suspended.'; return ''; }
        $student = Student::findByUserId($this->config, (int)$user['user_id']);
        if (!$student) {
            Student::upsert($this->config, (int)$user['user_id'], []);
            $student = Student::findByUserId($this->config, (int)$user['user_id']);
        }
        $requests = MentorshipRequest::forStudent($this->config, (int)$student['student_id']);
        return $this->view('mentorship/my_requests', ['requests' => $requests]);
    }

    // Student boosts a pending request's priority by spending coins
    public function boostRequest(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if ($user['role'] !== 'student') { http_response_code(403); echo 'Only students can boost'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        if (\Nexus\Helpers\Restrictions::isMentorshipSuspended($this->config, (int)$user['user_id'])) { http_response_code(403); echo 'Your mentorship features are currently suspended.'; return ''; }

        $req = MentorshipRequest::find($this->config, $requestId);
        if (!$req || ($req['status'] ?? '') !== 'pending') { http_response_code(404); echo 'Request not found'; return ''; }
        // Ensure ownership
        $student = Student::findByUserId($this->config, (int)$user['user_id']);
        if (!$student || (int)$student['student_id'] !== (int)$req['student_id']) { http_response_code(403); echo 'Forbidden'; return ''; }

        $boostAmount = max(1, (int)($_POST['boost_amount'] ?? 0));
    $maxBoostPerRequest = \Nexus\Helpers\Setting::get($this->config, 'app.coins.max_boost_per_request', 200, 'integer');
        if ($boostAmount > $maxBoostPerRequest) { $boostAmount = $maxBoostPerRequest; }

    // Spend coins immediately (no escrow) and increase priority_score by weight * boost
    UserWallet::ensureExists($this->config, (int)$user['user_id']);
    $wallet = UserWallet::getByUserId($this->config, (int)$user['user_id']);
    if (!$wallet) { http_response_code(400); echo 'Wallet not found'; return ''; }
    if ((int)$wallet['balance'] < $boostAmount) { http_response_code(400); echo 'Insufficient balance'; return ''; }

        $weight = (int)($this->config->get('app.coins.priority_weight') ?? 1);
    $weight = \Nexus\Helpers\Setting::get($this->config, 'app.coins.priority_weight', 1, 'integer');
        $pdo = Database::pdo($this->config);
        $pdo->beginTransaction();
        try {
            // Deduct coins (spend)
            $ok = UserWallet::transact($this->config, (int)$user['user_id'], 'Mentorship Priority Boost', $boostAmount, false, 'Boost mentorship request priority', 'mentorship_request', (int)$requestId);
            if (!$ok) {
                $pdo->rollBack();
                http_response_code(400);
                if ((bool)($this->config->get('app.debug') ?? false)) {
                    $role = (string)($user['role'] ?? '');
                    $wb = (int)($wallet['balance'] ?? 0);
                    $reason = \Nexus\Models\UserWallet::getLastError();
                    echo 'Boost failed (debug: role=' . htmlspecialchars($role) . ', balance=' . $wb . ', amount=' . $boostAmount . ', reason=' . htmlspecialchars((string)$reason) . ').';
                } else {
                    echo 'Boost failed';
                }
                return '';
            }
            // Increase priority with cap
            $maxPriority = (int)($this->config->get('app.coins.max_priority_score') ?? 100000);
            $maxPriority = \Nexus\Helpers\Setting::get($this->config, 'app.coins.max_priority_score', 100000, 'integer');
            $inc = $weight * $boostAmount;
            $st = $pdo->prepare('UPDATE mentorship_requests SET priority_score = LEAST(priority_score + :inc, :cap) WHERE request_id = :id AND status = "pending"');
            $st->execute([':inc' => $inc, ':cap' => $maxPriority, ':id' => $requestId]);
            if ($st->rowCount() !== 1) { $pdo->rollBack(); http_response_code(400); echo 'Unable to apply boost'; return ''; }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            http_response_code(500); echo 'Failed to boost'; return '';
        }

        $this->redirect('/mentorship/requests/mine');
        return '';
    }

    public function listingRequests(int $listingId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        $isAdmin = (($user['role'] ?? '') === 'admin');
        $isAlumni = (($user['role'] ?? '') === 'alumni');
        if (!$isAdmin && !$isAlumni) { http_response_code(403); echo 'Only mentors or admins can view requests'; return ''; }
        if (!$isAdmin && \Nexus\Helpers\Restrictions::isMentorshipSuspended($this->config, (int)$user['user_id'])) { http_response_code(403); echo 'Your mentorship features are currently suspended.'; return ''; }
        $listing = MentorshipListing::find($this->config, $listingId);
        if (!$listing) { http_response_code(404); echo 'Listing not found'; return ''; }
        if ($isAlumni) {
            $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
            if ((int)$listing['alumni_id'] !== (int)$alumni['alumni_id']) { http_response_code(403); echo 'Not your listing'; return ''; }
        }
        $requests = MentorshipRequest::listForListing($this->config, $listingId);
        // Prepare a map of user_ids per request for visibility checks in the view
        return $this->view('mentorship/listing_requests', [
            'requests' => $requests,
            'listing' => $listing,
            'isAdmin' => $isAdmin,
        ]);
    }

    // Student or mentor: request cancellation (requires admin approval)
    public function requestCancellation(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $req = MentorshipRequest::findWithUsers($this->config, $requestId);
        if (!$req || !in_array(($req['status'] ?? ''), ['accepted','pending'], true)) { http_response_code(404); echo 'Request not found'; return ''; }
        $role = (string)($user['role'] ?? '');
        // Only student or alumni listed on the request can initiate
        $allowed = false;
        if ($role === 'student') {
            $stu = Student::findByUserId($this->config, (int)$user['user_id']);
            $allowed = $stu && (int)$stu['student_id'] === (int)$req['student_id'];
        } elseif ($role === 'alumni') {
            $al = Alumni::findByUserId($this->config, (int)$user['user_id']);
            $listing = MentorshipListing::find($this->config, (int)$req['listing_id']);
            $allowed = $al && $listing && (int)$al['alumni_id'] === (int)$listing['alumni_id'];
        } else {
            http_response_code(403); echo 'Forbidden'; return ''; }
        if (!$allowed) { http_response_code(403); echo 'Forbidden'; return ''; }
        if (MentorshipCancellation::existsPendingForRequest($this->config, $requestId)) { http_response_code(409); echo 'Cancellation already requested'; return ''; }
        $reason = trim((string)($_POST['reason'] ?? ''));
        MentorshipCancellation::request($this->config, $requestId, (int)$user['user_id'], $role, $reason);
        // Optional: notify admins (first admin user)
        try {
            $pdo = Database::pdo($this->config);
            $admin = (int)($pdo->query("SELECT user_id FROM users WHERE role='admin' ORDER BY user_id ASC LIMIT 1")->fetchColumn() ?: 0);
            if ($admin > 0) { \Nexus\Models\Notification::send($this->config, $admin, 'Mentorship cancellation requested', 'A cancellation was requested and awaits approval.', 'mentorship', 'mentorship_request', (int)$requestId, '/admin'); }
        } catch (\Throwable $e) {}
        if ($role === 'student') { $this->redirect('/mentorship/requests/mine'); } else { $this->redirect('/mentorship/listing/' . (int)$req['listing_id'] . '/requests'); }
        return '';
    }

    // Admin: approve cancellation, handles refund/slots and sets request to cancelled
    public function approveCancellation(int $cancellationId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'admin') { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $c = MentorshipCancellation::find($this->config, $cancellationId);
        if (!$c || ($c['status'] ?? '') !== 'pending') { http_response_code(404); echo 'Not found'; return ''; }
        $req = MentorshipRequest::findWithUsers($this->config, (int)$c['request_id']);
        if (!$req) { http_response_code(404); echo 'Request missing'; return ''; }
        $pdo = Database::pdo($this->config);
        $pdo->beginTransaction();
        try {
            // Approve flag
            MentorshipCancellation::approve($this->config, $cancellationId, (int)$user['user_id']);
            // Update request status and adjust slots if it was accepted
            $wasAccepted = ($req['status'] ?? '') === 'accepted';
            if ($wasAccepted && !(bool)$req['is_free_request']) {
                $studentUserId = (int)$req['student_user_id'];
                \Nexus\Models\UserWallet::ensureExists($this->config, $studentUserId);
                \Nexus\Models\UserWallet::transact($this->config, $studentUserId, 'Mentorship Refund', (int)$req['bid_amount'], true, 'Refund on cancellation', 'mentorship_request', (int)$req['request_id']);
            }
            MentorshipRequest::updateStatus($this->config, (int)$req['request_id'], 'cancelled');
            if ($wasAccepted) { MentorshipListing::decrementSlot($this->config, (int)$req['listing_id']); }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            http_response_code(500); echo 'Failed to approve'; return '';
        }
        // Notify parties
        try {
            \Nexus\Models\Notification::send($this->config, (int)$req['student_user_id'], 'Mentorship Cancelled', 'Your mentorship was cancelled by admin approval.', 'mentorship', 'mentorship_request', (int)$req['request_id'], '/mentorship/requests/mine');
            \Nexus\Models\Notification::send($this->config, (int)$req['alumni_user_id'], 'Mentorship Cancelled', 'Mentorship was cancelled by admin approval.', 'mentorship', 'mentorship_request', (int)$req['request_id'], '/mentorship');
        } catch (\Throwable $e) {}
        $this->redirect('/admin');
        return '';
    }

    // Admin: reject cancellation
    public function rejectCancellation(int $cancellationId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'admin') { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $c = MentorshipCancellation::find($this->config, $cancellationId);
        if (!$c || ($c['status'] ?? '') !== 'pending') { http_response_code(404); echo 'Not found'; return ''; }
        MentorshipCancellation::reject($this->config, $cancellationId, (int)$user['user_id']);
        // Notify requester
        try { \Nexus\Models\Notification::send($this->config, (int)$c['requested_by_user_id'], 'Cancellation Rejected', 'Your mentorship cancellation request was rejected.', 'mentorship', 'mentorship_request', (int)$c['request_id'], '/mentorship/requests/mine'); } catch (\Throwable $e) {}
        $this->redirect('/admin');
        return '';
    }

    // Admin direct cancel without prior request
    public function adminCancel(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'admin') { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $req = MentorshipRequest::findWithUsers($this->config, $requestId);
        if (!$req || !in_array(($req['status'] ?? ''), ['accepted','pending'], true)) { http_response_code(404); echo 'Request not found'; return ''; }
        $pdo = Database::pdo($this->config);
        $pdo->beginTransaction();
        try {
            $wasAccepted = ($req['status'] ?? '') === 'accepted';
            // If not free and was accepted, refund student
            if ($wasAccepted && !(bool)$req['is_free_request']) {
                $studentUserId = (int)$req['student_user_id'];
                UserWallet::ensureExists($this->config, $studentUserId);
                UserWallet::transact($this->config, $studentUserId, 'Mentorship Refund', (int)$req['bid_amount'], true, 'Admin direct cancellation refund', 'mentorship_request', (int)$req['request_id']);
            }
            MentorshipRequest::updateStatus($this->config, (int)$req['request_id'], 'cancelled');
            if ($wasAccepted) { MentorshipListing::decrementSlot($this->config, (int)$req['listing_id']); }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            http_response_code(500); echo 'Failed to cancel'; return '';
        }
        // Notify parties
        try {
            Notification::send($this->config, (int)$req['student_user_id'], 'Mentorship Cancelled', 'Your mentorship was cancelled by an admin.', 'mentorship', 'mentorship_request', (int)$req['request_id'], '/mentorship/requests/mine');
            Notification::send($this->config, (int)$req['alumni_user_id'], 'Mentorship Cancelled', 'A mentorship with your listing was cancelled by an admin.', 'mentorship', 'mentorship_request', (int)$req['request_id'], '/mentorship');
        } catch (\Throwable $e) {}
        $this->redirect('/admin');
        return '';
    }

    // Admin: force delete a listing. Cancels pending/accepted requests, issues refunds where needed, notifies parties, then deletes the listing.
    public function adminForceDelete(int $listingId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'admin') { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!\Nexus\Helpers\Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');

        $listing = MentorshipListing::find($this->config, $listingId);
        if (!$listing) { http_response_code(404); echo 'Listing not found'; return ''; }

        // Gather all requests for this listing
        $reqs = MentorshipRequest::listForListing($this->config, $listingId);
        foreach ($reqs as $r) {
            $rid = (int)($r['request_id'] ?? 0);
            if ($rid <= 0) { continue; }
            // Load with user mapping (student/alumni user ids)
            $req = MentorshipRequest::findWithUsers($this->config, $rid);
            if (!$req) { continue; }
            $status = (string)($req['status'] ?? '');
            $studentUserId = (int)($req['student_user_id'] ?? 0);
            $alumniUserId = (int)($req['alumni_user_id'] ?? 0);
            // If accepted, refund student if not free and free up slot
            if ($status === 'accepted') {
                if (!(bool)($req['is_free_request'] ?? false)) {
                    try {
                        UserWallet::ensureExists($this->config, $studentUserId);
                        UserWallet::transact($this->config, $studentUserId, 'Mentorship Refund', (int)($req['bid_amount'] ?? 0), true, 'Admin force delete of listing', 'mentorship_request', $rid);
                    } catch (\Throwable $e) {}
                }
                // Decrement slot for accepted
                try { MentorshipListing::decrementSlot($this->config, (int)($req['listing_id'] ?? $listingId)); } catch (\Throwable $e) {}
            }
            // Cancel request if not already terminal
            if (!in_array($status, ['cancelled','completed','declined'], true)) {
                MentorshipRequest::updateStatus($this->config, $rid, 'cancelled');
            }
            // Notify student and alumni
            try {
                Notification::send($this->config, $studentUserId, 'Mentorship Listing Removed', 'An admin removed the mentorship listing. Your request was cancelled and any escrow was refunded if applicable.', 'mentorship', 'mentorship_request', $rid, '/mentorship');
                if ($alumniUserId > 0) {
                    Notification::send($this->config, $alumniUserId, 'Mentorship Listing Removed', 'Your mentorship listing was removed by an admin; all associated requests were cancelled.', 'mentorship', 'mentorship_request', $rid, '/mentorship');
                }
            } catch (\Throwable $e) {}
        }

        // Finally delete the listing
        MentorshipListing::delete($this->config, $listingId);
        try { \Nexus\Helpers\Flash::add('success', 'Listing force-deleted and related requests cleaned up.'); } catch (\Throwable $e) {}
        $this->redirect('/mentorship');
        return '';
    }

    public function acceptRequest(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
    if ($user['role'] !== 'alumni') { http_response_code(403); echo 'Only alumni can accept'; return ''; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
    if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        if (\Nexus\Helpers\Restrictions::isMentorshipSuspended($this->config, (int)$user['user_id'])) { http_response_code(403); echo 'Your mentorship features are currently suspended.'; return ''; }

        $req = MentorshipRequest::findWithUsers($this->config, $requestId);
    if (!$req || $req['status'] !== 'pending') { http_response_code(404); echo 'Request not found'; return ''; }
        $listing = MentorshipListing::find($this->config, (int)$req['listing_id']);
        if (!$listing || !$listing['is_active']) { http_response_code(404); echo 'Listing not found'; return ''; }
        $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
        if ((int)$listing['alumni_id'] !== (int)$alumni['alumni_id']) { http_response_code(403); echo 'Not your listing'; return ''; }
        if ((int)$listing['current_slots'] >= (int)$listing['max_slots']) { http_response_code(400); echo 'No slots available'; return ''; }

        // Enforce 1-month window: same student-alumni cannot have overlapping active mentorship
        if (!MentorshipRequest::canAccept($this->config, (int)$req['student_id'], (int)$listing['alumni_id'])) {
            http_response_code(409);
            echo 'This student already has an active mentorship window with you.';
            return '';
        }

        // Escrow hold on accept (no mentor credit yet) in a transaction
        $pdo = Database::pdo($this->config);
        $pdo->beginTransaction();
        try {
            if (!(bool)$req['is_free_request']) {
                $studentUserId = (int)$req['student_user_id'];
                UserWallet::ensureExists($this->config, $studentUserId);
                $amount = (int)$req['bid_amount'];
                $held = UserWallet::transact($this->config, $studentUserId, 'Mentorship Escrow Hold', $amount, false, 'Escrow hold for mentorship', 'mentorship_request', (int)$requestId);
                if (!$held) { $pdo->rollBack(); http_response_code(400); echo 'Insufficient balance'; return ''; }
                Gamify::addReputation(
                    $this->config,
                    $studentUserId,
                    2,
                    'mentorship:request:accepted',
                    'mentorship_request',
                    (int)$requestId
                );
            }
            // Accept and set 1-month window
            MentorshipRequest::accept($this->config, $requestId, null, null);
            $inc = MentorshipListing::incrementSlot($this->config, (int)$req['listing_id']);
            if (!$inc) { $pdo->rollBack(); http_response_code(400); echo 'No slots available'; return ''; }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            http_response_code(500); echo 'Failed to accept request'; return '';
        }

    $this->redirect('/mentorship/listing/' . (int)$req['listing_id'] . '/requests');
    return '';
    }

    public function declineRequest(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
    if ($user['role'] !== 'alumni') { http_response_code(403); echo 'Only alumni can decline'; return ''; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        if (\Nexus\Helpers\Restrictions::isMentorshipSuspended($this->config, (int)$user['user_id'])) { http_response_code(403); echo 'Your mentorship features are currently suspended.'; return ''; }

        $req = MentorshipRequest::find($this->config, $requestId);
    if (!$req || $req['status'] !== 'pending') { http_response_code(404); echo 'Request not found'; return ''; }
        $listing = MentorshipListing::find($this->config, (int)$req['listing_id']);
        $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
        if (!$listing || (int)$listing['alumni_id'] !== (int)$alumni['alumni_id']) { http_response_code(403); echo 'Not your listing'; return ''; }

    MentorshipRequest::updateStatus($this->config, $requestId, 'declined');
    $this->redirect('/mentorship/listing/' . (int)$req['listing_id'] . '/requests');
    return '';
    }

    public function reserveRequest(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if ($user['role'] !== 'alumni') { http_response_code(403); echo 'Only alumni can reserve'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $alumni = \Nexus\Models\Alumni::findByUserId($this->config, (int)$user['user_id']);
        $minutes = (int)($this->config->get('app.coins.reservation_minutes') ?? 10);
    $minutes = \Nexus\Helpers\Setting::get($this->config, 'app.coins.reservation_minutes', 10, 'integer');
        $ok = MentorshipRequest::reserveForMentor($this->config, $requestId, (int)$alumni['alumni_id'], $minutes);
        if (!$ok) { http_response_code(400); echo 'Unable to reserve'; return ''; }
        $req = MentorshipRequest::find($this->config, $requestId);
        $this->redirect('/mentorship/listing/' . (int)$req['listing_id'] . '/requests');
        return '';
    }

    public function releaseReservation(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if ($user['role'] !== 'alumni') { http_response_code(403); echo 'Only alumni can release'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $alumni = \Nexus\Models\Alumni::findByUserId($this->config, (int)$user['user_id']);
        $ok = MentorshipRequest::releaseReservation($this->config, $requestId, (int)$alumni['alumni_id']);
        if (!$ok) { http_response_code(400); echo 'Unable to release'; return ''; }
        $req = MentorshipRequest::find($this->config, $requestId);
        $this->redirect('/mentorship/listing/' . (int)$req['listing_id'] . '/requests');
        return '';
    }

    public function extendReservation(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if ($user['role'] !== 'alumni') { http_response_code(403); echo 'Only alumni can extend'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $alumni = \Nexus\Models\Alumni::findByUserId($this->config, (int)$user['user_id']);
        $minutes = (int)($this->config->get('app.coins.reservation_minutes') ?? 10);
        $maxext = (int)($this->config->get('app.coins.max_reservation_extensions') ?? 1);
    $maxext = \Nexus\Helpers\Setting::get($this->config, 'app.coins.max_reservation_extensions', 1, 'integer');
        $ok = MentorshipRequest::extendReservation($this->config, $requestId, (int)$alumni['alumni_id'], $minutes, $maxext);
        if (!$ok) { http_response_code(400); echo 'Unable to extend reservation'; return ''; }
        $req = MentorshipRequest::find($this->config, $requestId);
        $this->redirect('/mentorship/listing/' . (int)$req['listing_id'] . '/requests');
        return '';
    }

    public function schedule(int $requestId): string
    {
        // Deprecated in this flow: scheduling replaced by direct chat
        http_response_code(410);
        echo 'Scheduling via this endpoint has been replaced by chat.';
        return '';
    }

    // Start or open a direct chat with the student for this accepted request
    public function chat(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'alumni') { http_response_code(403); echo 'Only alumni can chat from listing requests'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        if (\Nexus\Helpers\Restrictions::isMentorshipSuspended($this->config, (int)$user['user_id'])) { http_response_code(403); echo 'Your mentorship features are currently suspended.'; return ''; }
        $req = MentorshipRequest::findWithUsers($this->config, $requestId);
        if (!$req || ($req['status'] ?? '') !== 'accepted') { http_response_code(404); echo 'Request invalid'; return ''; }
        $listing = MentorshipListing::find($this->config, (int)$req['listing_id']);
        $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
        if (!$listing || (int)$listing['alumni_id'] !== (int)$alumni['alumni_id']) { http_response_code(403); echo 'Not your listing'; return ''; }
        // Ensure messaging is allowed per ACL
        $me = (int)$user['user_id'];
        $studentUserId = (int)$req['student_user_id'];
        if (!\Nexus\Helpers\Messaging::canMessage($this->config, $me, $studentUserId)) { http_response_code(403); echo 'Messaging not allowed.'; return ''; }
        // Reuse existing conversation or create a new one
        $cid = \Nexus\Models\Conversation::findDirectBetween($this->config, $me, $studentUserId);
        if (!$cid) {
            $cid = \Nexus\Models\Conversation::create($this->config, $me, 'direct', null);
            \Nexus\Models\Conversation::addParticipant($this->config, $cid, $me);
            \Nexus\Models\Conversation::addParticipant($this->config, $cid, $studentUserId);
        }
        $this->redirect('/messages/' . $cid);
        return '';
    }

    public function completeSession(int $sessionId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
    if ($user['role'] !== 'alumni') { http_response_code(403); echo 'Only alumni can complete'; return ''; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        if (\Nexus\Helpers\Restrictions::isMentorshipSuspended($this->config, (int)$user['user_id'])) { http_response_code(403); echo 'Your mentorship features are currently suspended.'; return ''; }

        $session = MentorshipSession::find($this->config, $sessionId);
        if (!$session) { http_response_code(404); echo 'Session not found'; return ''; }
        $req = MentorshipRequest::findWithUsers($this->config, (int)$session['request_id']);
        if (!$req || $req['status'] !== 'accepted') { http_response_code(409); echo 'Request not in accepted state'; return ''; }
        $listing = MentorshipListing::find($this->config, (int)$req['listing_id']);
        $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
        if ((int)$listing['alumni_id'] !== (int)$alumni['alumni_id']) { http_response_code(403); echo 'Not your session'; return ''; }

        // Release escrow to alumni if not free
        if (!(bool)$req['is_free_request']) {
            $alumniUserId = (int)$req['alumni_user_id'];
            $amount = (int)$req['bid_amount'];
            UserWallet::ensureExists($this->config, $alumniUserId);
            $ok = UserWallet::transact($this->config, $alumniUserId, 'Mentorship Escrow Release', $amount, true, 'Mentorship payout', 'mentorship_request', (int)$req['request_id']);
            if (!$ok) { http_response_code(500); echo 'Payout failed'; return ''; }
            Gamify::addReputation(
                $this->config,
                $alumniUserId,
                10,
                'mentorship:session:completed',
                'mentorship_request',
                (int)$req['request_id']
            );
        }

    MentorshipRequest::markCompleted($this->config, (int)$req['request_id']);
    MentorshipSession::markCompleted($this->config, $sessionId);
    // Free up a slot on listing after completion
    MentorshipListing::decrementSlot($this->config, (int)$req['listing_id']);
    // Apply cooldown to student (default 3 days)
    Student::applyCooldown($this->config, (int)$req['student_id'], 3);
    $this->redirect('/mentorship/listing/' . (int)$req['listing_id'] . '/requests');
    return '';
    }

    public function feedback(int $sessionId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->view('mentorship/feedback', ['sessionId' => $sessionId]);
        }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
        $feedback = trim((string)($_POST['feedback'] ?? ''));
        $session = MentorshipSession::find($this->config, $sessionId);
        if (!$session) { http_response_code(404); echo 'Session not found'; return ''; }
        $req = MentorshipRequest::findWithUsers($this->config, (int)$session['request_id']);
        $listing = MentorshipListing::find($this->config, (int)$req['listing_id']);
        if (($user['role'] ?? '') === 'student') {
            $student = Student::findByUserId($this->config, (int)$user['user_id']);
            if ((int)$student['student_id'] !== (int)$req['student_id']) { http_response_code(403); echo 'Forbidden'; return ''; }
            MentorshipSession::setStudentFeedback($this->config, $sessionId, $rating, $feedback);
        } elseif (($user['role'] ?? '') === 'alumni') {
            $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
            if ((int)$alumni['alumni_id'] !== (int)$listing['alumni_id']) { http_response_code(403); echo 'Forbidden'; return ''; }
            MentorshipSession::setMentorFeedback($this->config, $sessionId, $rating, $feedback);
        } else { http_response_code(403); echo 'Forbidden'; return ''; }
    $this->redirect('/mentorship/listing/' . (int)$listing['listing_id'] . '/requests');
    return '';
    }

    private static function studentUserIdByStudentId(Config $config, int $studentId): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT user_id FROM students WHERE student_id = ?');
        $stmt->execute([$studentId]);
        return (int)$stmt->fetchColumn();
    }

    private static function alumniUserId(Config $config, int $alumniId): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT user_id FROM alumni WHERE alumni_id = ?');
        $stmt->execute([$alumniId]);
        return (int)$stmt->fetchColumn();
    }
}
