<?php
declare(strict_types=1);

namespace Nexus\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(Config $config): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config->get('db.host'),
            (int) $config->get('db.port'),
            $config->get('db.database'),
            $config->get('db.charset')
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            self::$pdo = new PDO($dsn, (string) $config->get('db.username'), (string) $config->get('db.password'), $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Database connection failed';
            exit;
        }
        return self::$pdo;
    }
}
