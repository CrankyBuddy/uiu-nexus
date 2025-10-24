<?php
namespace Nexus\Helpers;

use Nexus\Core\Config;
use Nexus\Models\SystemSetting;

final class Setting
{
    /**
     * Get a setting value from system_settings table if present, else fallback to config file.
     * @param Config $config
     * @param string $key (dot notation, e.g. 'app.gamify.forum.vote_up_coins')
     * @param mixed $default
     * @param string $type ('integer', 'boolean', 'string', 'json')
     * @return mixed
     */
    public static function get(Config $config, string $key, $default = null, string $type = 'string')
    {
        $row = SystemSetting::get($config, $key);
        if ($row && isset($row['setting_value'])) {
            $val = $row['setting_value'];
            switch ($type) {
                case 'integer':
                    return (int)$val;
                case 'boolean':
                    return in_array(strtolower($val), ['1','true','on','yes'], true);
                case 'json':
                    $decoded = json_decode($val, true);
                    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $default;
                default:
                    return $val;
            }
        }
        // fallback to config file
        return $config->get($key, $default);
    }
}