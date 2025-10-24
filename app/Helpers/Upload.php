<?php
declare(strict_types=1);

namespace Nexus\Helpers;

final class Upload
{
    // $webSubdir example: '/uploads/profiles'
    public static function save(array $file, string $webSubdir, array $allowedMime, int $maxBytes = 10_000_000): ?string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return null;
        if ($file['size'] > $maxBytes) return null;

        $mime = '';
        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']) ?: '';
        }
        if ($mime === '' && isset($file['type'])) {
            $mime = (string) $file['type'];
        }
        if (!in_array($mime, $allowedMime, true)) return null;

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'bin';
        $name = bin2hex(random_bytes(8)) . '.' . strtolower($ext);
        $publicRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
        $sub = DIRECTORY_SEPARATOR . trim($webSubdir, '\/');
        $targetDir = rtrim($publicRoot . $sub, DIRECTORY_SEPARATOR);
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        $target = $targetDir . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file($file['tmp_name'], $target)) return null;

        // Return web path
        $webPath = '/' . trim(str_replace(DIRECTORY_SEPARATOR, '/', $webSubdir), '/') . '/' . $name;
        return $webPath;
    }

    // Convert a web path like '/uploads/cv/abc.pdf' to an absolute filesystem path
    public static function webToFsPath(string $webPath): ?string
    {
        $webPath = trim($webPath);
        if ($webPath === '' || $webPath[0] !== '/') return null;
        $publicRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
        $rel = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($webPath, '/'));
        $fs = rtrim($publicRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;
        return $fs;
    }
}
