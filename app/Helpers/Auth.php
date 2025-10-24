<?php
declare(strict_types=1);

namespace Nexus\Helpers;

final class Auth
{
    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['user'] = $user;
        $_SESSION['last_active'] = time();
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function role(): ?string
    {
        return isset($_SESSION['user']['role']) ? (string) $_SESSION['user']['role'] : null;
    }

    public static function hasRole(string $role): bool
    {
        return self::role() === $role;
    }

    public static function enforceAuth(): void
    {
        self::ensureActive();
        if (!self::check()) {
            $base = str_replace('\\', '/', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));
            $target = ($base && $base !== '/') ? $base . '/auth/login' : '/auth/login';
            header('Location: ' . $target);
            exit;
        }
    }

    public static function enforceGuest(): void
    {
        if (self::check()) {
            $base = str_replace('\\', '/', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));
            $target = ($base && $base !== '/') ? $base . '/' : '/';
            header('Location: ' . $target);
            exit;
        }
    }

    public static function ensureActive(int $timeoutSeconds = 1800): void
    {
        $now = time();
        if (isset($_SESSION['last_active']) && ($now - (int)$_SESSION['last_active']) > $timeoutSeconds) {
            self::logout();
            $base = str_replace('\\', '/', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));
            $target = ($base && $base !== '/') ? $base . '/auth/login?timeout=1' : '/auth/login?timeout=1';
            header('Location: ' . $target);
            exit;
        }
        // If logged in and the platform restriction is now active, force logout
        try {
            if (isset($_SESSION['user_id']) && isset($GLOBALS['config']) && $GLOBALS['config'] instanceof \Nexus\Core\Config) {
                $uid = (int)$_SESSION['user_id'];
                if (\Nexus\Helpers\Restrictions::isPlatformSuspended($GLOBALS['config'], $uid)) {
                    self::logout();
                    $base = str_replace('\\', '/', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));
                    $target = ($base && $base !== '/') ? $base . '/auth/login?suspended=1' : '/auth/login?suspended=1';
                    header('Location: ' . $target);
                    exit;
                }
            }
        } catch (\Throwable $e) { /* ignore */ }
        $_SESSION['last_active'] = $now;
    }
}
