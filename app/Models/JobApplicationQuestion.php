<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class JobApplicationQuestion
{
    public static function upsertForJob(Config $config, int $jobId, array $questions): void
    {
        $pdo = Database::pdo($config);
        // Remove existing then insert new ordering
        $pdo->prepare('DELETE FROM job_application_questions WHERE job_id = :j')->execute([':j' => $jobId]);
        if (!$questions) return;
        $ins = $pdo->prepare('INSERT INTO job_application_questions (job_id, question_text, question_type, is_required, order_no) VALUES (:j,:t,:ty,:req,:ord)');
        $ord = 1;
        foreach ($questions as $q) {
            $ins->execute([
                ':j' => $jobId,
                ':t' => (string)($q['text'] ?? ''),
                ':ty' => (string)($q['type'] ?? 'text'),
                ':req' => !empty($q['required']) ? 1 : 0,
                ':ord' => $ord++,
            ]);
        }
    }

    public static function forJob(Config $config, int $jobId): array
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare('SELECT * FROM job_application_questions WHERE job_id = :j ORDER BY order_no ASC');
        $st->execute([':j' => $jobId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
