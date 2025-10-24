<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class ProfileVisibility
{
    public static function getFlagsForUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
        $flags = [];
        try {
            $st = $pdo->prepare('SELECT field_key, is_visible FROM profile_field_visibility WHERE user_id = :u');
            $st->execute([':u' => $userId]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $flags[(string)$row['field_key']] = ((int)$row['is_visible'] === 1);
            }
        } catch (\Throwable $e) {
            // table may not exist yet; ignore
        }
        // Fallback to JSON privacy_settings
        if (empty($flags)) {
            try {
                $st = $pdo->prepare('SELECT privacy_settings FROM user_profiles WHERE user_id = :u');
                $st->execute([':u' => $userId]);
                $ps = (string)($st->fetchColumn() ?: '');
                $psArr = [];
                if ($ps !== '') {
                    $tmp = json_decode($ps, true);
                    if (is_array($tmp)) { $psArr = $tmp; }
                }
                // Map common keys
                if (array_key_exists('contact_visible', $psArr)) {
                    $flags['phone'] = (bool)$psArr['contact_visible'];
                    $flags['address'] = (bool)$psArr['contact_visible'];
                }
                // Prefer specific toggles if present
                if (array_key_exists('phone_visible', $psArr)) {
                    $flags['phone'] = (bool)$psArr['phone_visible'];
                }
                if (array_key_exists('email_visible', $psArr)) {
                    $flags['email'] = (bool)$psArr['email_visible'];
                }
                if (array_key_exists('cgpa_visible', $psArr)) {
                    $flags['cgpa'] = (bool)$psArr['cgpa_visible'];
                }
                if (array_key_exists('resume_visible', $psArr)) { $flags['resume'] = (bool)$psArr['resume_visible']; }
                if (array_key_exists('linkedin_visible', $psArr)) { $flags['linkedin'] = (bool)$psArr['linkedin_visible']; }
                if (array_key_exists('certificates_visible', $psArr)) { $flags['certificates'] = (bool)$psArr['certificates_visible']; }
            } catch (\Throwable $e) {}
        }
        return $flags;
    }

    public static function setFlags(Config $config, int $userId, array $flags): void
    {
        $pdo = Database::pdo($config);
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare('INSERT INTO profile_field_visibility (user_id, field_key, is_visible) VALUES (:u,:k,:v)
                                 ON DUPLICATE KEY UPDATE is_visible = VALUES(is_visible), updated_at = CURRENT_TIMESTAMP');
            foreach ($flags as $key => $val) {
                $st->execute([':u' => $userId, ':k' => (string)$key, ':v' => (int) ((bool)$val)]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            // If table missing or error, swallow for now; JSON fallback still works
        }
    }
}
