<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Core\Database;
use Nexus\Helpers\Auth;
use Nexus\Helpers\Csrf;
use Nexus\Helpers\Gamify;
use Nexus\Helpers\Flash;
use Nexus\Models\Notification;
use Nexus\Helpers\Gate;
use Nexus\Models\ForumCategory;
use Nexus\Models\ForumPost;
use Nexus\Models\PostVote;
use Nexus\Models\UserWallet;

final class ForumController extends Controller
{
    public function index(): string
    {
        Auth::enforceAuth();
        $categories = ForumCategory::all($this->config);
        $recent = ForumPost::listRecent($this->config, 12);
        return $this->view('forum/index', compact('categories','recent'));
    }

    public function category(int $id): string
    {
        Auth::enforceAuth();
        $cat = ForumCategory::find($this->config, $id);
        if (!$cat) { http_response_code(404); echo 'Category not found'; return ''; }
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $posts = ForumPost::listByCategory($this->config, $id, $limit + 1, $offset); // fetch one extra to detect next
    $hasNext = count($posts) > $limit;
    if ($hasNext) { array_pop($posts); }
    return $this->view('forum/category', ['category' => $cat, 'posts' => $posts, 'page' => $page, 'hasNext' => $hasNext]);
    }

    public function show(int $id): string
    {
        Auth::enforceAuth();
    $post = ForumPost::findWithAuthor($this->config, $id);
        if (!$post || ($post['post_type'] !== 'question' && $post['post_type'] !== 'discussion')) { http_response_code(404); echo 'Post not found'; return ''; }
        // Only show unapproved posts to their author or admins
    $uid = (int)(Auth::id() ?? 0);
        $isAdmin = false;
        try {
            $isAdmin = (Auth::user()['role'] ?? '') === 'admin' || Gate::has($this->config, $uid, 'manage.permissions');
        } catch (\Throwable $e) {}
        if ((int)($post['is_approved'] ?? 1) !== 1 && (int)($post['author_id'] ?? 0) !== $uid && !$isAdmin) {
            http_response_code(403); echo 'Awaiting moderation'; return '';
        }
        ForumPost::incrementView($this->config, $id);
        $answers = ForumPost::listAnswers($this->config, $id);

        $currentVote = null;
        $answerVotes = [];
        if ($uid > 0) {
            try {
                $currentVote = PostVote::get($this->config, $uid, (int)$post['post_id']);
            } catch (\Throwable $e) { $currentVote = null; }
            foreach ($answers as $ans) {
                $pid = (int)($ans['post_id'] ?? 0);
                if ($pid > 0) {
                    try {
                        $answerVotes[$pid] = PostVote::get($this->config, $uid, $pid);
                    } catch (\Throwable $e) {
                        $answerVotes[$pid] = null;
                    }
                }
            }
        }

        return $this->view('forum/show', [
            'post' => $post,
            'answers' => $answers,
            'currentVote' => $currentVote,
            'answerVotes' => $answerVotes,
        ]);
    }

