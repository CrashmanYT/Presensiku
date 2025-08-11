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
            'show_test_button' => (bool) static::get('dashboard.buttons.show_test', true),
        ];
    }

    /**
     * Attendance Settings
     */
    public static function getAttendanceSettings(): array
    {
        return [
            'default_time_in_start' => static::get('attendance.defaults.time_in_start', '07:00'),
            'default_time_in_end' => static::get('attendance.defaults.time_in_end', '08:00'),
            'default_time_out_start' => static::get('attendance.defaults.time_out_start', '15:00'),
            'default_time_out_end' => static::get('attendance.defaults.time_out_end', '16:00'),
        ];
    }

    /**
     * Notification Settings
     */
    public static function getNotificationSettings(): array
    {
        return [
            'enabled' => (bool) static::get('notifications.enabled', true),
            'absent_notification_time' => static::get('notifications.absent.notification_time', '09:00'),
            'channels' => static::get('notifications.channels', ['whatsapp']),
            'student_affairs_number' => static::get('notifications.whatsapp.student_affairs_number', ''),
            'administration_number' => static::get('notifications.whatsapp.administration_number', ''),
        ];
    }

    /**
     * Discipline Score Settings
     */
    public static function getDisciplineScores(): array
    {
        return [
            'hadir' => (int) static::get('discipline.scores.hadir', 5),
            'terlambat' => (int) static::get('discipline.scores.terlambat', -2),
            'izin' => (int) static::get('discipline.scores.izin', 0),
            'sakit' => (int) static::get('discipline.scores.sakit', 0),
            'tidak_hadir' => (int) static::get('discipline.scores.tidak_hadir', -5),
        ];
    }

    /**
     * System Settings
     */
    public static function getSystemSettings(): array
    {
        return [
            'timezone' => static::get('system.localization.timezone', 'Asia/Jakarta'),
            'date_format' => static::get('system.localization.date_format', 'd/m/Y'),
            'language' => static::get('system.localization.language', 'id'),
            'maintenance_mode' => (bool) static::get('system.maintenance_mode', false),
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
        return (bool) static::get('notifications.enabled', true);
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
        $format = static::get('system.localization.date_format', 'd/m/Y');

        return $date instanceof \DateTime ? $date->format($format) : date($format, strtotime($date));
    }

    /**
     * WhatsApp Templates
     */
    public static function getWhatsAppTemplates(): array
    {
        return static::get('notifications.whatsapp.templates', [
            'late' => [],
            'absent' => [],
            'permit' => [],
        ]);
    }

    /**
     * Get WhatsApp message template with variables replaced
     */
    public static function getWhatsAppMessage(string $type, array $variables = []): string
    {
        $templates = static::getWhatsAppTemplates();

        if (! isset($templates[$type]) || ! is_array($templates[$type]) || empty($templates[$type])) {
            return "Template pesan untuk '{$type}' tidak ditemukan atau kosong.";
        }

        $randomTemplate = $templates[$type][array_rand($templates[$type])];
        $templateString = $randomTemplate['message'] ?? '';

        if (empty($templateString)) {
            return "Format template pesan untuk '{$type}' tidak valid.";
        }

        // Replace variables in the template string
        foreach ($variables as $variable => $value) {
            $templateString = str_replace('{'.$variable.'}', $value, $templateString);
        }
        return $templateString;

        // $key = "notifications.whatsapp.templates.{$type}";
        // $templates = static::get($key, []);

        // if (! is_array($templates) || empty($templates)) {
        //     return "Template pesan untuk '{$type}' tidak ditemukan atau kosong.";
        // }

        // // Pick a random template from the repeater
        // $randomTemplate = $templates[array_rand($templates)];
        // $templateString = $randomTemplate['message'] ?? '';

        // if (empty($templateString)) {
        //     return "Format template pesan untuk '{$type}' tidak valid.";
        // }

        // foreach ($variables as $variable => $value) {
        //     $templateString = str_replace('{'.$variable.'}', $value, $templateString);
        // }

        // return $templateString;
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

    /**
     * Monthly Summary Settings (for Student Affairs / Kesiswaan)
     */
    public static function getMonthlySummarySettings(): array
    {
        return [
            'enabled' => (bool) static::get('notifications.whatsapp.monthly_summary.enabled', true),
            'output' => (string) static::get('notifications.whatsapp.monthly_summary.output', 'text'),
            'thresholds' => [
                'min_total_late' => (int) static::get('notifications.whatsapp.monthly_summary.thresholds.min_total_late', 3),
                'min_total_absent' => (int) static::get('notifications.whatsapp.monthly_summary.thresholds.min_total_absent', 2),
                'min_score' => (int) static::get('notifications.whatsapp.monthly_summary.thresholds.min_score', -5),
            ],
            'limit' => (int) static::get('notifications.whatsapp.monthly_summary.limit', 50),
            'send_time' => (string) static::get('notifications.whatsapp.monthly_summary.send_time', '07:30'),
        ];
    }
}
