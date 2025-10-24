<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Core\Config;
use Nexus\Helpers\Csrf;
use Nexus\Helpers\Auth as AuthHelper;
use Nexus\Helpers\Gamify;
use Nexus\Models\User;
use Nexus\Models\SystemSetting;
use Nexus\Models\UserWallet;

final class AuthController extends Controller
{
    public function loginForm(): string
    {
        return $this->view('auth.login');
    }

    public function registerForm(): string
    {
        $u = \Nexus\Helpers\Auth::user();
        if (!$u || (($u['role'] ?? '') !== 'admin')) {
            http_response_code(403);
            return 'Only admins can create new accounts.';
        }
        return $this->view('auth.register');
    }

    public function login(): string
    {
        if (!Csrf::check($_POST['_token'] ?? null)) {
            http_response_code(419);
            return 'CSRF token mismatch';
        }
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $errors = [];
        if ($email === '' || $password === '') {
            $errors[] = 'Email and password are required.';
        }

        if ($errors) {
            return $this->view('auth.login', compact('errors', 'email'));
        }

        $user = User::findByEmail($this->config, $email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid credentials';
            return $this->view('auth.login', compact('errors', 'email'));
        }
        if (!(bool)$user['is_active']) {
            $errors[] = 'Account is suspended.';
            return $this->view('auth.login', compact('errors', 'email'));
        }
        // Platform suspension via user_feature_restrictions
        try {
            if (\Nexus\Helpers\Restrictions::isPlatformSuspended($this->config, (int)$user['user_id'])) {
                $errors[] = 'Your account is currently suspended from the platform.';
                return $this->view('auth.login', compact('errors', 'email'));
            }
        } catch (\Throwable $e) { /* ignore */ }

    AuthHelper::login($user);
    session_regenerate_id(true);
    User::touchLastLogin($this->config, (int)$user['user_id']);
    $this->redirect('/');
    }

