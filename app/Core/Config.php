<?php
declare(strict_types=1);

namespace Nexus\Core;

final class Config
{
    private array $data = [];

    public static function load(string $configDir): self
    {
        $instance = new self();
        $defaults = [
            'app' => [
                'name' => 'UIU NEXUS',
                'env' => 'local',
                'debug' => true,
                'url' => 'http://localhost',
                'timezone' => 'Asia/Dhaka',
            ],
            'db' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'nexus',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
        ];

        $instance->data = $defaults;

        foreach (['app.php', 'database.php'] as $file) {
            $path = rtrim($configDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
            if (file_exists($path)) {
                $loaded = require $path;
                if (is_array($loaded)) {
                    $instance->data = array_replace_recursive($instance->data, $loaded);
                }
            }
        }
        return $instance;
    }

    public function get(string $key, $default = null)
    {
        $segments = explode('.', $key);
        $value = $this->data;
        foreach ($segments as $seg) {
            if (!is_array($value) || !array_key_exists($seg, $value)) {
                return $default;
            }
            $value = $value[$seg];
        }
        return $value;
    }
}
