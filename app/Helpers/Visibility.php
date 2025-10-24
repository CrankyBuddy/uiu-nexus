<?php
declare(strict_types=1);

namespace Nexus\Helpers;

use Nexus\Core\Config;
use Nexus\Core\Database;
use Nexus\Models\User;
use PDO;

final class Visibility
{
    /**
     * Decide if viewer can see a given field of subject's profile.
    * fieldKey examples: phone, email, address, cgpa, resume, cv, linkedin, certificates,
     * recruiter hr fields: hr_contact_phone, hr_contact_email.
     * Context keys (optional):
     *  - studentAppliedToViewerJob (bool)
     *  - mentorshipAccepted (bool)
     */
    public static function canViewField(Config $config, int $viewerId, string $viewerRole, int $subjectId, string $fieldKey, array $context = []): bool
    {
        // LinkedIn is always visible if a URL exists; no hiding supported
        if ($fieldKey === 'linkedin') {
            return true;
        }
        // Admin override
        if ($viewerRole === 'admin' || Gate::has($config, $viewerId, 'manage.permissions')) {
            return true;
        }
        // Hard rule: Address is masked for everyone except admins
        if ($fieldKey === 'address') {
            return false;
        }
        // Owner always sees own fields
        if ($viewerId === $subjectId) {
            return true;
        }

        $pdo = Database::pdo($config);
        $subject = User::findById($config, $subjectId);
        $subjectRole = (string)($subject['role'] ?? '');

        // Contextual exceptions
        if ($viewerRole === 'recruiter' && !empty($context['studentAppliedToViewerJob']) && $subjectRole === 'student') {
            if (in_array($fieldKey, ['cgpa','phone','resume','cv','linkedin'], true)) {
                return true;
            }
        }
        // Mentorship: If the listing has a CGPA constraint, allow alumni (mentor)
        // to view the student's CGPA for requests to that listing, even if hidden.
        if ($viewerRole === 'alumni' && !empty($context['listingHasMinCgpa']) && $subjectRole === 'student') {
            if (in_array($fieldKey, ['cgpa'], true)) { return true; }
        }
        if ($viewerRole === 'alumni' && !empty($context['mentorshipAccepted']) && $subjectRole === 'student') {
            if (in_array($fieldKey, ['cgpa','cv'], true)) { return true; }
        }

        // Try normalized table first
        $st = $pdo->prepare('SELECT is_visible FROM profile_field_visibility WHERE user_id = :u AND field_key = :k LIMIT 1');
        $st->execute([':u' => $subjectId, ':k' => $fieldKey]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return (int)$row['is_visible'] === 1;
        }

        // Fallback to JSON privacy_settings in user_profiles
        $st2 = $pdo->prepare('SELECT privacy_settings FROM user_profiles WHERE user_id = :u');
        $st2->execute([':u' => $subjectId]);
        $ps = (string)($st2->fetchColumn() ?: '');
        $psArr = [];
        if ($ps !== '') {
            try { $tmp = json_decode($ps, true, 512, JSON_THROW_ON_ERROR); if (is_array($tmp)) { $psArr = $tmp; } } catch (\Throwable $e) {}
        }

        // Map JSON keys to field keys for fallback
        $jsonMap = [
            'phone' => 'contact_visible',
            'email' => 'email_visible',
            'address' => 'contact_visible',
            'cgpa' => 'cgpa_visible',
            'resume' => 'resume_visible',
            'cv' => 'resume_visible',
            'linkedin' => 'linkedin_visible',
            'certificates' => 'certificates_visible',
        ];

        if (isset($jsonMap[$fieldKey]) && array_key_exists($jsonMap[$fieldKey], $psArr)) {
            return (bool)$psArr[$jsonMap[$fieldKey]];
        }

        // Defaults if nothing set, by role and field
        $defaults = [
            'student' => [
                'phone' => false,
                'email' => false,
                'address' => false,
                'cgpa' => false,
                'resume' => true,
                'cv' => true,
                'linkedin' => true,
                'certificates' => true,
            ],
            'alumni' => [
                'phone' => false,
                'email' => false,
                'linkedin' => true,
                'portfolio' => true,
                'company' => true,
                'job_title' => true,
                'mentorship_rating' => false,
            ],
            'recruiter' => [
                'hr_contact_phone' => false,
                'hr_contact_email' => false,
            ],
        ];

        return (bool)($defaults[$subjectRole][$fieldKey] ?? false);
    }
}