    public function register(): string
    {
        $u = \Nexus\Helpers\Auth::user();
        if (!$u || (($u['role'] ?? '') !== 'admin')) {
            http_response_code(403);
            return 'Only admins can create new accounts.';
        }
        if (!Csrf::check($_POST['_token'] ?? null)) {
            http_response_code(419);
            return 'CSRF token mismatch';
        }
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['role'] ?? 'student');
        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!in_array($role, ['student','alumni','recruiter','admin'], true)) {
            $errors[] = 'Invalid role selected.';
        }

        // Role-specific validations and field collection
    $profileFirst = '';
    $profileLast = '';
        $student = $alumni = $recruiter = [];
    // admin_level removed

        // Note: we now collect split names directly from the form per role.

        $uiuRequired = in_array($role, ['student','alumni','admin'], true);
        if ($uiuRequired && !preg_match('/@uiu\.ac\.bd$/i', $email)) {
            $errors[] = 'Email must be a UIU email (@uiu.ac.bd) for the selected role.';
        }

        if ($role === 'student') {
            $profileFirst = trim((string)($_POST['student_first_name'] ?? ''));
            $profileLast = trim((string)($_POST['student_last_name'] ?? ''));
            if ($profileFirst === '' || $profileLast === '') { $errors[] = 'Student first and last name are required.'; }
            $student['department'] = trim((string)($_POST['student_department'] ?? '')) ?: null;
            $student['program_level'] = trim((string)($_POST['student_program'] ?? '')) ?: null;
            $student['university_id'] = trim((string)($_POST['student_university_id'] ?? '')) ?: null;
            $student['cgpa'] = (string)($_POST['student_cgpa'] ?? '');
            $student['admission_year'] = (int)($_POST['student_admission_year'] ?? 0);
            $student['admission_trimester'] = (string)($_POST['student_admission_trimester'] ?? '');
            if (!$student['department']) { $errors[] = 'Student department is required.'; }
            if (!in_array($student['program_level'], ['BSc','MSc'], true)) { $errors[] = 'Student program must be BSc or MSc.'; }
            if (!$student['university_id']) { $errors[] = 'Student ID is required.'; }
            // Non-editable later by user: CGPA must be provided by admin at creation
            if ($student['cgpa'] === '') {
                $errors[] = 'Student CGPA is required.';
            } else {
                $cg = (float)$student['cgpa'];
                if ($cg < 0.0 || $cg > 4.0) { $errors[] = 'Student CGPA must be between 0.00 and 4.00.'; }
            }
            if ($student['admission_year'] < 1990 || $student['admission_year'] > 2100) { $errors[] = 'Student admission year must be between 1990 and 2100.'; }
            if (!in_array($student['admission_trimester'], ['Spring','Summer','Fall'], true)) { $errors[] = 'Student admission trimester must be Spring, Summer, or Fall.'; }
        } elseif ($role === 'alumni') {
            $profileFirst = trim((string)($_POST['alumni_first_name'] ?? ''));
            $profileLast = trim((string)($_POST['alumni_last_name'] ?? ''));
            if ($profileFirst === '' || $profileLast === '') { $errors[] = 'Alumni first and last name are required.'; }
            $alumni['department'] = trim((string)($_POST['alumni_department'] ?? '')) ?: null;
            $alumni['graduation_year'] = (int)($_POST['alumni_graduation_year'] ?? 0);
            $alumni['university_id'] = trim((string)($_POST['alumni_university_id'] ?? '')) ?: null; // Alumni ID
            $alumni['student_id_number'] = trim((string)($_POST['alumni_student_id'] ?? '')) ?: null; // Historical student ID
            $alumni['cgpa'] = (string)($_POST['alumni_cgpa'] ?? '');
            $alumni['program_level'] = trim((string)($_POST['alumni_program'] ?? '')) ?: null;
            if (!$alumni['department']) { $errors[] = 'Alumni department is required.'; }
            if ($alumni['graduation_year'] < 1990 || $alumni['graduation_year'] > 2100) { $errors[] = 'Alumni graduation year must be between 1990 and 2100.'; }
            if (!$alumni['university_id']) { $errors[] = 'Alumni ID is required.'; }
            if (!$alumni['student_id_number']) { $errors[] = 'Student ID (historical) is required for alumni.'; }
            if ($alumni['cgpa'] === '') {
                $errors[] = 'Alumni CGPA is required.';
            } else {
                $cg = (float)$alumni['cgpa'];
                if ($cg < 0.0 || $cg > 4.0) { $errors[] = 'Alumni CGPA must be between 0.00 and 4.00.'; }
            }
            if (!in_array($alumni['program_level'], ['BSc','MSc'], true)) { $errors[] = 'Alumni program must be BSc or MSc.'; }
        } elseif ($role === 'recruiter') {
            $repFirst = trim((string)($_POST['recruiter_rep_first_name'] ?? ''));
            $repLast = trim((string)($_POST['recruiter_rep_last_name'] ?? ''));
            if ($repFirst === '' || $repLast === '') { $errors[] = 'Recruiter representative first and last name are required.'; }
            $profileFirst = $repFirst; $profileLast = $repLast;
            $recruiter['company_name'] = trim((string)($_POST['recruiter_company_name'] ?? ''));
            $recruiter['company_email'] = trim((string)($_POST['recruiter_company_email'] ?? ''));
            $recruiter['hr_contact_name'] = trim($repFirst . ' ' . $repLast);
            $recruiter['hr_contact_first_name'] = $repFirst;
            $recruiter['hr_contact_last_name'] = $repLast;
            $recruiter['hr_contact_email'] = $email;
            if ($recruiter['company_name'] === '') { $errors[] = 'Recruiter company name is required.'; }
            if ($recruiter['company_email'] === '' || !filter_var($recruiter['company_email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid recruiter company email is required.'; }
        } elseif ($role === 'admin') {
            $adminFirst = trim((string)($_POST['admin_first_name'] ?? ''));
            $adminLast = trim((string)($_POST['admin_last_name'] ?? ''));
            $adminRoleTitle = trim((string)($_POST['admin_role_title'] ?? ''));
            if ($adminFirst === '' || $adminLast === '') { $errors[] = "Admin's first and last name are required."; }
            if ($adminRoleTitle === '') { $errors[] = 'Admin role is required.'; }
            $profileFirst = $adminFirst; $profileLast = $adminLast;
        }

        if ($errors) {
            return $this->view('auth.register', compact('errors', 'email', 'role'));
        }

        if (User::findByEmail($this->config, $email)) {
            $errors[] = 'Email already registered.';
            return $this->view('auth.register', compact('errors', 'email', 'role'));
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = User::create($this->config, $email, $hash, $role);
        if (!$userId) {
            $errors[] = 'Registration failed. Please try again.';
            return $this->view('auth.register', compact('errors', 'email', 'role'));
        }

    // Ensure wallet exists with default balance
    UserWallet::ensureExists($this->config, (int)$userId);

    // Optional: Registration bonus and starter reputation to demonstrate gamification
        \Nexus\Models\UserWallet::transact($this->config, (int)$userId, 'Registration Bonus', 100, true, 'Welcome bonus', 'system', null);
        Gamify::addReputation(
            $this->config,
            (int)$userId,
            10,
            'account:registration_bonus',
            'system',
            null
        );

        // Ensure basic profile and role row exist so user appears in People directory (which joins user_profiles)
        try {
            // Profile name
            \Nexus\Models\UserProfile::upsert($this->config, (int)$userId, [
                'first_name' => $profileFirst,
                'last_name' => $profileLast,
            ]);
            // Role-specific rows
            if ($role === 'student') {
                \Nexus\Models\Student::upsert($this->config, (int)$userId, [
                    'department' => $student['department'] ?? null,
                    'program_level' => $student['program_level'] ?? null,
                    'university_id' => $student['university_id'] ?? null,
                    'cgpa' => isset($student['cgpa']) && $student['cgpa'] !== '' ? (float)$student['cgpa'] : null,
                    'admission_year' => $student['admission_year'] ?? null,
                    'admission_trimester' => $student['admission_trimester'] ?? null,
                ]);
            } elseif ($role === 'alumni') {
                \Nexus\Models\Alumni::upsert($this->config, (int)$userId, [
                    'department' => $alumni['department'] ?? null,
                    'graduation_year' => $alumni['graduation_year'] ?? null,
                    'university_id' => $alumni['university_id'] ?? null,
                    'student_id_number' => $alumni['student_id_number'] ?? null,
                    'cgpa' => isset($alumni['cgpa']) && $alumni['cgpa'] !== '' ? (float)$alumni['cgpa'] : null,
                    'program_level' => $alumni['program_level'] ?? null,
                ]);
            } elseif ($role === 'recruiter') {
                \Nexus\Models\Recruiter::upsert($this->config, (int)$userId, [
                    'company_name' => $recruiter['company_name'] ?? '',
                    'company_email' => $recruiter['company_email'] ?? null,
                    'hr_contact_name' => $recruiter['hr_contact_name'] ?? null,
                    'hr_contact_first_name' => $recruiter['hr_contact_first_name'] ?? null,
                    'hr_contact_last_name' => $recruiter['hr_contact_last_name'] ?? null,
                    'hr_contact_email' => $recruiter['hr_contact_email'] ?? null,
                ]);
            } elseif ($role === 'admin') {
                \Nexus\Models\Admin::upsert($this->config, (int)$userId, [
                    'role_title' => $adminRoleTitle ?? 'Admin',
                ]);
            }
        } catch (\Throwable $e) { /* ignore if profile already exists */ }

        // Admin-created users are auto-verified; redirect back to admin users list
        $this->redirect('/admin/users');
        return '';
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Csrf::check($_POST['_token'] ?? null)) {
                AuthHelper::logout();
            }
        }
        $this->redirect('/');
    }

    public function verify(): string
    {
        http_response_code(404);
        return 'Verification is not required.';
    }
}
