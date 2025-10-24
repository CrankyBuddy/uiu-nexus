<?php
declare(strict_types=1);

namespace Nexus\Helpers;

final class Flash
{
    /**
     * Add a flash message for next request.
     * @param 'success'|'danger'|'warning'|'info' $type
     */
    public static function add(string $type, string $message): void
    {
        if (!isset($_SESSION)) { return; }
        if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Retrieve and clear flash messages.
     * @return array<int, array{type:string, message:string}>
     */
    public static function consume(): array
    {
        if (!isset($_SESSION) || empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            return [];
        }
        $msgs = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $msgs;
    }
}
