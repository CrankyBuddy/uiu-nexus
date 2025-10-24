<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Core\Database;
use Nexus\Helpers\Auth;
use Nexus\Helpers\Csrf;
use Nexus\Models\JobCategory;
use Nexus\Models\JobType;
use Nexus\Models\Location;
use Nexus\Models\JobListing;
use Nexus\Models\JobApplication;
use Nexus\Models\Recruiter as RecruiterModel;
use Nexus\Models\Student as StudentModel;
use Nexus\Models\Notification;
use Nexus\Models\ApplicationNote;
use Nexus\Models\Interview;
use Nexus\Models\Referral;
use Nexus\Helpers\Audit;
use PDO;
use Nexus\Models\UserProfile;
use Nexus\Models\UserSkill;
use Nexus\Models\User as UserModel;
use Nexus\Models\JobApplicationQuestion;
use Nexus\Models\JobApplicationAnswer;
use Nexus\Models\JobApplicationReference;
use Nexus\Models\StudentReference;
use Nexus\Models\UserDocument;

final class JobsController extends Controller
{
    public function index(): string
    {
        Auth::enforceAuth();
        $jobs = JobListing::listActive($this->config, 20);
        $cats = JobCategory::all($this->config);
        $types = JobType::all($this->config);
        $locs = Location::all($this->config);
        return $this->view('jobs/index', ['jobs' => $jobs, 'categories' => $cats, 'types' => $types, 'locations' => $locs]);
    }

    public function show(int $id): string
    {
        Auth::enforceAuth();
        $job = JobListing::findWithJoins($this->config, $id);
        if (!$job || !(bool)$job['is_active'] || !(bool)$job['is_approved']) {
            http_response_code(404);
            echo 'Job not found';
            return '';
        }
        // Recruiter-defined additional questions
        $questions = JobApplicationQuestion::forJob($this->config, $id);
        // Student's available references (active) if viewer is a student
        $availableRefs = [];
        $user = Auth::user();
        if (($user['role'] ?? '') === 'student') {
            $student = StudentModel::findByUserId($this->config, (int)$user['user_id']);
            if ($student) {
                $availableRefs = StudentReference::forStudent($this->config, (int)$student['student_id']);
            }
        }
        return $this->view('jobs/show', ['job' => $job, 'questions' => $questions, 'availableRefs' => $availableRefs]);
    }