    public function pending(): string
    {
        Auth::enforceAuth();
        $uid = (int)(Auth::id() ?? 0);
        $isAdmin = false;
        try { $isAdmin = (Auth::user()['role'] ?? '') === 'admin' || Gate::has($this->config, $uid, 'manage.permissions'); } catch (\Throwable $e) {}
        $tab = isset($_GET['tab']) ? (string)$_GET['tab'] : 'pending';
        $perPage = isset($_GET['per_page']) && $_GET['per_page'] !== '' ? max(5, min(100, (int)$_GET['per_page'])) : 20;
        $page = isset($_GET['page']) && $_GET['page'] !== '' ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $perPage;
        if ($tab === 'rejected') {
            $total = $isAdmin ? ForumPost::countRejected($this->config, null) : ForumPost::countRejected($this->config, $uid);
            $list = $isAdmin ? ForumPost::listRejected($this->config, null, $perPage, $offset) : ForumPost::listRejected($this->config, $uid, $perPage, $offset);
        } elseif ($tab === 'approved') {
            $total = $isAdmin ? ForumPost::countApproved($this->config, null) : ForumPost::countApproved($this->config, $uid);
            $list = $isAdmin ? ForumPost::listApproved($this->config, null, $perPage, $offset) : ForumPost::listApproved($this->config, $uid, $perPage, $offset);
        } else {
            $total = $isAdmin ? ForumPost::countPending($this->config, null) : ForumPost::countPending($this->config, $uid);
            $list = $isAdmin ? ForumPost::listPending($this->config, null, $perPage, $offset) : ForumPost::listPending($this->config, $uid, $perPage, $offset);
        }
        $pages = max(1, (int)ceil($total / $perPage));
        $hasNext = $page < $pages;
        // Fetch last-seen markers for highlighting (non-admin only)
        $lastSeenApproved = null; $lastSeenRejected = null;
        if (!$isAdmin) {
            try {
                // Prefer session fallback if present for immediate UX
                if (!empty($_SESSION['forum_seen_approved_' . $uid])) {
                    $lastSeenApproved = (string)$_SESSION['forum_seen_approved_' . $uid];
                } else {
                    $lsa = \Nexus\Models\SystemSetting::get($this->config, 'forum_seen_approved_' . $uid);
                    if ($lsa && isset($lsa['setting_value'])) { $lastSeenApproved = trim((string)$lsa['setting_value']); }
                }
            } catch (\Throwable $e) {}
            try {
                if (!empty($_SESSION['forum_seen_rejected_' . $uid])) {
                    $lastSeenRejected = (string)$_SESSION['forum_seen_rejected_' . $uid];
                } else {
                    $lsr = \Nexus\Models\SystemSetting::get($this->config, 'forum_seen_rejected_' . $uid);
                    if ($lsr && isset($lsr['setting_value'])) { $lastSeenRejected = trim((string)$lsr['setting_value']); }
                }
            } catch (\Throwable $e) {}
        }
        return $this->view('forum/pending', [
            'pending' => $list,
            'isAdmin' => $isAdmin,
            'tab' => $tab,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
            'total' => $total,
            'hasNext' => $hasNext,
            'last_seen_approved' => $lastSeenApproved,
            'last_seen_rejected' => $lastSeenRejected,
        ]);
    }

