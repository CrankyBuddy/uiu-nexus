<?php
declare(strict_types=1);

namespace Nexus\Controllers;

use Nexus\Core\Controller;
use Nexus\Helpers\Auth;
use Nexus\Helpers\Csrf;
use Nexus\Models\Student as StudentModel;
use Nexus\Models\Alumni as AlumniModel;
use Nexus\Models\StudentReference;

final class ReferencesController extends Controller
{
    // Student: manage my references (list)
    public function mine(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        if (($user['role'] ?? '') !== 'student') { http_response_code(403); echo 'Only students'; return ''; }
        $student = StudentModel::findByUserId($this->config, (int)$user['user_id']);
        if (!$student) { StudentModel::upsert($this->config, (int)$user['user_id'], []); $student = StudentModel::findByUserId($this->config, (int)$user['user_id']); }
        $refs = StudentReference::forStudent($this->config, (int)$student['student_id']);
        return $this->view('references/mine', ['references' => $refs]);
    }

    // Alumni: create a reference for a student they have mentored
    public function create(): string
    {
        Auth::enforceAuth();
        $user = Auth::user();
        $role = (string)($user['role'] ?? '');
        if ($role !== 'alumni' && $role !== 'admin') { http_response_code(403); echo 'Forbidden'; return ''; }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
            $studentId = (int)($_POST['student_id'] ?? 0);
            $text = trim((string)($_POST['reference_text'] ?? ''));
            $alumniId = null;
            if ($role === 'alumni') {
                $alumni = AlumniModel::findByUserId($this->config, (int)$user['user_id']);
                $alumniId = (int)($alumni['alumni_id'] ?? 0);
            } else {
                $alumniId = (int)($_POST['alumni_id'] ?? 0);
            }
            if ($studentId > 0 && $alumniId > 0) {
                StudentReference::create($this->config, $studentId, $alumniId, (int)$user['user_id'], $role, $text ?: null);
            }
            $this->redirect('/references/mine');
            return '';
        }
        return $this->view('references/create', []);
    }

    // Revoke an existing reference (mentor, student owner, or admin)
    public function revoke(int $id): string
    {
        Auth::enforceAuth();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $user = Auth::user();
        $role = (string)($user['role'] ?? '');
        // soft revoke; any role can request if owner/admin/mentor
        StudentReference::revoke($this->config, $id, (int)$user['user_id'], trim((string)($_POST['reason'] ?? '')) ?: null);
        $this->redirect('/references/mine');
        return '';
    }

    // Hard delete (owner or admin)
    public function delete(int $id): string
    {
        Auth::enforceAuth();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); echo 'Method not allowed'; return ''; }
        if (!Csrf::check($_POST['_token'] ?? null)) die('Invalid CSRF');
        $user = Auth::user();
        $role = (string)($user['role'] ?? '');
        if ($role !== 'admin' && $role !== 'student' && $role !== 'alumni') { http_response_code(403); echo 'Forbidden'; return ''; }
        StudentReference::delete($this->config, $id);
        $this->redirect('/references/mine');
        return '';
    }
}
