<?php
declare(strict_types=1);

namespace Nexus\Core;

final class View
{
    public static string $basePath = __DIR__ . '/../Views';

    public static function render(string $view, array $data = []): string
    {
        $viewPath = rtrim(self::$basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';
        if (!file_exists($viewPath)) {
            return 'View not found';
        }
        extract($data, EXTR_OVERWRITE);
        // Render the view into $content
        ob_start();
        include $viewPath;
        $content = (string) ob_get_clean();

        // If the view already produced a full HTML document, don't wrap again.
        $raw = ltrim($content);
        $isFullDocument = (bool)preg_match('/<!doctype|<html\b/i', $raw);

        if ($isFullDocument) {
            $html = $content;
        } else {
            // Determine layout: default to layouts.main, allow override via $data['layout'] or $layout within the view,
            // and allow opting out by setting layout to false.
            $layout = $data['layout'] ?? ($layout ?? 'layouts.main');
            if ($layout !== false) {
                $layoutPath = rtrim(self::$basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, (string)$layout) . '.php';
                if (file_exists($layoutPath)) {
                    ob_start();
                    include $layoutPath; // expects $title and $content in scope
                    $html = (string) ob_get_clean();
                } else {
                    // Fallback to raw content if layout missing
                    $html = $content;
                }
            } else {
                // No layout requested
                $html = $content;
            }
        }

        // Auto-prefix absolute URLs (href/src/action starting with "/") with the app's base path (directory of public/index.php)
        // This allows the app to run from a subdirectory like /nexus/public without editing every template.
        $scriptDir = str_replace('\\', '/', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));
        if ($scriptDir && $scriptDir !== '/') {
            $base = $scriptDir; // e.g., /nexus/public
            // Prefix only if not already starting with the base
            $html = preg_replace_callback(
                '/\b(href|src|action)\s*=\s*"(\/[^"\s]*)"/i',
                function ($m) use ($base) {
                    $attr = $m[1];
                    $path = $m[2];
                    if (strpos($path, $base . '/') === 0) return $m[0];
                    // normalize double slashes
                    $new = $base . $path;
                    $new = preg_replace('#//+#', '/', $new);
                    return $attr . '="' . $new . '"';
                },
                $html
            ) ?? $html;
        }

        return $html;
    }
}
