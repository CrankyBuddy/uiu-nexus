<?php
declare(strict_types=1);

namespace Nexus\Models;

use Nexus\Core\Config;
use Nexus\Core\Database;
use PDO;

final class UserCertificate
{
    public static function listByUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
    // MariaDB/MySQL do not support "NULLS LAST"; emulate by sorting by (issued_on IS NULL) then date desc
    // Show in strict insertion order (oldest first), so newly added appear at the bottom
    $stmt = $pdo->prepare('SELECT * FROM user_certificates WHERE user_id = :u ORDER BY certificate_id ASC');
        $stmt->execute([':u' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function upsert(Config $config, ?int $id, int $userId, array $data): int
    {
        $pdo = Database::pdo($config);
        if ($id) {
            $stmt = $pdo->prepare('UPDATE user_certificates SET title=:t, description=:d, url=:url, issued_by=:ib, issued_on=:io WHERE certificate_id=:id AND user_id = :u');
            $stmt->execute([':t'=>$data['title'],':d'=>$data['description'],':url'=>$data['url'],':ib'=>$data['issued_by'],':io'=>$data['issued_on'],':id'=>$id,':u'=>$userId]);
            return $id;
        }
        $stmt = $pdo->prepare('INSERT INTO user_certificates (user_id, title, description, url, issued_by, issued_on) VALUES (:u,:t,:d,:url,:ib,:io)');
        $stmt->execute([':u'=>$userId,':t'=>$data['title'],':d'=>$data['description'],':url'=>$data['url'],':ib'=>$data['issued_by'],':io'=>$data['issued_on']]);
        return (int)$pdo->lastInsertId();
    }

    public static function delete(Config $config, int $userId, int $id): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('DELETE FROM user_certificates WHERE certificate_id = :id AND user_id = :u');
        $stmt->execute([':id'=>$id, ':u'=>$userId]);
        return $stmt->rowCount() > 0;
    }

    public static function deleteAllForUser(Config $config, int $userId): bool
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('DELETE FROM user_certificates WHERE user_id = :u');
        $stmt->execute([':u' => $userId]);
        return true;
    }

    public static function listIdsByUser(Config $config, int $userId): array
    {
        $pdo = Database::pdo($config);
        $stmt = $pdo->prepare('SELECT certificate_id FROM user_certificates WHERE user_id = :u');
        $stmt->execute([':u' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
