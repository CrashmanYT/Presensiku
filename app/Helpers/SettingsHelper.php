<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    /**
     * Get a setting value with default fallback.
     *
     * @param string $key     Dot-notated setting key
     * @param mixed  $default Default value if not set
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }

    /**
     * Set a setting value.
     *
     * @param string $key   Dot-notated setting key
     * @param mixed  $value Value to persist
     * @param string $type  Underlying type hint (e.g., 'string','int','bool','json')
     * @return void
     */
    public static function set(string $key, $value, string $type = 'string'): void
    {
        Setting::set($key, $value, $type);
    }

    /**
     * Dashboard Settings.
     *
     * @return array{show_test_button: bool}
     */
    public static function getDashboardSettings(): array
    {
        return [
            'show_test_button' => (bool) static::get('dashboard.buttons.show_test', true),
        ];
    }

    /**
     * Attendance Settings.
     *
     * @return array{default_time_in_start:string,default_time_in_end:string,default_time_out_start:string,default_time_out_end:string}
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
     * Notification Settings.
     *
     * @return array{
     *   enabled: bool,
     *   absent_notification_time: string,
     *   channels: array<int,string>,
     *   student_affairs_number: string,
     *   administration_number: string
     * }
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
     * Discipline Score Settings.
     *
     * @return array{hadir:int,terlambat:int,izin:int,sakit:int,tidak_hadir:int}
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
     * System Settings.
     *
     * @return array{timezone:string,date_format:string,language:string,maintenance_mode:bool}
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
     * Get all public settings (for frontend/dashboard use).
     *
     * @return array<string,mixed>
     */
    public static function getPublicSettings(): array
    {
        return Setting::getPublic();
    }

    /**
     * Check if notifications are enabled.
     */
    public static function isNotificationEnabled(): bool
    {
        return (bool) static::get('notifications.enabled', true);
    }

    /**
     * Check if maintenance mode is active.
     */
    public static function isMaintenanceMode(): bool
    {
        return (bool) static::get('system.maintenance_mode', false);
    }

    /**
     * Get formatted date using system date format.
     *
     * @param \DateTimeInterface|string $date
     * @return string
     */
    public static function formatDate($date): string
    {
        $format = static::get('system.localization.date_format', 'd/m/Y');

        return $date instanceof \DateTime ? $date->format($format) : date($format, strtotime($date));
    }

    /**
     * WhatsApp Templates.
     *
     * @return array<string, array<int, array{message:string}>>
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
     * Get a WhatsApp message template with variables replaced.
     * Picks a random template from the configured set and interpolates variables
     * using {varName} markers.
     *
     * @param string              $type       Template type key (e.g., 'late','absent','permit')
     * @param array<string,string> $variables Replacement variables
     * @return string
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
     * Clear settings cache.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::forget('settings');
    }

    /**
     * Refresh settings cache.
     *
     * @return void
     */
    public static function refreshCache(): void
    {
        static::clearCache();
        Setting::pluck('value', 'key')->toArray();
    }

    /**
     * Monthly Summary Settings (for Student Affairs / Kesiswaan).
     *
     * @return array{
     *   enabled: bool,
     *   output: string,
     *   thresholds: array{min_total_late:int,min_total_absent:int,min_score:int},
     *   limit: int,
     *   send_time: string
     * }
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

    /**
     * Daily Teacher Late Summary Settings (for Administration / Tata Usaha).
     *
     * @return array{
     *   enabled: bool,
     *   output: string,
     *   send_time: string
     * }
     */
    public static function getTeacherLateDailySettings(): array
    {
        // Default send_time: follow the end of time-in window so the list is complete
        $defaultSendTime = (string) static::get('attendance.defaults.time_in_end', '08:00');

        return [
            'enabled' => (bool) static::get('notifications.whatsapp.teacher_late_daily.enabled', true),
            // 'pdf_link' or 'pdf_attachment'
            'output' => (string) static::get('notifications.whatsapp.teacher_late_daily.output', 'pdf_link'),
            'send_time' => (string) static::get('notifications.whatsapp.teacher_late_daily.send_time', $defaultSendTime),
        ];
    }
}
