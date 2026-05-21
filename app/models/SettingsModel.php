<?php
declare(strict_types=1);

/**
 * Site-wide key/value settings (e.g. How to Play guide).
 * Single tiny table; one row per setting.
 */
class SettingsModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function get(string $key, string $default = ''): string
    {
        $row = $this->db->fetch(
            'SELECT setting_value FROM site_settings WHERE setting_key = ?',
            [$key]
        );
        if (!$row) {
            return $default;
        }
        return (string)($row['setting_value'] ?? $default);
    }

    public function set(string $key, string $value): void
    {
        $this->db->execute(
            'INSERT INTO site_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [$key, $value]
        );
    }
}