    public function create(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'recruiter') { http_response_code(403); echo 'Only recruiters can post jobs'; return ''; }
        $cats = JobCategory::all($this->config);
        $types = JobType::all($this->config);
        $locs = Location::all($this->config);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
            $recruiter = RecruiterModel::findByUserId($this->config, (int)$user['user_id']);
            if (!$recruiter) { // ensure a row exists minimally
                RecruiterModel::upsert($this->config, (int)$user['user_id'], ['company_name' => '']);
                $recruiter = RecruiterModel::findByUserId($this->config, (int)$user['user_id']);
            }
            $title = trim((string)($_POST['job_title'] ?? ''));
            $desc = trim((string)($_POST['job_description'] ?? ''));
            $catId = (int)($_POST['category_id'] ?? 0);
            $typeId = (int)($_POST['type_id'] ?? 0);
            $locId = (int)($_POST['location_id'] ?? 0);
            $duration = trim((string)($_POST['duration'] ?? ''));
            $salaryMin = ($_POST['salary_range_min'] ?? '') !== '' ? (int)$_POST['salary_range_min'] : null;
            $salaryMax = ($_POST['salary_range_max'] ?? '') !== '' ? (int)$_POST['salary_range_max'] : null;
            $stipend = ($_POST['stipend_amount'] ?? '') !== '' ? (int)$_POST['stipend_amount'] : null;
            $deadline = (string)($_POST['application_deadline'] ?? '');
            $skills = trim((string)($_POST['required_skills'] ?? ''));
            if ($title === '' || $desc === '' || $catId <= 0 || $typeId <= 0 || $locId <= 0) {
                return $this->view('jobs/create', ['categories' => $cats, 'types' => $types, 'locations' => $locs, 'error' => 'All required fields must be filled.']);
            }
            $skillsJson = $skills !== '' ? json_encode(array_map('trim', explode(',', $skills)), JSON_THROW_ON_ERROR) : null;
            $jobId = JobListing::create($this->config, (int)$recruiter['recruiter_id'], $title, $desc, $catId, $typeId, $locId, $duration, $salaryMin, $salaryMax, $stipend, $deadline, $skillsJson, true, true, false);
            // Upsert custom application questions if provided
            $qTexts = $_POST['question_text'] ?? [];
            $qTypes = $_POST['question_type'] ?? [];
            $qReqs  = $_POST['question_required'] ?? [];
            $qs = [];
            if (is_array($qTexts)) {
                foreach ($qTexts as $i => $text) {
                    $text = trim((string)$text);
                    if ($text === '') continue;
                    $type = isset($qTypes[$i]) ? (string)$qTypes[$i] : 'text';
                    if (!in_array($type, ['text','textarea'], true)) { $type = 'text'; }
                    $req = isset($qReqs[$i]) && (string)$qReqs[$i] === '1';
                    $qs[] = ['text' => $text, 'type' => $type, 'required' => $req];
                }
                if ($qs) { JobApplicationQuestion::upsertForJob($this->config, (int)$jobId, $qs); }
            }
            $this->redirect('/jobs/listing/' . $jobId . '/applications');
            return '';
        }
        return $this->view('jobs/create', ['categories' => $cats, 'types' => $types, 'locations' => $locs]);
    }

    public function apply(int $id): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'student') { http_response_code(403); echo 'Only students can apply'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $job = JobListing::findWithJoins($this->config, $id);
        if (!$job || !(bool)$job['is_active'] || !(bool)$job['is_approved']) { http_response_code(404); echo 'Job not found'; return ''; }
        $student = StudentModel::findByUserId($this->config, (int)$user['user_id']);
        if (!$student) { StudentModel::upsert($this->config, (int)$user['user_id'], []); $student = StudentModel::findByUserId($this->config, (int)$user['user_id']); }
        $cover = trim((string)($_POST['cover_letter'] ?? ''));
        // Validate required questions
        $questions = JobApplicationQuestion::forJob($this->config, $id);
        $answersToSave = [];
        foreach ($questions as $q) {
            $key = 'q_' . (int)$q['question_id'];
            $ans = (string)($_POST[$key] ?? '');
            if (!empty($q['is_required']) && trim($ans) === '') {
                \Nexus\Helpers\Flash::add('danger', 'Please answer all required questions.');
                $this->redirect('/jobs/' . $id);
                return '';
            }
            if ($ans !== '') { $answersToSave[(int)$q['question_id']] = $ans; }
        }
        // Create application
        $appId = JobApplication::create($this->config, $id, (int)$student['student_id'], $cover);
        if (!$appId) { http_response_code(400); echo 'Already applied or failed'; return ''; }
        // Save Q&A
        if ($answersToSave) { JobApplicationAnswer::saveAnswers($this->config, (int)$appId, $answersToSave); }
        // Save up to 2 references (must belong to applicant and be active)
        $refIds = $_POST['reference_ids'] ?? [];
        $validRefIds = [];
        if (is_array($refIds) && $refIds) {
            $pdo = Database::pdo($this->config);
            $in = implode(',', array_fill(0, count($refIds), '?'));
            $st = $pdo->prepare("SELECT reference_id FROM student_references WHERE reference_id IN ($in) AND student_id = ? AND status='active'");
            $params = array_map('intval', $refIds);
            $params[] = (int)$student['student_id'];
            $st->execute($params);
            $validRefIds = array_map('intval', array_column($st->fetchAll(PDO::FETCH_ASSOC) ?: [], 'reference_id'));
        }
        if ($validRefIds) { JobApplicationReference::setForApplication($this->config, (int)$appId, $validRefIds); }
        // Notify recruiter
        $jobRow = JobListing::find($this->config, $id);
        if ($jobRow) {
            $pdo = Database::pdo($this->config);
            // Fetch recruiter user_id from recruiters
            $st = $pdo->prepare('SELECT user_id FROM recruiters WHERE recruiter_id = :rid');
            $st->execute([':rid' => (int)$jobRow['recruiter_id']]);
            $recUid = (int)($st->fetchColumn() ?: 0);
            if ($recUid > 0) {
                Notification::send($this->config, $recUid, 'New Job Application', 'A student applied to your job: ' . (string)$jobRow['job_title'], 'job_application', 'job', $id, '/jobs/listing/' . $id . '/applications');
            }
        }
        // Notify applicant (confirmation)
        Notification::send(
            $this->config,
            (int)$user['user_id'],
            'Application Submitted',
            'Your application has been submitted for: ' . (string)($jobRow['job_title'] ?? 'the job'),
            'job_application',
            'job',
            $id,
            '/applications/mine'
        );
    $this->redirect('/applications/mine');
    return '';
    }

    public function myListings(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'recruiter') { http_response_code(403); echo 'Forbidden'; return ''; }
        $recruiter = RecruiterModel::findByUserId($this->config, (int)$user['user_id']);
        if (!$recruiter) { return $this->view('jobs/my_listings', ['jobs' => []]); }
        $jobs = JobListing::byRecruiter($this->config, (int)$recruiter['recruiter_id']);
        return $this->view('jobs/my_listings', ['jobs' => $jobs]);
    }

    public function listingApplications(int $id): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'recruiter') { http_response_code(403); echo 'Forbidden'; return ''; }
        $job = JobListing::find($this->config, $id);
        if (!$job) { http_response_code(404); echo 'Job not found'; return ''; }
        $recruiter = RecruiterModel::findByUserId($this->config, (int)$user['user_id']);
        if ((int)$job['recruiter_id'] !== (int)($recruiter['recruiter_id'] ?? 0)) { http_response_code(403); echo 'Not your listing'; return ''; }
        $status = isset($_GET['status']) ? (string)$_GET['status'] : null;
        $q = isset($_GET['q']) ? (string)$_GET['q'] : null;
        $apps = JobApplication::forJob($this->config, $id, $status ?: null, $q ?: null);
        return $this->view('jobs/applications', ['applications' => $apps, 'job' => $job, 'filterStatus' => $status, 'filterQuery' => $q]);
    }

    // Recruiter: view single application with notes & interviews
    public function application(int $id): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'recruiter') { http_response_code(403); echo 'Forbidden'; return ''; }
        $pdo = Database::pdo($this->config);
        $app = $pdo->prepare('SELECT a.*, j.job_title, j.recruiter_id, s.student_id, u.user_id AS student_user_id, u.email AS student_email, up.first_name, up.last_name FROM job_applications a INNER JOIN job_listings j ON j.job_id = a.job_id INNER JOIN students s ON s.student_id = a.student_id INNER JOIN users u ON u.user_id = s.user_id LEFT JOIN user_profiles up ON up.user_id = u.user_id WHERE a.application_id = :id');
        $app->execute([':id' => $id]);
        $row = $app->fetch(PDO::FETCH_ASSOC);
        if (!$row) { http_response_code(404); echo 'Not found'; return ''; }
        $rec = RecruiterModel::findByUserId($this->config, (int)$user['user_id']);
        if ((int)$row['recruiter_id'] !== (int)($rec['recruiter_id'] ?? -1)) { http_response_code(403); echo 'Forbidden'; return ''; }
        $notes = ApplicationNote::forApplication($this->config, (int)$id);
        $interviews = Interview::forApplication($this->config, (int)$id);
        // Enrich with Q&A and attached references
        $answers = JobApplicationAnswer::forApplication($this->config, (int)$id);
        $references = JobApplicationReference::forApplication($this->config, (int)$id);
        // Preload mentor CV docs (by alumni user_id), keyed by reference_id
        $mentorDocs = [];
        foreach ($references as $r) {
            $alumniUserId = (int)($r['alumni_user_id'] ?? 0);
            if ($alumniUserId > 0) {
                $mentorDocs[(int)$r['reference_id']] = UserDocument::getByUserAndType($this->config, $alumniUserId, 'cv') ?: null;
            }
        }
        // Applicant profile context for recruiter visibility
        $subjectUserId = (int)($row['student_user_id'] ?? 0);
        $applicantUser = $subjectUserId > 0 ? UserModel::findById($this->config, $subjectUserId) : null;
        $applicantProfile = $subjectUserId > 0 ? UserProfile::findByUserId($this->config, $subjectUserId) : null;
        $applicantStudent = $subjectUserId > 0 ? StudentModel::findByUserId($this->config, $subjectUserId) : null;
        $applicantSkills = $subjectUserId > 0 ? UserSkill::getNamesByUser($this->config, $subjectUserId) : [];
        $visibilityContext = ['studentAppliedToViewerJob' => true];
        return $this->view('jobs/application', [
            'application' => $row,
            'notes' => $notes,
            'interviews' => $interviews,
            'answers' => $answers,
            'references' => $references,
            'mentorDocs' => $mentorDocs,
            'applicantUser' => $applicantUser,
            'applicantProfile' => $applicantProfile,
            'applicantStudent' => $applicantStudent,
            'applicantSkills' => $applicantSkills,
            'visibilityContext' => $visibilityContext,
        ]);
    }

    // Recruiter: add application note
    public function addNote(int $id): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'recruiter') { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        // Ownership check: application must belong to recruiter's job
        $pdo = Database::pdo($this->config);
        $own = $pdo->prepare('SELECT j.recruiter_id FROM job_applications a INNER JOIN job_listings j ON j.job_id = a.job_id WHERE a.application_id = :id');
        $own->execute([':id' => $id]);
        $rec = RecruiterModel::findByUserId($this->config, (int)$user['user_id']);
        if ((int)($own->fetchColumn() ?: -1) !== (int)($rec['recruiter_id'] ?? -2)) { http_response_code(403); echo 'Forbidden'; return ''; }
        $note = trim((string)($_POST['note_text'] ?? ''));
        if ($note !== '') {
            ApplicationNote::add($this->config, $id, (int)$user['user_id'], $note, true);
            Audit::log($this->config, (int)$user['user_id'], 'application.note_add', 'application', $id, null, ['note_len' => strlen($note)]);
            // Notify the applicant about the new note
            try {
                $info = $pdo->prepare('SELECT a.application_id, j.job_title, s.student_id, u.user_id AS student_user_id FROM job_applications a INNER JOIN job_listings j ON j.job_id = a.job_id INNER JOIN students s ON s.student_id = a.student_id INNER JOIN users u ON u.user_id = s.user_id WHERE a.application_id = :id');
                $info->execute([':id' => $id]);
                $row = $info->fetch(PDO::FETCH_ASSOC) ?: null;
                if ($row) {
                    Notification::send($this->config, (int)$row['student_user_id'], 'New Note on Application', 'A new note was added to your application for: ' . (string)$row['job_title'], 'application_note', 'application', (int)$id, '/applications/mine');
                }
            } catch (\Throwable $e) { /* ignore notification failures */ }
        }
    $this->redirect('/applications/' . $id);
    return '';
    }

    // Recruiter: schedule an interview
    public function scheduleInterview(int $id): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'recruiter') { http_response_code(403); echo 'Forbidden'; return ''; }
        $pdo = Database::pdo($this->config);
        $app = $pdo->prepare('SELECT a.*, j.job_title, j.recruiter_id, s.student_id, u.user_id AS student_user_id FROM job_applications a INNER JOIN job_listings j ON j.job_id = a.job_id INNER JOIN students s ON s.student_id = a.student_id INNER JOIN users u ON u.user_id = s.user_id WHERE a.application_id = :id');
        $app->execute([':id' => $id]);
        $row = $app->fetch(PDO::FETCH_ASSOC);
        if (!$row) { http_response_code(404); echo 'Not found'; return ''; }
        $rec = RecruiterModel::findByUserId($this->config, (int)$user['user_id']);
        if ((int)$row['recruiter_id'] !== (int)($rec['recruiter_id'] ?? -1)) { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
            $dt = trim((string)($_POST['scheduled_date'] ?? ''));
            $dur = (int)($_POST['duration_minutes'] ?? 30);
            $link = trim((string)($_POST['meeting_link'] ?? ''));
            $iname = trim((string)($_POST['interviewer_name'] ?? ''));
            // Normalize datetime-local to DATETIME
            $dt = str_replace('T', ' ', $dt);
            if (strlen($dt) === 16) { $dt .= ':00'; }
            Interview::create($this->config, (int)$id, $dt, max(15, $dur), $link ?: null, $iname ?: null);
            Audit::log($this->config, (int)$user['user_id'], 'application.interview_schedule', 'application', (int)$id, null, ['scheduled_date' => $dt, 'duration' => $dur]);
            // Notify student
            Notification::send($this->config, (int)$row['student_user_id'], 'Interview Scheduled', 'Interview scheduled for: ' . (string)$row['job_title'], 'interview', 'application', (int)$id, '/applications/mine');
            // Notify recruiter (confirmation)
            try {
                $recUidStmt = $pdo->prepare('SELECT user_id FROM recruiters WHERE recruiter_id = :rid');
                $recUidStmt->execute([':rid' => (int)$row['recruiter_id']]);
                $recUid = (int)($recUidStmt->fetchColumn() ?: 0);
                if ($recUid > 0) {
                    Notification::send($this->config, $recUid, 'Interview Scheduled', 'You scheduled an interview for: ' . (string)$row['job_title'], 'interview', 'application', (int)$id, '/jobs/listing/' . (int)$row['job_id'] . '/applications');
                }
            } catch (\Throwable $e) { /* ignore notification failures */ }
            $this->redirect('/applications/' . $id);
            return '';
        }
        return $this->view('jobs/schedule_interview', ['application' => $row]);
    }

    // Recruiter: update application status
    public function updateApplicationStatus(int $id): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'recruiter') { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        // Ownership check
        $pdo = Database::pdo($this->config);
        $own = $pdo->prepare('SELECT j.recruiter_id FROM job_applications a INNER JOIN job_listings j ON j.job_id = a.job_id WHERE a.application_id = :id');
        $own->execute([':id' => $id]);
        $rec = RecruiterModel::findByUserId($this->config, (int)$user['user_id']);
        if ((int)($own->fetchColumn() ?: -1) !== (int)($rec['recruiter_id'] ?? -2)) { http_response_code(403); echo 'Forbidden'; return ''; }
        $status = (string)($_POST['status'] ?? 'applied');
        if (!in_array($status, ['applied','under_review','shortlisted','interview','accepted','rejected'], true)) { http_response_code(400); echo 'Bad status'; return ''; }
        $sql = "UPDATE job_applications SET status = :s,
            shortlisted_at = CASE WHEN :s_short = 'shortlisted' THEN CURRENT_TIMESTAMP ELSE shortlisted_at END,
            interviewed_at = CASE WHEN :s_int = 'interview' THEN CURRENT_TIMESTAMP ELSE interviewed_at END,
            decided_at = CASE WHEN :s_dec IN ('accepted','rejected') THEN CURRENT_TIMESTAMP ELSE decided_at END
            WHERE application_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':s' => $status, ':s_short' => $status, ':s_int' => $status, ':s_dec' => $status, ':id' => $id]);
        // Notify both applicant and recruiter about status change
        try {
            $info = $pdo->prepare('SELECT a.application_id, a.job_id, j.job_title, j.recruiter_id, s.student_id, u.user_id AS student_user_id FROM job_applications a JOIN job_listings j ON j.job_id = a.job_id JOIN students s ON s.student_id = a.student_id JOIN users u ON u.user_id = s.user_id WHERE a.application_id = :id');
            $info->execute([':id' => $id]);
            $row = $info->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($row) {
                // Applicant notification
                $stuUid = (int)($row['student_user_id'] ?? 0);
                if ($stuUid > 0) {
                    Notification::send($this->config, $stuUid, 'Application Status Updated', 'Your application for "' . (string)$row['job_title'] . '" is now ' . $status . '.', 'job_application', 'application', (int)$id, '/applications/mine');
                }
                // Recruiter notification
                $recUidStmt = $pdo->prepare('SELECT user_id FROM recruiters WHERE recruiter_id = :rid');
                $recUidStmt->execute([':rid' => (int)$row['recruiter_id']]);
                $recUid = (int)($recUidStmt->fetchColumn() ?: 0);
                if ($recUid > 0) {
                    Notification::send($this->config, $recUid, 'Application Status Updated', 'Status updated to ' . $status . ' for job "' . (string)$row['job_title'] . '".', 'job_application', 'application', (int)$id, '/jobs/listing/' . (int)$row['job_id'] . '/applications');
                }
            }
        } catch (\Throwable $e) { /* ignore notification failures */ }
    Audit::log($this->config, (int)$user['user_id'], 'application.status_update', 'application', $id, null, ['status' => $status]);
    $this->redirect('/applications/' . $id);
    return '';
    }

    // Alumni: refer a student to a job
    public function refer(int $jobId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'alumni') { http_response_code(403); echo 'Forbidden'; return ''; }
        $pdo = Database::pdo($this->config);
        $job = JobListing::find($this->config, $jobId);
        if (!$job || !(bool)$job['is_active'] || !(bool)$job['is_approved']) { http_response_code(404); echo 'Job not found'; return ''; }
        $alumni = $pdo->prepare('SELECT * FROM alumni WHERE user_id = :u');
        $alumni->execute([':u' => (int)$user['user_id']]);
        $al = $alumni->fetch(PDO::FETCH_ASSOC);
        if (!$al) { http_response_code(403); echo 'Profile incomplete'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
            $studentId = (int)($_POST['student_id'] ?? 0);
            $message = trim((string)($_POST['message'] ?? ''));
            if ($studentId > 0) {
                $refId = Referral::create($this->config, $jobId, (int)$al['alumni_id'], $studentId, $message);
                Audit::log($this->config, (int)$user['user_id'], 'referral.create', 'referral', (int)$refId, null, ['job_id' => $jobId, 'student_id' => $studentId]);
            }
            $this->redirect('/jobs/' . $jobId);
            return '';
        }
        return $this->view('jobs/refer', ['job' => $job]);
    }

    // Recruiter: view referrals for a job and accept/decline
    public function referralsForJob(int $jobId): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'recruiter') { http_response_code(403); echo 'Forbidden'; return ''; }
        $job = JobListing::find($this->config, $jobId);
        if (!$job) { http_response_code(404); echo 'Not found'; return ''; }
        $rec = RecruiterModel::findByUserId($this->config, (int)$user['user_id']);
        if ((int)$job['recruiter_id'] !== (int)($rec['recruiter_id'] ?? -1)) { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::check($_POST['_token'] ?? null)) {
            // accept/decline single referral
            $rid = (int)($_POST['referral_id'] ?? 0);
            $action = (string)($_POST['action'] ?? '');
            if ($rid > 0 && in_array($action, ['accept','decline'], true)) {
                $status = $action === 'accept' ? 'accepted' : 'declined';
                $reward = $status === 'accepted' ? 50 : 0;
                Referral::updateStatus($this->config, $rid, $status, $reward);
                Audit::log($this->config, (int)$user['user_id'], 'referral.update_status', 'referral', $rid, null, ['status' => $status, 'reward' => $reward]);
                // Notify alumni and optionally student
                $pdo = Database::pdo($this->config);
                $info = $pdo->prepare('SELECT r.*, a.user_id AS alumni_user_id, s.user_id AS student_user_id, j.job_title FROM referrals r INNER JOIN alumni a ON a.alumni_id = r.alumni_id INNER JOIN students s ON s.student_id = r.student_id INNER JOIN job_listings j ON j.job_id = r.job_id WHERE r.referral_id = :id');
                $info->execute([':id' => $rid]);
                $row = $info->fetch(PDO::FETCH_ASSOC) ?: null;
                if ($row) {
                    $title = 'Referral ' . ($status === 'accepted' ? 'Accepted' : 'Declined');
                    $msg = 'Your referral for job: ' . (string)$row['job_title'] . ' was ' . $status . '.';
                    Notification::send($this->config, (int)$row['alumni_user_id'], $title, $msg, 'referral', 'referral', $rid, '/referrals/mine');
                }
            }
        }
        $refs = Referral::forJob($this->config, $jobId);
        return $this->view('jobs/referrals', ['job' => $job, 'referrals' => $refs]);
    }

    // Alumni: list my referrals
    public function myReferrals(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'alumni') { http_response_code(403); echo 'Forbidden'; return ''; }
        $pdo = Database::pdo($this->config);
        $al = $pdo->prepare('SELECT alumni_id FROM alumni WHERE user_id = :u');
        $al->execute([':u' => (int)$user['user_id']]);
        $alumniId = (int)($al->fetchColumn() ?: 0);
        $refs = $alumniId ? Referral::forAlumni($this->config, $alumniId) : [];
        return $this->view('referrals/my_referrals', ['referrals' => $refs]);
    }

    public function myApplications(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'student') { http_response_code(403); echo 'Forbidden'; return ''; }
        $student = StudentModel::findByUserId($this->config, (int)$user['user_id']);
        if (!$student) { return $this->view('jobs/my_applications', ['applications' => []]); }
        $apps = JobApplication::forStudent($this->config, (int)$student['student_id']);
        return $this->view('jobs/my_applications', ['applications' => $apps]);
    }

    // Admin-only: approve or decline a job listing
    public function moderate(int $id): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'admin') { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'approve') {
            JobListing::setApproval($this->config, $id, true);
        } elseif ($action === 'decline') {
            JobListing::setApproval($this->config, $id, false);
        }
        // Notify recruiter about moderation decision
        try {
            $job = JobListing::find($this->config, $id);
            if ($job) {
                $pdo = Database::pdo($this->config);
                $recUidStmt = $pdo->prepare('SELECT user_id FROM recruiters WHERE recruiter_id = :rid');
                $recUidStmt->execute([':rid' => (int)$job['recruiter_id']]);
                $recUid = (int)($recUidStmt->fetchColumn() ?: 0);
                if ($recUid > 0) {
                    $msg = ($action === 'approve') ? 'Your job has been approved: ' : 'Your job was declined: ';
                    Notification::send($this->config, $recUid, 'Job Moderation', $msg . (string)$job['job_title'], 'job', 'job', (int)$id, '/jobs/' . (int)$id);
                }
            }
        } catch (\Throwable $e) { /* ignore notification failures */ }
    $this->redirect('/jobs/' . $id);
    return '';
    }

    // Recruiter-only: toggle active status of own job
    public function toggleActive(int $id): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'recruiter') { http_response_code(403); echo 'Forbidden'; return ''; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $job = JobListing::find($this->config, $id);
        if (!$job) { http_response_code(404); echo 'Not found'; return ''; }
        $recruiter = RecruiterModel::findByUserId($this->config, (int)$user['user_id']);
        if ((int)($job['recruiter_id'] ?? 0) !== (int)($recruiter['recruiter_id'] ?? -1)) { http_response_code(403); echo 'Not your listing'; return ''; }
        $new = !(bool)$job['is_active'];
        JobListing::setActive($this->config, $id, $new);
    $this->redirect('/jobs/my-listings');
    return '';
    }

    // Admin or owning recruiter: delete a job listing
    public function delete(int $id): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $job = JobListing::find($this->config, $id);
        if (!$job) { http_response_code(404); echo 'Not found'; return ''; }
        $role = (string)($user['role'] ?? '');
        $allowed = false;
        if ($role === 'admin') {
            $allowed = true;
        } elseif ($role === 'recruiter') {
            $rec = RecruiterModel::findByUserId($this->config, (int)$user['user_id']);
            $allowed = ((int)($job['recruiter_id'] ?? 0) === (int)($rec['recruiter_id'] ?? -1));
        }
        if (!$allowed) { http_response_code(403); echo 'Forbidden'; return ''; }
        // Perform delete
        JobListing::delete($this->config, $id);
        // Optionally notify the recruiter if deleted by admin
        try {
            if ($role === 'admin') {
                $pdo = Database::pdo($this->config);
                $recUidStmt = $pdo->prepare('SELECT user_id FROM recruiters WHERE recruiter_id = :rid');
                $recUidStmt->execute([':rid' => (int)$job['recruiter_id']]);
                $recUid = (int)($recUidStmt->fetchColumn() ?: 0);
                if ($recUid > 0) {
                    Notification::send($this->config, $recUid, 'Job Deleted', 'An admin deleted your job: ' . (string)$job['job_title'], 'job', 'job', (int)$id, '/jobs');
                }
            }
        } catch (\Throwable $e) { /* ignore */ }
        // Redirect
        if ($role === 'admin') {
            $this->redirect('/jobs');
        } else {
            $this->redirect('/jobs/my-listings');
        }
        return '';
    }
}
