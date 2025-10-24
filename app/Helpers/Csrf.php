<?php
declare(strict_types=1);

namespace Nexus\Helpers;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function check(?string $token): bool
    {
        return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
