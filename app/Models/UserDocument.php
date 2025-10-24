<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class UserDocument
{
    public static function getByUserAndType(Config $config, int $userId, string $docType): ?array
    {
        try {
            $pdo = Database::pdo($config);
            $st = $pdo->prepare('SELECT * FROM user_documents WHERE user_id = :u AND doc_type = :t LIMIT 1');
            $st->execute([':u' => $userId, ':t' => $docType]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            return null; // tolerant if table missing
        }
    }

    public static function upsert(Config $config, int $userId, string $docType, array $data): void
    {
        try {
            $pdo = Database::pdo($config);
            $st = $pdo->prepare('INSERT INTO user_documents (user_id, doc_type, file_name, file_url, mime_type, file_size) VALUES (:u, :t, :n, :url, :m, :s)
                ON DUPLICATE KEY UPDATE file_name = VALUES(file_name), file_url = VALUES(file_url), mime_type = VALUES(mime_type), file_size = VALUES(file_size)');
            $st->execute([
                ':u' => $userId,
                ':t' => $docType,
                ':n' => (string)($data['file_name'] ?? ''),
                ':url' => (string)($data['file_url'] ?? ''),
                ':m' => (string)($data['mime_type'] ?? ''),
                ':s' => isset($data['file_size']) ? (int)$data['file_size'] : null,
            ]);
        } catch (\Throwable $e) {
            // no-op if table missing
        }
    }

    public static function delete(Config $config, int $userId, string $docType): void
    {
        try {
            $pdo = Database::pdo($config);
            $st = $pdo->prepare('DELETE FROM user_documents WHERE user_id = :u AND doc_type = :t');
            $st->execute([':u' => $userId, ':t' => $docType]);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
