<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    /**
     * Get a setting value with default fallback
     */
    public static function get(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, string $type = 'string'): void
    {
        Setting::set($key, $value, $type);
    }

    /**
     * Dashboard Settings
     */
    public static function getDashboardSettings(): array
    {
        return [
            'show_test_button' => (bool) static::get('dashboard.show_test_button', true),
        ];
    }

    /**
     * Attendance Settings
     */
    public static function getAttendanceSettings(): array
    {
        return [
            'default_time_in_start' => static::get('default_time_in_start', '07:00'),
            'default_time_in_end' => static::get('default_time_in_end', '08:00'),
            'default_time_out_start' => static::get('default_time_out_start', '15:00'),
            'default_time_out_end' => static::get('default_time_out_end', '16:00'),
        ];
    }

    /**
     * Notification Settings
     */
    public static function getNotificationSettings(): array
    {
        return [
            'enabled' => (bool) static::get('notification.enabled', true),
            'late_threshold' => (int) static::get('notification.late_threshold', 15),
            'absent_notification_time' => static::get('notification.absent_notification_time', '09:00'),
            'channels' => json_decode(static::get('notification.channels', '["whatsapp"]'), true),
            'wa_api_key' => static::get('wa_api_key', ''),
            'kesiswaan_whatsapp_number' => static::get('kesiswaan_whatsapp_number', ''),
        ];
    }

    /**
     * System Settings
     */
    public static function getSystemSettings(): array
    {
        return [
            'timezone' => static::get('system.timezone', 'Asia/Jakarta'),
            'date_format' => static::get('system.date_format', 'd/m/Y'),
            'language' => static::get('system.language', 'id'),
            'maintenance_mode' => (bool) static::get('system.maintenance_mode', false),
        ];
    }

    /**
     * WhatsApp Templates
     */
    public static function getWhatsAppTemplates(): array
    {
        return [
            'late_message' => static::get('wa_message_late', 'Halo {nama_siswa}, Anda terlambat masuk sekolah. Jam masuk: {jam_masuk}, seharusnya: {jam_seharusnya}'),
            'absent_message' => static::get('wa_message_absent', 'Halo, {nama_siswa} tidak hadir ke sekolah pada tanggal {tanggal}. Mohon konfirmasi.'),
            'present_message' => static::get('wa_message_present', 'Halo, {nama_siswa} telah hadir ke sekolah pada jam {jam_masuk}.'),
        ];
    }

    /**
     * Get all public settings (for frontend/dashboard use)
     */
    public static function getPublicSettings(): array
    {
        return Setting::getPublic();
    }

    /**
     * Check if notifications are enabled
     */
    public static function isNotificationEnabled(): bool
    {
        return (bool) static::get('notification.enabled', true);
    }

    /**
     * Check if maintenance mode is active
     */
    public static function isMaintenanceMode(): bool
    {
        return (bool) static::get('system.maintenance_mode', false);
    }

    /**
     * Get formatted date using system date format
     */
    public static function formatDate($date): string
    {
        $format = static::get('system.date_format', 'd/m/Y');
        return $date instanceof \DateTime ? $date->format($format) : date($format, strtotime($date));
    }

    /**
     * Get WhatsApp message template with variables replaced
     */
    public static function getWhatsAppMessage(string $type, array $variables = []): string
    {
        $template = static::get("wa_message_{$type}", '');

        foreach ($variables as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        return $template;
    }

    /**
     * Clear settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('settings');
    }

    /**
     * Refresh settings cache
     */
    public static function refreshCache(): void
    {
        static::clearCache();
        Setting::pluck('value', 'key')->toArray();
    }
}
