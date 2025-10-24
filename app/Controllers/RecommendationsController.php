<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Helpers\Auth;
use Nexus\Helpers\Csrf;
use Nexus\Helpers\Audit;
use Nexus\Models\Alumni;
use Nexus\Models\Student;
use Nexus\Models\RecommendationRequest;

final class RecommendationsController extends Controller
{
    public function show(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        $uid = (int)($user['user_id'] ?? 0);
        $role = (string)($user['role'] ?? '');
        $row = RecommendationRequest::findWithUsers($this->config, $requestId);
        if (!$row) { http_response_code(404); echo 'Not found'; return ''; }
        // Allow student or mentor in the pair, or admin
        $isParticipant = ($uid === (int)$row['student_user_id']) || ($uid === (int)$row['alumni_user_id']);
        $isAdmin = ($role === 'admin');
        if (!$isParticipant && !$isAdmin) { http_response_code(403); echo 'Forbidden'; return ''; }
        // Enrich with display names
        $pdo = \Nexus\Core\Database::pdo($this->config);
        $names = ['student' => ['first_name' => '', 'last_name' => ''], 'alumni' => ['first_name' => '', 'last_name' => '']];
        $st = $pdo->prepare('SELECT up.first_name, up.last_name FROM user_profiles up WHERE up.user_id = :u');
        $st->execute([':u' => (int)$row['student_user_id']]); $sn = $st->fetch(\PDO::FETCH_ASSOC) ?: [];
        $st->execute([':u' => (int)$row['alumni_user_id']]); $mn = $st->fetch(\PDO::FETCH_ASSOC) ?: [];
        $names['student'] = $sn; $names['alumni'] = $mn;
        return $this->view('recommendations/show', ['rec' => $row, 'names' => $names, 'role' => $role]);
    }
    // Student: list my recommendation requests
    public function my(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'student') { http_response_code(403); echo 'Only students'; return ''; }
        $student = Student::findByUserId($this->config, (int)$user['user_id']);
        if (!$student) { Student::upsert($this->config, (int)$user['user_id'], []); $student = Student::findByUserId($this->config, (int)$user['user_id']); }
        $rows = RecommendationRequest::forStudent($this->config, (int)$student['student_id']);
        return $this->view('recommendations/my', ['requests' => $rows]);
    }

    // Student: create a recommendation request to a specific alumni
    public function create(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'student') { http_response_code(403); echo 'Only students'; return ''; }
        $student = Student::findByUserId($this->config, (int)$user['user_id']);
        if (!$student) { Student::upsert($this->config, (int)$user['user_id'], []); $student = Student::findByUserId($this->config, (int)$user['user_id']); }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
            $alumniId = (int)($_POST['alumni_id'] ?? 0);
            $message = trim((string)($_POST['message'] ?? ''));
            if ($alumniId <= 0) { return $this->view('recommendations/create', ['error' => 'Select a mentor']); }
            if (RecommendationRequest::hasActiveOrPending($this->config, (int)$student['student_id'], $alumniId)) {
                return $this->view('recommendations/create', ['error' => 'Already requested or accepted with this mentor']);
            }
            $id = RecommendationRequest::create($this->config, (int)$student['student_id'], $alumniId, $message);
            Audit::log($this->config, (int)$user['user_id'], 'recommendation.request.create', 'recommendation_request', $id, null, ['alumni_id' => $alumniId]);
            $this->redirect('/recommendations/mine');
            return '';
        }
        return $this->view('recommendations/create', []);
    }

    // Mentor: list incoming recommendation requests
    public function inbox(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'alumni') { http_response_code(403); echo 'Only alumni'; return ''; }
        $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
        $rows = RecommendationRequest::forMentor($this->config, (int)$alumni['alumni_id']);
        return $this->view('recommendations/inbox', ['requests' => $rows]);
    }

    // Mentor: accept with snapshot and note
    public function accept(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'alumni') { http_response_code(403); echo 'Only alumni'; return ''; }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
        $req = RecommendationRequest::findWithUsers($this->config, $requestId);
        if (!$req || (int)$req['alumni_id'] !== (int)$alumni['alumni_id']) { http_response_code(403); echo 'Forbidden'; return ''; }
        $note = trim((string)($_POST['mentor_note'] ?? ''));
        $snapshot = RecommendationRequest::buildMentorSnapshot($this->config, (int)$alumni['alumni_id']);
        $ok = RecommendationRequest::accept($this->config, $requestId, $snapshot, $note);
        if ($ok) { Audit::log($this->config, (int)$user['user_id'], 'recommendation.request.accept', 'recommendation_request', $requestId, null, ['mentor_note' => $note]); }
        $this->redirect('/recommendations/inbox');
        return '';
    }

    // Mentor: reject
    public function reject(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'alumni') { http_response_code(403); echo 'Only alumni'; return ''; }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
        $req = RecommendationRequest::findWithUsers($this->config, $requestId);
        if (!$req || (int)$req['alumni_id'] !== (int)$alumni['alumni_id']) { http_response_code(403); echo 'Forbidden'; return ''; }
        if (RecommendationRequest::reject($this->config, $requestId)) { Audit::log($this->config, (int)$user['user_id'], 'recommendation.request.reject', 'recommendation_request', $requestId, null, null); }
        $this->redirect('/recommendations/inbox');
        return '';
    }

    // Mentor: revoke an accepted recommendation later
    public function revoke(int $requestId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'alumni') { http_response_code(403); echo 'Only alumni'; return ''; }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $alumni = Alumni::findByUserId($this->config, (int)$user['user_id']);
        $ok = RecommendationRequest::revoke($this->config, $requestId, (int)$alumni['alumni_id']);
        if ($ok) { Audit::log($this->config, (int)$user['user_id'], 'recommendation.request.revoke', 'recommendation_request', $requestId, null, null); }
        $this->redirect('/recommendations/inbox');
        return '';
    }
}
