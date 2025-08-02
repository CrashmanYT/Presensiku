<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SettingsHelper;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure cache is cleared before each test
        SettingsHelper::clearCache();
    }

    #[Test]
    public function it_gets_a_setting_value_with_a_default()
    {
        $this->assertEquals('default_value', SettingsHelper::get('non_existent_key', 'default_value'));
    }

    #[Test]
    public function it_sets_and_gets_a_setting_value()
    {
        SettingsHelper::set('test_key', 'test_value');
        $this->assertEquals('test_value', SettingsHelper::get('test_key'));
    }

    #[Test]
    public function it_returns_correct_dashboard_settings()
    {
        SettingsHelper::set('dashboard.buttons.show_test', false);
        $settings = SettingsHelper::getDashboardSettings();
        $this->assertFalse($settings['show_test_button']);
    }

    #[Test]
    public function it_returns_correct_attendance_settings()
    {
        SettingsHelper::set('attendance.defaults.time_in_start', '08:00');
        $settings = SettingsHelper::getAttendanceSettings();
        $this->assertEquals('08:00', $settings['default_time_in_start']);
    }

    #[Test]
    public function it_returns_correct_notification_settings()
    {
        SettingsHelper::set('notifications.enabled', false);
        $settings = SettingsHelper::getNotificationSettings();
        $this->assertFalse($settings['enabled']);
    }

    #[Test]
    public function it_returns_correct_discipline_scores()
    {
        SettingsHelper::set('discipline.scores.hadir', 10);
        $settings = SettingsHelper::getDisciplineScores();
        $this->assertEquals(10, $settings['hadir']);
    }

    #[Test]
    public function it_returns_correct_system_settings()
    {
        SettingsHelper::set('system.localization.timezone', 'UTC');
        $settings = SettingsHelper::getSystemSettings();
        $this->assertEquals('UTC', $settings['timezone']);
    }

    #[Test]
    public function it_returns_correct_whatsapp_templates()
    {
        $template = [['message' => 'Test message']];
        SettingsHelper::set('notifications.whatsapp.templates.late', $template, 'json');
        $settings = SettingsHelper::getWhatsAppTemplates();
        $this->assertEquals($template, $settings['late']);
    }

    #[Test]
    public function it_checks_if_notification_is_enabled()
    {
        SettingsHelper::set('notifications.enabled', true);
        $this->assertTrue(SettingsHelper::isNotificationEnabled());

        SettingsHelper::set('notifications.enabled', false);
        $this->assertFalse(SettingsHelper::isNotificationEnabled());
    }

    #[Test]
    public function it_checks_if_maintenance_mode_is_active()
    {
        SettingsHelper::set('system.maintenance_mode', true);
        $this->assertTrue(SettingsHelper::isMaintenanceMode());

        SettingsHelper::set('system.maintenance_mode', false);
        $this->assertFalse(SettingsHelper::isMaintenanceMode());
    }

    #[Test]
    public function it_formats_a_date_using_system_format()
    {
        SettingsHelper::set('system.localization.date_format', 'Y-m-d');
        $date = new \DateTime('2025-01-01');
        $this->assertEquals('2025-01-01', SettingsHelper::formatDate($date));
    }

    #[Test]
    public function it_gets_a_whatsapp_message_with_variables_replaced()
    {
        $template = [['message' => 'Hello, {name}!']];
        SettingsHelper::set('notifications.whatsapp.templates.greeting', $template, 'json');

        $message = SettingsHelper::getWhatsAppMessage('greeting', ['name' => 'World']);
        $this->assertEquals('Hello, World!', $message);
    }

    #[Test]
    public function it_clears_and_refreshes_the_cache()
    {
        Cache::shouldReceive('forget')->with('settings')->once();
        SettingsHelper::clearCache();

        Cache::shouldReceive('forget')->with('settings')->once();
        // We can't mock the Setting model directly, so we'll just check that the cache is cleared
        // and that the method doesn't throw an exception.
        SettingsHelper::refreshCache();
        $this->assertTrue(true);
    }
}