    public function approve(int $id): string
    {
        Auth::enforceAuth();
    $uid = (int)(Auth::id() ?? 0);
    // Only admins/moderators can approve
    $isAdmin = ((Auth::user()['role'] ?? '') === 'admin') || Gate::has($this->config, $uid, 'manage.permissions');
    if (!$isAdmin) { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        ForumPost::approve($this->config, $id, $uid);
        // Notify author
        try {
            $post = ForumPost::find($this->config, $id);
            if ($post) {
                Notification::send($this->config, (int)$post['author_id'], 'Post Approved', 'Your forum post "' . ($post['title'] ?? substr((string)$post['content'], 0, 60)) . '" was approved.', 'forum_moderation', 'forum_post', (int)$id, '/forum/post/' . $id);
            }
        } catch (\Throwable $e) {}
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        if (stripos($accept, 'application/json') !== false) {
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'post_id' => $id]);
        }
        $this->redirect('/forum/pending');
        return '';
    }

    public function reject(int $id): string
    {
        Auth::enforceAuth();
    $uid = (int)(Auth::id() ?? 0);
    $isAdmin = ((Auth::user()['role'] ?? '') === 'admin') || Gate::has($this->config, $uid, 'manage.permissions');
    if (!$isAdmin) { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $reason = trim((string)($_POST['reason'] ?? ''));
        ForumPost::reject($this->config, $id, $uid, $reason !== '' ? $reason : null);
        // Notify author
        try {
            $post = ForumPost::find($this->config, $id);
            if ($post) {
                Notification::send($this->config, (int)$post['author_id'], 'Post Rejected', 'Your forum post "' . ($post['title'] ?? substr((string)$post['content'], 0, 60)) . '" was rejected.' . ($reason !== '' ? (' Reason: ' . $reason) : ''), 'forum_moderation', 'forum_post', (int)$id, '/forum/pending?tab=rejected');
            }
        } catch (\Throwable $e) {}
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        if (stripos($accept, 'application/json') !== false) {
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'post_id' => $id, 'status' => 'rejected']);
        }
        $this->redirect('/forum/pending');
        return '';
    }

    public function bulkModerate(): string
    {
        Auth::enforceAuth();
    $uid = (int)(Auth::id() ?? 0);
    $isAdmin = ((Auth::user()['role'] ?? '') === 'admin') || Gate::has($this->config, $uid, 'manage.permissions');
    if (!$isAdmin) { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $action = $_POST['action'] ?? '';
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) { $this->redirect('/forum/pending'); return ''; }
        $processed = [];
        foreach ($ids as $raw) {
            $pid = (int)$raw;
            if ($pid <= 0) continue;
            if ($action === 'approve') {
                ForumPost::approve($this->config, $pid, $uid);
                try {
                    $post = ForumPost::find($this->config, $pid);
                    if ($post) Notification::send($this->config, (int)$post['author_id'], 'Post Approved', 'Your forum post "' . ($post['title'] ?? substr((string)$post['content'], 0, 60)) . '" was approved.', 'forum_moderation', 'forum_post', (int)$pid, '/forum/post/' . $pid);
                } catch (\Throwable $e) {}
                $processed[] = $pid;
            } elseif ($action === 'reject') {
                $reason = trim((string)($_POST['reason_' . $pid] ?? ''));
                ForumPost::reject($this->config, $pid, $uid, $reason !== '' ? $reason : null);
                try {
                    $post = ForumPost::find($this->config, $pid);
                    if ($post) Notification::send($this->config, (int)$post['author_id'], 'Post Rejected', 'Your forum post "' . ($post['title'] ?? substr((string)$post['content'], 0, 60)) . '" was rejected.' . ($reason !== '' ? (' Reason: ' . $reason) : ''), 'forum_moderation', 'forum_post', (int)$pid, '/forum/pending?tab=rejected');
                } catch (\Throwable $e) {}
                $processed[] = $pid;
            }
        }
        $this->redirect('/forum/pending');
        return '';
    }

    public function create(): string
    {
        Auth::enforceAuth();
        // Social suspension blocks creating posts for non-admins
        $uid = (int)(Auth::id() ?? 0);
        $me = Auth::user();
        try { $isAdmin = (($me['role'] ?? '') === 'admin') || Gate::has($this->config, $uid, 'manage.permissions'); } catch (\Throwable $e) { $isAdmin = false; }
        if (!$isAdmin && \Nexus\Helpers\Restrictions::isSocialSuspended($this->config, $uid)) { http_response_code(403); echo 'Your social features are currently suspended.'; return ''; }
        $categories = ForumCategory::all($this->config);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
            $user = Auth::user();
            $title = trim((string)($_POST['title'] ?? ''));
            $content = trim((string)($_POST['content'] ?? ''));
            $categoryId = (int)($_POST['category_id'] ?? 0);
            if ($title === '' || $content === '' || $categoryId <= 0) {
                return $this->view('forum/create', ['categories' => $categories, 'error' => 'All fields are required.']);
            }
            $postId = ForumPost::createQuestion($this->config, (int)$user['user_id'], $categoryId, $title, $content);
            // Auto-approve if admin
            if ($isAdmin) { ForumPost::approve($this->config, $postId, $uid); }
            $this->redirect('/forum/post/' . $postId);
            return '';
        }
        return $this->view('forum/create', ['categories' => $categories]);
    }

    public function answer(int $id): string
    {
        Auth::enforceAuth();
        $uid = (int)(Auth::id() ?? 0);
        $me = Auth::user();
        try { $isAdmin = (($me['role'] ?? '') === 'admin') || Gate::has($this->config, $uid, 'manage.permissions'); } catch (\Throwable $e) { $isAdmin = false; }
        if (!$isAdmin && \Nexus\Helpers\Restrictions::isSocialSuspended($this->config, $uid)) { http_response_code(403); echo 'Your social features are currently suspended.'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
    if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $parent = ForumPost::find($this->config, $id);
        if (!$parent || ($parent['post_type'] !== 'question' && $parent['post_type'] !== 'discussion')) { http_response_code(404); echo 'Post not found'; return ''; }
        $content = trim((string)($_POST['content'] ?? ''));
    if ($content === '') { $this->redirect('/forum/post/' . $id); return ''; }
        $user = Auth::user();
    $ansId = ForumPost::createAnswer($this->config, (int)$user['user_id'], (int)$parent['category_id'], $id, $content);
    if ($isAdmin) { ForumPost::approve($this->config, $ansId, $uid); }
    // Reward student commenters with configurable coins
    if ((string)($user['role'] ?? '') === 'student') {
    $commentCoins = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.comment_post_coins', 0, 'integer');
        if ($commentCoins > 0) {
            UserWallet::transact(
                $this->config,
                (int)$user['user_id'],
                'Forum Comment Participation',
                $commentCoins,
                true,
                'Coins for posting a forum comment',
                'forum_comment',
                $ansId
            );
        }
    }
    $this->redirect('/forum/post/' . $id);
    return '';
    }

    public function vote(int $id): string
    {
        Auth::enforceAuth();
        $uid = (int)(Auth::id() ?? 0);
        $me = Auth::user();
        try { $isAdmin = (($me['role'] ?? '') === 'admin') || Gate::has($this->config, $uid, 'manage.permissions'); } catch (\Throwable $e) { $isAdmin = false; }
        if (!$isAdmin && \Nexus\Helpers\Restrictions::isSocialSuspended($this->config, $uid)) { http_response_code(403); echo 'Your social features are currently suspended.'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
    if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $vote = $_POST['vote'] ?? '';
    $post = ForumPost::findWithAuthor($this->config, $id);
        if (!$post) { http_response_code(404); echo 'Not found'; return ''; }
        $user = Auth::user();
    if ((int)$post['author_id'] === (int)$user['user_id']) { $this->redirect('/forum/post/' . $id); return ''; }
        // Enforce minimum reputation to downvote (admins bypass)
    $minRepToDown = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.min_rep_to_downvote', 50, 'integer');
        $voterWallet = \Nexus\Models\UserWallet::getByUserId($this->config, (int)$user['user_id']);
        $voterRep = (int)($voterWallet['reputation_score'] ?? 0);
        if ($vote === 'down' && !$isAdmin && $voterRep < $minRepToDown) {
            // Not enough reputation to downvote
            $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
            $xrw = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
            if (stripos($accept, 'application/json') !== false || strtolower($xrw) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                return json_encode(['ok' => false, 'error' => 'Not enough reputation to downvote.']);
            }
            Flash::add('warning', 'You need higher reputation to downvote.');
            $this->redirect('/forum/post/' . $id);
            return '';
        }

        $result = PostVote::set($this->config, (int)$user['user_id'], $id, $vote === 'down' ? 'downvote' : 'upvote');
        $currentVoteState = null;
        if (in_array($result, ['up', 'switch_up'], true)) {
            $currentVoteState = 'upvote';
        } elseif (in_array($result, ['down', 'switch_down'], true)) {
            $currentVoteState = 'downvote';
        } elseif ($result === 'noop') {
            $currentVoteState = ($vote === 'down') ? 'downvote' : 'upvote';
        }
    // Config-driven weights (use Setting helper for admin overrides)
    $repAnswerUp = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.answer_upvote_rep', 2, 'integer');
    $repQuestionUp = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.question_upvote_rep', 1, 'integer');
    $repDownAuthor = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.author_downvoted_rep', -1, 'integer');
    $repDownVoterCost = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.voter_downvote_cost_rep', -1, 'integer');
    $isAnswer = (string)($post['post_type'] ?? '') === 'answer';
    $upWeight = $isAnswer ? $repAnswerUp : $repQuestionUp;

        // Compute author's reputation delta based on result
        $authorDelta = 0;
        if ($result === 'up') {
            $authorDelta = $upWeight;
        } elseif ($result === 'down') {
            $authorDelta = $repDownAuthor;
        } elseif ($result === 'switch_up') {
            // From downvote to upvote: remove prior down penalty, add upvote weight
            $authorDelta = (-1 * $repDownAuthor) + $upWeight;
        } elseif ($result === 'switch_down') {
            // From upvote to downvote: remove prior upvote, add down penalty
            $authorDelta = (-1 * $upWeight) + $repDownAuthor;
        }
        $referenceType = $isAnswer ? 'forum_answer' : 'forum_post';
        if ($authorDelta !== 0) {
            Gamify::addReputation(
                $this->config,
                (int)$post['author_id'],
                $authorDelta,
                'forum:vote:' . $result,
                $referenceType,
                (int)$id
            );
        }
        // Optional coin mirror for author (student comments have dedicated tuning)
        $postAuthorRole = (string)($post['author_role'] ?? '');
        $isComment = $isAnswer;
        $coinUp = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.vote_up_coins', 0, 'integer');
        $coinDown = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.vote_down_penalty_coins', 0, 'integer');
        if ($isComment) {
            $coinUp = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.comment_upvote_coins', $coinUp, 'integer');
            $coinDown = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.comment_downvote_penalty_coins', $coinDown, 'integer');
        }
        if ($postAuthorRole !== 'student') {
            $coinUp = 0;
        }
        if ($postAuthorRole !== 'student') {
            $coinDown = 0;
        }
        $coinUp = max(0, $coinUp);
        $coinDown = max(0, $coinDown);
        $typePrefix = $isComment ? 'Forum Comment' : 'Forum';
        $refType = $isComment ? 'forum_comment' : 'forum_post';
        $upType = $typePrefix . ' Upvote Reward';
        $downType = $typePrefix . ' Downvote Penalty';
        $downRefundType = $typePrefix . ' Downvote Penalty Refund';
        $upReversalType = $typePrefix . ' Upvote Reward Reversal';
        $upDesc = $isComment ? 'Reward for comment upvote' : 'Reward for post upvote';
        $downDesc = $isComment ? 'Penalty for comment downvote' : 'Penalty for post downvote';
        $refundDesc = 'Refund penalty on vote change';
        $reversalDesc = 'Reversal due to vote change';
        if ($result === 'up') {
            if ($coinUp > 0) { UserWallet::transact($this->config, (int)$post['author_id'], $upType, $coinUp, true, $upDesc, $refType, $id); }
        } elseif ($result === 'down') {
            if ($coinDown > 0) { UserWallet::transact($this->config, (int)$post['author_id'], $downType, $coinDown, false, $downDesc, $refType, $id); }
        } elseif ($result === 'switch_up') {
            if ($coinDown > 0) { UserWallet::transact($this->config, (int)$post['author_id'], $downRefundType, $coinDown, true, $refundDesc, $refType, $id); }
            if ($coinUp > 0) { UserWallet::transact($this->config, (int)$post['author_id'], $upType, $coinUp, true, $upDesc, $refType, $id); }
        } elseif ($result === 'switch_down') {
            if ($coinUp > 0) { UserWallet::transact($this->config, (int)$post['author_id'], $upReversalType, $coinUp, false, $reversalDesc, $refType, $id); }
            if ($coinDown > 0) { UserWallet::transact($this->config, (int)$post['author_id'], $downType, $coinDown, false, $downDesc, $refType, $id); }
        }
        // Apply voter cost when the resulting state is a downvote (no refunds on switch_up)
        if ($result === 'down' || $result === 'switch_down') {
            if ($repDownVoterCost !== 0) {
                Gamify::addReputation(
                    $this->config,
                    (int)$user['user_id'],
                    $repDownVoterCost,
                    'forum:vote:voter:' . $result,
                    $referenceType,
                    (int)$id
                );
            }
        }
        ForumPost::recountVotes($this->config, $id);
        // If AJAX, return JSON with updated counts
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $xrw = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        if (stripos($accept, 'application/json') !== false || strtolower($xrw) === 'xmlhttprequest') {
            $row = ForumPost::find($this->config, $id);
            header('Content-Type: application/json');
            return json_encode([
                'ok' => true,
                'post_id' => $id,
                'up' => (int)($row['upvote_count'] ?? 0),
                'down' => (int)($row['downvote_count'] ?? 0),
                'result' => $result,
                'current_vote' => $currentVoteState,
            ]);
        }
    $this->redirect('/forum/post/' . $id);
    return '';
    }

    public function bestAnswer(int $id): string
    {
        Auth::enforceAuth();
        $uid = (int)(Auth::id() ?? 0);
        $me = Auth::user();
        try { $isAdmin = (($me['role'] ?? '') === 'admin') || Gate::has($this->config, $uid, 'manage.permissions'); } catch (\Throwable $e) { $isAdmin = false; }
        if (!$isAdmin && \Nexus\Helpers\Restrictions::isSocialSuspended($this->config, $uid)) { http_response_code(403); echo 'Your social features are currently suspended.'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
    if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $answer = ForumPost::find($this->config, $id);
        if (!$answer || $answer['post_type'] !== 'answer') { http_response_code(404); echo 'Not found'; return ''; }
        $question = ForumPost::find($this->config, (int)$answer['parent_post_id']);
        if (!$question) { http_response_code(404); echo 'Question not found'; return ''; }
        $user = Auth::user();
        if ((int)$question['author_id'] !== (int)$user['user_id']) { http_response_code(403); echo 'Forbidden'; return ''; }
        // Capture previous best before updating to allow client to update UI
        $pdo = Database::pdo($this->config);
        $prevBest = null;
        try {
            $st = $pdo->prepare("SELECT post_id FROM forum_posts WHERE parent_post_id = :p AND post_type = 'answer' AND is_best_answer = 1 LIMIT 1");
            $st->execute([':p' => (int)$question['post_id']]);
            $prevBest = $st->fetchColumn();
            $prevBest = $prevBest ? (int)$prevBest : null;
        } catch (\Throwable $e) {}
        ForumPost::markBestAnswer($this->config, $id);
    // Reward via config
    $repAns = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.accepted_answer_rep_answerer', 5, 'integer');
    $repAsker = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.accepted_answer_rep_asker', 2, 'integer');
    $coins = \Nexus\Helpers\Setting::get($this->config, 'app.gamify.forum.accepted_answer_coins', 10, 'integer');
    if ($repAns !== 0) {
        Gamify::addReputation(
            $this->config,
            (int)$answer['author_id'],
            $repAns,
            'forum:best_answer:answerer',
            'forum_answer',
            (int)$id
        );
    }
    if ($repAsker !== 0) {
        Gamify::addReputation(
            $this->config,
            (int)$question['author_id'],
            $repAsker,
            'forum:best_answer:asker',
            'forum_post',
            (int)$question['post_id']
        );
    }
    if ($coins > 0) { UserWallet::transact($this->config, (int)$answer['author_id'], 'Best Answer Reward', $coins, true, 'Reward for best answer', 'forum_answer', $id); }
        // If AJAX, return JSON and let client update DOM
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $xrw = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        if (stripos($accept, 'application/json') !== false || strtolower($xrw) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            return json_encode([
                'ok' => true,
                'question_id' => (int)$question['post_id'],
                'best_id' => (int)$id,
                'prev_best_id' => $prevBest,
            ]);
        }
    $this->redirect('/forum/post/' . (int)$question['post_id']);
    return '';
    }

    public function delete(int $id): string
    {
        Auth::enforceAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $uid = (int)(Auth::id() ?? 0);
        $post = ForumPost::find($this->config, $id);
        if (!$post) { http_response_code(404); echo 'Not found'; return ''; }
        $isAdmin = false;
        try { $isAdmin = (Auth::user()['role'] ?? '') === 'admin' || Gate::has($this->config, $uid, 'manage.permissions'); } catch (\Throwable $e) {}
        if ((int)($post['author_id'] ?? 0) !== $uid && !$isAdmin) { http_response_code(403); echo 'Forbidden'; return ''; }
        // Prevent deleting reported posts for non-admins; admins can override
        if (ForumPost::isReported($this->config, $id) && !$isAdmin) { http_response_code(409); echo 'Cannot delete a reported post.'; return ''; }
        // Delete the post (and its answers/votes)
        ForumPost::delete($this->config, $id);
        // If deleting an answer, go back to the parent post; else go to forum
        $parent = (int)($post['parent_post_id'] ?? 0);
        if ($parent > 0) {
            $this->redirect('/forum/post/' . $parent);
        } else {
            $this->redirect('/forum');
        }
        return '';
    }

    public function markSeen(): string
    {
        Auth::enforceAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $uid = (int)(Auth::id() ?? 0);
        // Admins don't need/read these markers
        $isAdmin = false;
        try { $isAdmin = (Auth::user()['role'] ?? '') === 'admin' || Gate::has($this->config, $uid, 'manage.permissions'); } catch (\Throwable $e) {}
        if ($isAdmin) { $this->redirect('/forum/pending'); return ''; }
        $type = trim((string)($_POST['type'] ?? ''));
        // Get DB-consistent timestamp (matches rejected_at/approved_at semantics)
        $now = null;
        try {
            $pdo = Database::pdo($this->config);
            $now = (string)($pdo->query('SELECT NOW()')->fetchColumn() ?: '');
        } catch (\Throwable $e) {}
        if ($now === '' || $now === null) { $now = date('Y-m-d H:i:s'); }
        try {
            if ($type === 'rejected') {
                \Nexus\Models\SystemSetting::set($this->config, 'forum_seen_rejected_' . $uid, $now, 'string', 'Last seen forum rejected list', $uid);
                // Session fallback for immediate effect
                $_SESSION['forum_seen_rejected_' . $uid] = $now;
            } elseif ($type === 'approved') {
                \Nexus\Models\SystemSetting::set($this->config, 'forum_seen_approved_' . $uid, $now, 'string', 'Last seen forum approved list', $uid);
                $_SESSION['forum_seen_approved_' . $uid] = $now;
            }
        } catch (\Throwable $e) {}
        $this->redirect('/forum/pending' . ($type ? ('?tab=' . urlencode($type)) : ''));
        return '';
    }
}
