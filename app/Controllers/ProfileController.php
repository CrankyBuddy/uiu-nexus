<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Helpers\Auth;
use Nexus\Helpers\Csrf;
use Nexus\Helpers\Upload;
use Nexus\Helpers\Flash;
use Nexus\Models\User;
use Nexus\Models\UserProfile;
use Nexus\Models\UserSkill;
use Nexus\Models\ProfileVisibility;
use Nexus\Models\UserFieldLock;
use Nexus\Models\Student;
use Nexus\Models\Alumni;
use Nexus\Models\Recruiter;
use Nexus\Models\CareerInterest;
use Nexus\Models\UserCourseInterest;
use Nexus\Models\StudentProject;
use Nexus\Models\UserCertificate;
use Nexus\Models\AlumniPreference;
use Nexus\Models\AlumniFocusSkill;
use Nexus\Models\RecruiterPosition;
use Nexus\Helpers\Gamify;

final class ProfileController extends Controller
{

    public function show(): string
    {
        Auth::enforceAuth();
        $viewerId = (int) Auth::id();
        $viewer = Auth::user();
        $viewerIsAdmin = (($viewer['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($this->config, $viewerId, 'manage.permissions');
        $paramUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $subjectId = ($viewerIsAdmin && $paramUserId > 0) ? $paramUserId : $viewerId;
        $user = User::findById($this->config, $subjectId);
        if (!$user) { http_response_code(404); return 'User not found'; }
        $profile = UserProfile::findByUserId($this->config, $subjectId);
        $roleData = $this->loadRoleData($subjectId, (string)($user['role'] ?? ''));
        $skills = UserSkill::getNamesByUser($this->config, $subjectId);
        $isOwner = ($viewerId === $subjectId);
        // Ensure badges reflect current reputation before render
        try {
            $roleNow = (string)($user['role'] ?? '');
            if (in_array($roleNow, ['student','alumni'], true)) {
                Gamify::autoAwardBadges($this->config, $subjectId);
            }
        } catch (\Throwable $e) { /* non-fatal */ }
        return $this->view('profile.show', compact('user','profile','roleData','skills','viewerIsAdmin','isOwner'));
    }

    public function editForm(): string
    {
        Auth::enforceAuth();
        $viewerId = (int) Auth::id();
        $viewer = Auth::user();
        $viewerIsAdmin = (($viewer['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($this->config, $viewerId, 'manage.permissions');
        $paramUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $subjectId = ($viewerIsAdmin && $paramUserId > 0) ? $paramUserId : $viewerId;
        $user = User::findById($this->config, $subjectId);
        if (!$user) { http_response_code(404); return 'User not found'; }
        $profile = UserProfile::findByUserId($this->config, $subjectId);
        $roleData = $this->loadRoleData($subjectId, $user['role']);
        $skills = UserSkill::getNamesByUser($this->config, $subjectId);
        $projects = StudentProject::listByUser($this->config, $subjectId);
        return $this->view('profile.edit', compact('user','profile','roleData','skills','projects','viewerIsAdmin'));
    }

    public function edit(): string
    {
        Auth::enforceAuth();
        if (!Csrf::check($_POST['_token'] ?? null)) {
            http_response_code(419);
            return 'CSRF token mismatch';
        }
        $viewerId = (int) Auth::id();
        $viewer = Auth::user();
        $viewerIsAdmin = (($viewer['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($this->config, $viewerId, 'manage.permissions');
        $targetId = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
        $uid = ($viewerIsAdmin && $targetId > 0) ? $targetId : $viewerId;
        $user = User::findById($this->config, $uid);
        // Active locks for this user; non-admins cannot change these fields.
        $lockedKeys = $viewerIsAdmin ? [] : UserFieldLock::activeKeysForUser($this->config, $uid);

        // Handle picture upload (optional), but honor lock if present
        $pictureUrl = null;
        if (!empty($_FILES['profile_picture']['name'] ?? '')) {
            $saved = Upload::save($_FILES['profile_picture'], '/uploads/profiles', ['image/png','image/jpeg','image/gif']);
            if ($saved) $pictureUrl = $saved;
        }
        // Handle CV upload (optional)
        $cvUploadedUrl = null; $cvUploadedMeta = [];
        if (!empty($_FILES['cv_file']['name'] ?? '')) {
            // Allowed MIME for docs
            $allowed = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            $savedCv = Upload::save($_FILES['cv_file'], '/uploads/cv', $allowed, 10_000_000);
            if ($savedCv) {
                $cvUploadedUrl = $savedCv;
                $cvUploadedMeta = [
                    'file_name' => (string)($_FILES['cv_file']['name'] ?? ''),
                    'mime_type' => (string)($_FILES['cv_file']['type'] ?? ''),
                    'file_size' => (int)($_FILES['cv_file']['size'] ?? 0),
                ];
            }
        }

        // Load existing profile for lock comparisons
        $existingProfile = UserProfile::findByUserId($this->config, $uid) ?? [];
        $existingPrivacy = [];
        if (!empty($existingProfile['privacy_settings'])) {
            $tmp = json_decode((string)$existingProfile['privacy_settings'], true);
            if (is_array($tmp)) { $existingPrivacy = $tmp; }
        }
        // Start from existing privacy to avoid wiping toggles that are controlled via async switches (no form names)
        $privacy = is_array($existingPrivacy) ? $existingPrivacy : [];
        // Only update from POST when an input with that name exists in the form
        if (!empty($viewerIsAdmin)) {
            // Admins could have form controls for some fields; still honor presence-based updates
            if (array_key_exists('contact_visible', $_POST)) {
                $privacy['contact_visible'] = isset($_POST['contact_visible']);
            }
            if (array_key_exists('cgpa_visible', $_POST)) {
                $privacy['cgpa_visible'] = isset($_POST['cgpa_visible']);
            }
            if (array_key_exists('resume_visible', $_POST)) {
                $privacy['resume_visible'] = isset($_POST['resume_visible']);
            }
            if (array_key_exists('linkedin_visible', $_POST)) {
                $privacy['linkedin_visible'] = isset($_POST['linkedin_visible']);
            }
        }
        // Non-admin save: only certificates checkbox remains in the form; preserve others
        if (array_key_exists('certificates_visible', $_POST)) {
            if (in_array('certificates', $lockedKeys, true)) {
                // Preserve locked value
                $privacy['certificates_visible'] = (bool)($existingPrivacy['certificates_visible'] ?? true);
            } else {
                $privacy['certificates_visible'] = isset($_POST['certificates_visible']);
            }
        }
        // Ensure keys exist with sane defaults when missing
        $privacy['contact_visible'] = (bool)($privacy['contact_visible'] ?? false);
        $privacy['email_visible'] = (bool)($privacy['email_visible'] ?? false);
        $privacy['cgpa_visible'] = (bool)($privacy['cgpa_visible'] ?? false);
        $privacy['resume_visible'] = (bool)($privacy['resume_visible'] ?? true); // CV default visible
        $privacy['linkedin_visible'] = (bool)($privacy['linkedin_visible'] ?? true);
        $privacy['certificates_visible'] = (bool)($privacy['certificates_visible'] ?? true);

        // Prepare fields honoring locks for non-admins
    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName = trim((string)($_POST['last_name'] ?? ''));
        $bio = trim((string)($_POST['bio'] ?? ''));
        $portfolio = trim((string)($_POST['portfolio_url'] ?? ''));
        $linkedinUrl = trim((string)($_POST['linkedin_url'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? '')) ?: null;
        $address = trim((string)($_POST['address'] ?? '')) ?: null;
        $region = trim((string)($_POST['region'] ?? '')) ?: null;
        $resumeUrl = trim((string)($_POST['resume_url'] ?? '')) ?: null;

        if (!$viewerIsAdmin) {
            if (in_array('first_name', $lockedKeys, true)) { $firstName = (string)($existingProfile['first_name'] ?? $firstName); }
            if (in_array('last_name', $lockedKeys, true)) { $lastName = (string)($existingProfile['last_name'] ?? $lastName); }
            // Preserve disabled fields (not submitted) for non-admins
            if (!array_key_exists('first_name', $_POST)) { $firstName = (string)($existingProfile['first_name'] ?? $firstName); }
            if (!array_key_exists('last_name', $_POST)) { $lastName = (string)($existingProfile['last_name'] ?? $lastName); }
            if (in_array('bio', $lockedKeys, true)) { $bio = (string)($existingProfile['bio'] ?? $bio); }
            if (in_array('portfolio_url', $lockedKeys, true)) { $portfolio = (string)($existingProfile['portfolio_url'] ?? $portfolio); }
            if (in_array('linkedin_url', $lockedKeys, true)) { $linkedinUrl = (string)($existingProfile['linkedin_url'] ?? $linkedinUrl); }
            if (in_array('phone', $lockedKeys, true)) { $phone = $existingProfile['phone'] ?? $phone; }
            if (in_array('address', $lockedKeys, true)) { $address = $existingProfile['address'] ?? $address; }
            if (in_array('region', $lockedKeys, true)) { $region = $existingProfile['region'] ?? $region; }
            if (in_array('resume', $lockedKeys, true) || in_array('resume_url', $lockedKeys, true)) { $resumeUrl = $existingProfile['resume_url'] ?? $resumeUrl; }
        }

        // If profile picture is locked for non-admins, keep existing
        if (!$viewerIsAdmin && in_array('profile_picture_url', $lockedKeys, true)) {
            $pictureUrl = $existingProfile['profile_picture_url'] ?? (string)($_POST['current_picture'] ?? '');
        }

        UserProfile::upsert($this->config, $uid, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'bio' => $bio,
            'portfolio_url' => $portfolio,
            'linkedin_url' => $linkedinUrl,
            'profile_picture_url' => $pictureUrl ?? (string)($_POST['current_picture'] ?? ''),
            'privacy_settings' => json_encode($privacy, JSON_THROW_ON_ERROR),
            'phone' => $phone,
            'address' => $address,
            'region' => $region,
            'resume_url' => $resumeUrl,
        ]);

        // Persist per-field visibility into normalized table (best-effort)
        $flags = [
            'phone' => !empty($privacy['contact_visible']),
            'email' => !empty($privacy['email_visible']),
            'address' => !empty($privacy['contact_visible']),
            'cgpa' => !empty($privacy['cgpa_visible']),
            'resume' => !empty($privacy['resume_visible']),
            'cv' => !empty($privacy['resume_visible']),
            // LinkedIn cannot be hidden
            'linkedin' => true,
            'certificates' => !empty($privacy['certificates_visible']),
        ];
        // Honor visibility flag locks for non-admins
        if (!$viewerIsAdmin) {
            foreach (array_keys($flags) as $k) {
                if (in_array($k, $lockedKeys, true)) {
                    // Restore previous flag state
                    $currentFlags = ProfileVisibility::getFlagsForUser($this->config, $uid);
                    $flags[$k] = (bool)($currentFlags[$k] ?? $flags[$k]);
                }
            }
        }
        ProfileVisibility::setFlags($this->config, $uid, $flags);

        // Persist uploaded CV into user_documents if present
        if ($cvUploadedUrl) {
            \Nexus\Models\UserDocument::upsert($this->config, $uid, 'cv', array_merge($cvUploadedMeta, [ 'file_url' => $cvUploadedUrl ]));
        }

        // Skills (comma-separated)
        $skillsCsv = (string)($_POST['skills'] ?? '');
        $skillNames = array_values(array_filter(array_map(static fn($n) => trim($n), explode(',', $skillsCsv)), static fn($n) => $n !== ''));
        if ($skillNames) {
            UserSkill::syncNames($this->config, $uid, $skillNames);
        } else {
            // if empty string, clear all
            UserSkill::syncNames($this->config, $uid, []);
        }

        // Career interests
        $careerInterestsCsv = (string)($_POST['career_interests'] ?? '');
        $careerInterestNames = array_values(array_filter(array_map('trim', explode(',', $careerInterestsCsv)), static fn($n)=>$n!==''));
        CareerInterest::syncByNames($this->config, $uid, $careerInterestNames);

        // Change password (optional)
        $curPwd = (string)($_POST['current_password'] ?? '');
        $newPwd = (string)($_POST['new_password'] ?? '');
        $confPwd = (string)($_POST['confirm_password'] ?? '');
        $adminEditingOther = ($viewerIsAdmin && $uid !== $viewerId);
        // Trigger password change only if any relevant field provided
        if ($curPwd !== '' || $newPwd !== '' || $confPwd !== '') {
            if ($adminEditingOther) {
                // Admin setting another user's password: current password not required
                if ($newPwd === '' || $confPwd === '') {
                    http_response_code(400);
                    Flash::add('danger', 'New password and confirmation are required.');
                    $editUrl = '/profile/edit' . ('?user_id=' . (int)$uid);
                    $this->redirect($editUrl);
                    return '';
                }
                if ($newPwd !== $confPwd) {
                    http_response_code(400);
                    Flash::add('danger', 'New password and confirmation do not match.');
                    $editUrl = '/profile/edit' . ('?user_id=' . (int)$uid);
                    $this->redirect($editUrl);
                    return '';
                }
                $hash = password_hash($newPwd, PASSWORD_DEFAULT);
                if (User::updatePassword($this->config, $uid, $hash)) {
                    Flash::add('success', 'Password updated.');
                } else {
                    Flash::add('danger', 'Could not update password. Please try again.');
                }
            } else {
                // Self-service change: require current password
                if ($curPwd === '' || $newPwd === '' || $confPwd === '') {
                    http_response_code(400);
                    Flash::add('danger', 'All password fields are required to change your password.');
                    $editUrl = '/profile/edit';
                    $this->redirect($editUrl);
                    return '';
                }
                if ($newPwd !== $confPwd) {
                    http_response_code(400);
                    Flash::add('danger', 'New password and confirmation do not match.');
                    $editUrl = '/profile/edit';
                    $this->redirect($editUrl);
                    return '';
                }
                // Validate current password
                $freshUser = User::findById($this->config, $uid);
                if (!$freshUser || !password_verify($curPwd, (string)($freshUser['password_hash'] ?? ''))) {
                    http_response_code(400);
                    Flash::add('danger', 'Current password is incorrect.');
                    $editUrl = '/profile/edit';
                    $this->redirect($editUrl);
                    return '';
                }
                // Hash and update
                $hash = password_hash($newPwd, PASSWORD_DEFAULT);
                if (User::updatePassword($this->config, $uid, $hash)) {
                    Flash::add('success', 'Your password has been updated.');
                } else {
                    Flash::add('danger', 'Could not update password. Please try again.');
                }
            }
        }

    // Course interests (students only): keep only 'struggling'
    if ($user['role'] === 'student') {
        $coursesStrugglingCsv = (string)($_POST['courses_struggling'] ?? '');
        $coursesInterested = [];
        $coursesStruggling = array_values(array_filter(array_map('trim', explode(',', $coursesStrugglingCsv)), static fn($n)=>$n!==''));
        UserCourseInterest::sync($this->config, $uid, $coursesInterested, $coursesStruggling);
    }

        // Projects minimal upsert: expect arrays of fields
        // Handle deletions first if requested
        $removedAll = !empty($_POST['projects_remove_all']) && (string)$_POST['projects_remove_all'] === '1';
        if ($removedAll) {
            // Bulk delete then skip individual delete list as it's redundant
            StudentProject::deleteAllForUser($this->config, $uid);
        } else {
            if (isset($_POST['project_delete_ids']) && is_array($_POST['project_delete_ids'])) {
                foreach ($_POST['project_delete_ids'] as $delId) {
                    $pid = (int)$delId;
                    if ($pid > 0) {
                        StudentProject::delete($this->config, $uid, $pid);
                    }
                }
            }
        }
        $projectsPayload = (string)($_POST['projects_payload'] ?? '');
        $savedIds = [];
        $handledProjectsViaJson = false;
        if ($projectsPayload !== '') {
            $rows = json_decode($projectsPayload, true);
            if (is_array($rows)) {
                if (count($rows) > 0) {
                    $handledProjectsViaJson = true;
                    foreach ($rows as $row) {
                        $title = trim((string)($row['title'] ?? ''));
                        if ($title === '') { continue; }
                        $pid = isset($row['id']) && $row['id'] !== '' ? (int)$row['id'] : null;
                        $data = [
                            'title' => $title,
                            'short_description' => trim((string)($row['short_description'] ?? '')),
                            'github_url' => trim((string)($row['github_url'] ?? '')),
                            'portfolio_url' => trim((string)($row['portfolio_url'] ?? '')),
                            'certificate_url' => trim((string)($row['certificate_url'] ?? '')),
                        ];
                        $newId = StudentProject::upsert($this->config, $pid, $uid, $data);
                        $savedIds[] = $newId;
                    }
                }
            }
        }
        if (!$handledProjectsViaJson && isset($_POST['project_title']) && is_array($_POST['project_title'])) {
            $count = count($_POST['project_title']);
            for ($i=0; $i<$count; $i++) {
                $title = trim((string)($_POST['project_title'][$i] ?? ''));
                if ($title === '') continue;
                $data = [
                    'title' => $title,
                    'short_description' => trim((string)($_POST['project_desc'][$i] ?? '')),
                    'github_url' => trim((string)($_POST['project_github'][$i] ?? '')),
                    'portfolio_url' => trim((string)($_POST['project_portfolio'][$i] ?? '')),
                    'certificate_url' => trim((string)($_POST['project_certificate'][$i] ?? '')),
                ];
                $pid = isset($_POST['project_id'][$i]) && $_POST['project_id'][$i] !== '' ? (int)$_POST['project_id'][$i] : null;
                $newId = StudentProject::upsert($this->config, $pid, $uid, $data);
                $savedIds[] = $newId;
            }
        }
        // If not a bulk remove, reconcile: delete any of the user's existing projects not present in savedIds
        if (!$removedAll) {
            $existingIds = StudentProject::listIdsByUser($this->config, $uid);
            $toDelete = array_diff($existingIds, $savedIds);
            foreach ($toDelete as $delId) {
                StudentProject::delete($this->config, $uid, (int)$delId);
            }
        }

        // Certificates upsert with robust handling (bulk delete, per-row delete, JSON payload, reconciliation)
        $certsRemovedAll = !empty($_POST['certs_remove_all']) && (string)$_POST['certs_remove_all'] === '1';
        if ($certsRemovedAll) {
            UserCertificate::deleteAllForUser($this->config, $uid);
        } else {
            if (isset($_POST['cert_delete_ids']) && is_array($_POST['cert_delete_ids'])) {
                foreach ($_POST['cert_delete_ids'] as $delId) {
                    $cid = (int)$delId;
                    if ($cid > 0) { UserCertificate::delete($this->config, $uid, $cid); }
                }
            }
        }
        $certsSavedIds = [];
        $certsPayload = (string)($_POST['certs_payload'] ?? '');
        $handledCertsViaJson = false;
        if ($certsPayload !== '') {
            $rows = json_decode($certsPayload, true);
            if (is_array($rows)) {
                if (count($rows) > 0) {
                    $handledCertsViaJson = true;
                    foreach ($rows as $row) {
                        $title = trim((string)($row['title'] ?? ''));
                        if ($title === '') { continue; }
                        $cid = isset($row['id']) && $row['id'] !== '' ? (int)$row['id'] : null;
                        $data = [
                            'title' => $title,
                            'description' => trim((string)($row['description'] ?? '')),
                            'url' => trim((string)($row['url'] ?? '')),
                            'issued_by' => trim((string)($row['issued_by'] ?? '')),
                            'issued_on' => ((string)($row['issued_on'] ?? '')) ?: null,
                        ];
                        $newId = UserCertificate::upsert($this->config, $cid, $uid, $data);
                        $certsSavedIds[] = $newId;
                    }
                }
            }
        }
        if (!$handledCertsViaJson && isset($_POST['cert_title']) && is_array($_POST['cert_title'])) {
            $count = count($_POST['cert_title']);
            for ($i=0; $i<$count; $i++) {
                $title = trim((string)($_POST['cert_title'][$i] ?? ''));
                if ($title === '') continue;
                $data = [
                    'title' => $title,
                    'description' => trim((string)($_POST['cert_desc'][$i] ?? '')),
                    'url' => trim((string)($_POST['cert_url'][$i] ?? '')),
                    'issued_by' => trim((string)($_POST['cert_issuer'][$i] ?? '')),
                    'issued_on' => ((string)($_POST['cert_date'][$i] ?? '')) ?: null,
                ];
                $cid = isset($_POST['cert_id'][$i]) && $_POST['cert_id'][$i] !== '' ? (int)$_POST['cert_id'][$i] : null;
                $newId = UserCertificate::upsert($this->config, $cid, $uid, $data);
                $certsSavedIds[] = $newId;
            }
        }
        if (!$certsRemovedAll) {
            $existingCertIds = UserCertificate::listIdsByUser($this->config, $uid);
            $toDelete = array_diff($existingCertIds, $certsSavedIds);
            foreach ($toDelete as $did) { UserCertificate::delete($this->config, $uid, (int)$did); }
        }

        // Role specifics
        if ($user['role'] === 'student') {
            $existing = Student::findByUserId($this->config, $uid) ?? [];
            Student::upsert($this->config, $uid, [
                'program_level' => (string)($_POST['program_level'] ?? ($existing['program_level'] ?? '')) ?: ($existing['program_level'] ?? null),
                'department' => (string)($_POST['department'] ?? ($existing['department'] ?? '')) ?: ($existing['department'] ?? null),
                // CGPA and University ID are admin-only editable
                'cgpa' => (!$viewerIsAdmin && in_array('cgpa', $lockedKeys, true)) ? ($existing['cgpa'] ?? null)
                    : ($viewerIsAdmin ? (((isset($_POST['cgpa']) && $_POST['cgpa'] !== '') ? (float)$_POST['cgpa'] : null)) : ($existing['cgpa'] ?? null)),
                'university_id' => (!$viewerIsAdmin && in_array('university_id', $lockedKeys, true)) ? ($existing['university_id'] ?? null)
                    : ($viewerIsAdmin ? trim((string)($_POST['university_id'] ?? ($existing['university_id'] ?? ''))) : ($existing['university_id'] ?? null)),
                'admission_year' => (isset($_POST['admission_year']) && $_POST['admission_year'] !== '') ? (int)$_POST['admission_year'] : ($existing['admission_year'] ?? null),
                'admission_trimester' => (string)($_POST['admission_trimester'] ?? ($existing['admission_trimester'] ?? '')) ?: ($existing['admission_trimester'] ?? null),
                'current_semester' => $existing['current_semester'] ?? null,
            ]);
        } elseif ($user['role'] === 'alumni') {
            $existingA = Alumni::findByUserId($this->config, $uid) ?? [];
            $alumniProgram = $viewerIsAdmin ? (string)($_POST['alumni_program'] ?? '') : '';
            Alumni::upsert($this->config, $uid, [
                'company' => trim((string)($_POST['company'] ?? '')),
                'job_title' => trim((string)($_POST['job_title'] ?? '')),
                'years_of_experience' => (isset($_POST['years_of_experience']) && $_POST['years_of_experience'] !== '') ? (int)$_POST['years_of_experience'] : null,
                // Graduation year: preserve for non-admins if not provided
                'graduation_year' => (!$viewerIsAdmin ? ($existingA['graduation_year'] ?? null)
                    : ((isset($_POST['graduation_year']) && $_POST['graduation_year'] !== '') ? (int)$_POST['graduation_year'] : null)),
                // CGPA is admin-only editable similar to students
                'cgpa' => (!$viewerIsAdmin && in_array('cgpa', $lockedKeys, true)) ? ($existingA['cgpa'] ?? null)
                    : ($viewerIsAdmin ? (((isset($_POST['alumni_cgpa']) && $_POST['alumni_cgpa'] !== '') ? (float)$_POST['alumni_cgpa'] : null)) : ($existingA['cgpa'] ?? null)),
                // mentorship_availability: now controlled by toggle in UI
                'mentorship_availability' => isset($_POST['mentorship_availability']) ? 1 : 0,
                'max_mentorship_slots' => (isset($_POST['max_mentorship_slots']) && $_POST['max_mentorship_slots'] !== '') ? (int)$_POST['max_mentorship_slots'] : 5,
                'industry' => trim((string)($_POST['industry'] ?? '')) ?: null,
                // Alumni ID (formerly University ID/Registration) is admin-only editable
                'university_id' => (!$viewerIsAdmin && in_array('university_id', $lockedKeys, true)) ? ($existingA['university_id'] ?? null)
                    : ($viewerIsAdmin ? (trim((string)($_POST['alumni_university_id'] ?? ($existingA['university_id'] ?? ''))) ?: null) : ($existingA['university_id'] ?? null)),
                // Student ID (historical student identifier) is admin-only editable
                'student_id_number' => (!$viewerIsAdmin && in_array('student_id_number', $lockedKeys, true)) ? ($existingA['student_id_number'] ?? null)
                    : ($viewerIsAdmin ? (trim((string)($_POST['alumni_student_id'] ?? ($existingA['student_id_number'] ?? ''))) ?: null) : ($existingA['student_id_number'] ?? null)),
                'program_level' => ($alumniProgram !== '' ? $alumniProgram : ($existingA['program_level'] ?? null)),
            ]);
            AlumniPreference::upsert($this->config, $uid, [
                // Dropdown sets 1 for taking, 0 for not; store null when not provided
                'mentees_allowed' => (isset($_POST['mentees_allowed']) && $_POST['mentees_allowed'] !== '') ? (int)$_POST['mentees_allowed'] : null,
                'meeting_type' => (string)($_POST['meeting_type'] ?? '' ) ?: null,
                'specific_requirements' => trim((string)($_POST['specific_requirements'] ?? '')) ?: null,
                'timezone' => trim((string)($_POST['timezone'] ?? '')) ?: null,
                'preferred_hours' => trim((string)($_POST['preferred_hours'] ?? '')) ?: null,
            ]);
            // No separate direct update needed; upsert above handles program_level
            $focusCsv = (string)($_POST['mentorship_focus_areas'] ?? '');
            $focusNames = array_values(array_filter(array_map('trim', explode(',', $focusCsv)), static fn($n)=>$n!==''));
            AlumniFocusSkill::syncSkillNames($this->config, $uid, $focusNames);
        } elseif ($user['role'] === 'recruiter') {
            $existingR = Recruiter::findByUserId($this->config, $uid) ?? [];
            Recruiter::upsert($this->config, $uid, [
                'company_name' => (!$viewerIsAdmin ? ($existingR['company_name'] ?? '') : trim((string)($_POST['company_name'] ?? ($existingR['company_name'] ?? '')))),
                'company_description' => trim((string)($_POST['company_description'] ?? '')),
                'company_website' => trim((string)($_POST['company_website'] ?? '')),
                'company_logo_url' => trim((string)($_POST['company_logo_url'] ?? '')),
                'company_size' => (string)($_POST['company_size'] ?? ''),
                'industry' => trim((string)($_POST['industry'] ?? '')),
                'hr_contact_name' => (!$viewerIsAdmin && in_array('hr_contact_name', $lockedKeys, true)) ? ($existingR['hr_contact_name'] ?? null) : trim((string)($_POST['hr_contact_name'] ?? '')),
                'hr_contact_email' => (!$viewerIsAdmin && in_array('hr_contact_email', $lockedKeys, true)) ? ($existingR['hr_contact_email'] ?? null) : trim((string)($_POST['hr_contact_email'] ?? '')),
                'company_location' => (!$viewerIsAdmin && in_array('company_location', $lockedKeys, true)) ? ($existingR['company_location'] ?? null) : (trim((string)($_POST['company_location'] ?? '')) ?: null),
                'hr_contact_role' => (!$viewerIsAdmin && in_array('hr_contact_role', $lockedKeys, true)) ? ($existingR['hr_contact_role'] ?? null) : (trim((string)($_POST['hr_contact_role'] ?? '')) ?: null),
                'hr_contact_phone' => (!$viewerIsAdmin && in_array('hr_contact_phone', $lockedKeys, true)) ? ($existingR['hr_contact_phone'] ?? null) : (trim((string)($_POST['hr_contact_phone'] ?? '')) ?: null),
                'career_page_url' => trim((string)($_POST['career_page_url'] ?? '')) ?: null,
                'company_linkedin' => trim((string)($_POST['company_linkedin'] ?? '')) ?: null,
                'social_links' => [],
            ]);
            // Quick-add positions
            if (isset($_POST['position_title']) && is_array($_POST['position_title'])) {
                $count = count($_POST['position_title']);
                for ($i=0; $i<$count; $i++) {
                    $t = trim((string)($_POST['position_title'][$i] ?? ''));
                    if ($t === '') continue;
                    $data = [
                        'title' => $t,
                        'description' => trim((string)($_POST['position_description'][$i] ?? '')),
                        'deadline' => ((string)($_POST['position_deadline'][$i] ?? '')) ?: null,
                        'type' => 'job',
                        'qualifications' => trim((string)($_POST['position_qualifications'][$i] ?? '')),
                    ];
                    $skillsCsv = (string)($_POST['position_skills'][$i] ?? '');
                    $skillNames = array_values(array_filter(array_map('trim', explode(',', $skillsCsv)), static fn($n)=>$n!==''));
                    RecruiterPosition::upsert($this->config, null, $uid, $data, $skillNames);
                }
            }
        }

    $showUrl = '/profile' . (($viewerIsAdmin && $uid !== $viewerId) ? ('?user_id=' . (int)$uid) : '');
    $this->redirect($showUrl);
    }

    private function loadRoleData(int $userId, string $role): ?array
    {
        return match ($role) {
            'student' => Student::findByUserId($this->config, $userId),
            'alumni' => Alumni::findByUserId($this->config, $userId),
            'recruiter' => Recruiter::findByUserId($this->config, $userId),
            default => null,
        };
    }

    public function public(int $id): string
    {
        Auth::enforceAuth();
        $subjectId = (int)$id;
        $subject = User::findById($this->config, $subjectId);
        if (!$subject || (int)($subject['is_active'] ?? 0) !== 1) {
            http_response_code(404);
            return 'Profile not found';
        }
        $viewerId = (int)Auth::id();
        $viewer = User::findById($this->config, $viewerId);
        $viewerRole = (string)($viewer['role'] ?? '');
        $viewerIsAdmin = ($viewerRole === 'admin') || \Nexus\Helpers\Gate::has($this->config, $viewerId, 'manage.permissions');
        $isOwner = ($viewerId === $subjectId);

        $profile = UserProfile::findByUserId($this->config, $subjectId);
        $roleData = $this->loadRoleData($subjectId, (string)$subject['role']);
        $skills = UserSkill::getNamesByUser($this->config, $subjectId);
        // Ensure badges reflect current reputation before render
        try {
            $roleNow = (string)($subject['role'] ?? '');
            if (in_array($roleNow, ['student','alumni'], true)) {
                Gamify::autoAwardBadges($this->config, $subjectId);
            }
        } catch (\Throwable $e) { /* non-fatal */ }

        // Reuse the same view; downstream logic will honor viewerIsAdmin/isOwner for visibility
        $user = $subject; // adapt var name expected by view
        return $this->view('profile.show', compact('user','profile','roleData','skills','viewerIsAdmin','isOwner'));
    }

    public function toggleVisibility(): string
    {
        Auth::enforceAuth();
        if (!Csrf::check($_POST['_token'] ?? null)) {
            http_response_code(419);
            return 'CSRF token mismatch';
        }
        $uid = (int)Auth::id();
        $viewer = Auth::user();
        $viewerIsAdmin = (($viewer['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($this->config, $uid, 'manage.permissions');
        $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : $uid;
        if (!$viewerIsAdmin && $targetUserId !== $uid) {
            http_response_code(403);
            return 'Forbidden';
        }
        $field = (string)($_POST['field'] ?? '');
        if (!in_array($field, ['phone','email','cgpa','cv','resume'], true)) {
            http_response_code(400);
            return 'Invalid field';
        }
        $val = isset($_POST['visible']) ? ((int)$_POST['visible'] ? 1 : 0) : 0;
        // Persist normalized flag
        ProfileVisibility::setFlags($this->config, $targetUserId, [$field => (bool)$val]);
        // Update JSON fallback without wiping other fields
        try {
            $prof = UserProfile::findByUserId($this->config, $targetUserId) ?? [];
            $ps = $prof['privacy_settings'] ?? '{}';
            $psArr = is_array($ps) ? $ps : (json_decode((string)$ps, true) ?: []);
            if ($field === 'phone') { $psArr['contact_visible'] = (bool)$val; }
            if ($field === 'email') { $psArr['email_visible'] = (bool)$val; }
            if ($field === 'cgpa') { $psArr['cgpa_visible'] = (bool)$val; }
            if ($field === 'cv' || $field === 'resume') { $psArr['resume_visible'] = (bool)$val; }
            UserProfile::upsert($this->config, $targetUserId, [
                'first_name' => (string)($prof['first_name'] ?? ''),
                'last_name' => (string)($prof['last_name'] ?? ''),
                'bio' => (string)($prof['bio'] ?? ''),
                'portfolio_url' => (string)($prof['portfolio_url'] ?? ''),
                'linkedin_url' => (string)($prof['linkedin_url'] ?? ''),
                'profile_picture_url' => (string)($prof['profile_picture_url'] ?? ''),
                'privacy_settings' => json_encode($psArr),
                'phone' => $prof['phone'] ?? null,
                'address' => $prof['address'] ?? null,
                'region' => $prof['region'] ?? null,
                'resume_url' => $prof['resume_url'] ?? null,
            ]);
        } catch (\Throwable $e) { /* ignore */ }
        $return = (string)($_POST['return_to'] ?? '/profile');
        $this->redirect($return ?: '/profile');
    }

    public function removeCv(): string
    {
        Auth::enforceAuth();
        if (!Csrf::check($_POST['_token'] ?? null)) {
            http_response_code(419);
            return 'CSRF token mismatch';
        }
        $uid = (int)Auth::id();
        $viewer = Auth::user();
        $viewerIsAdmin = (($viewer['role'] ?? '') === 'admin') || \Nexus\Helpers\Gate::has($this->config, $uid, 'manage.permissions');
        $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : $uid;
        if (!$viewerIsAdmin && $targetUserId !== $uid) {
            http_response_code(403);
            return 'Forbidden';
        }
        try {
            $cvDoc = \Nexus\Models\UserDocument::getByUserAndType($this->config, $targetUserId, 'cv');
            if ($cvDoc && !empty($cvDoc['file_url'])) {
                $fs = Upload::webToFsPath((string)$cvDoc['file_url']);
                if ($fs && is_file($fs)) { @unlink($fs); }
            }
            \Nexus\Models\UserDocument::delete($this->config, $targetUserId, 'cv');
            $prof = UserProfile::findByUserId($this->config, $targetUserId) ?? [];
            UserProfile::upsert($this->config, $targetUserId, [
                'first_name' => (string)($prof['first_name'] ?? ''),
                'last_name' => (string)($prof['last_name'] ?? ''),
                'bio' => (string)($prof['bio'] ?? ''),
                'portfolio_url' => (string)($prof['portfolio_url'] ?? ''),
                'linkedin_url' => (string)($prof['linkedin_url'] ?? ''),
                'profile_picture_url' => (string)($prof['profile_picture_url'] ?? ''),
                'privacy_settings' => (string)($prof['privacy_settings'] ?? '{}'),
                'phone' => $prof['phone'] ?? null,
                'address' => $prof['address'] ?? null,
                'region' => $prof['region'] ?? null,
                'resume_url' => null,
            ]);
        } catch (\Throwable $e) { /* ignore */ }
        $return = (string)($_POST['return_to'] ?? '/profile/edit');
        $this->redirect($return ?: '/profile/edit');
    }
}
