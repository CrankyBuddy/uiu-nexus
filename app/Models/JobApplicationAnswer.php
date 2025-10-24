<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class JobApplicationAnswer
{
    public static function saveAnswers(Config $config, int $applicationId, array $answers): void
    {
        $pdo = Database::pdo($config);
        if (!$answers) return;
        $ins = $pdo->prepare('INSERT INTO job_application_answers (application_id, question_id, answer_text) VALUES (:a,:q,:t)
            ON DUPLICATE KEY UPDATE answer_text = VALUES(answer_text)');
        foreach ($answers as $questionId => $text) {
            $ins->execute([':a' => $applicationId, ':q' => (int)$questionId, ':t' => (string)$text]);
        }
    }

    public static function forApplication(Config $config, int $applicationId): array
    {
        $pdo = Database::pdo($config);
        $st = $pdo->prepare('SELECT jaa.*, jaq.question_text, jaq.question_type FROM job_application_answers jaa JOIN job_application_questions jaq ON jaq.question_id = jaa.question_id WHERE jaa.application_id = :a ORDER BY jaq.order_no');
        $st->execute([':a' => $applicationId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
