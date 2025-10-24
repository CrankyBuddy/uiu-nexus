<?php
declare(strict_types=1);

namespace Nexus\Core;

abstract class Controller
{
    public function __construct(protected Config $config)
    {
    }

    protected function view(string $name, array $data = []): string
    {
        return View::render($name, $data);
    }

    protected function redirect(string $path): void
    {
        $scriptDir = str_replace('\\', '/', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));
        $target = $path;
        if ($scriptDir && $scriptDir !== '/') {
            $target = $scriptDir . (str_starts_with($path, '/') ? $path : '/' . $path);
        }
        header('Location: ' . $target);
        exit;
    }
}
