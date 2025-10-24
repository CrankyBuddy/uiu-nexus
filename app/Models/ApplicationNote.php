<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class ApplicationNote
{
    public static function add(Config $config, int $applicationId, int $authorUserId, string $noteText, bool $isInternal = true): int
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('INSERT INTO application_notes (application_id, author_id, note_text, is_internal) VALUES (:a,:u,:t,:i)');
        $stmt->execute([':a' => $applicationId, ':u' => $authorUserId, ':t' => $noteText, ':i' => $isInternal ? 1 : 0]);
        return (int)$pdo->lastInsertId();
    }

    public static function forApplication(Config $config, int $applicationId): array
    {
        $pdo = Database::pdo($config);
        $sql = 'SELECT n.*, u.email as author_email FROM application_notes n INNER JOIN users u ON u.user_id = n.author_id WHERE n.application_id = :a ORDER BY n.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':a' => $applicationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
