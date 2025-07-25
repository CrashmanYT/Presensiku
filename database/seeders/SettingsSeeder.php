<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Dashboard Settings
            [
                'key' => 'dashboard.show_test_button',
                'value' => '1',
                'type' => 'boolean',
                'group_name' => 'dashboard',
                'description' => 'Tampilkan tombol test di environment local',
                'is_public' => false,
            ],

            // Attendance Settings
            [
                'key' => 'default_time_in_start',
                'value' => '07:00',
                'type' => 'string',
                'group_name' => 'attendance',
                'description' => 'Default jam masuk mulai',
                'is_public' => false,
            ],
            [
                'key' => 'default_time_in_end',
                'value' => '08:00',
                'type' => 'string',
                'group_name' => 'attendance',
                'description' => 'Default jam masuk selesai',
                'is_public' => false,
            ],
            [
                'key' => 'default_time_out_start',
                'value' => '15:00',
                'type' => 'string',
                'group_name' => 'attendance',
                'description' => 'Default jam pulang mulai',
                'is_public' => false,
            ],
            [
                'key' => 'default_time_out_end',
                'value' => '16:00',
                'type' => 'string',
                'group_name' => 'attendance',
                'description' => 'Default jam pulang selesai',
                'is_public' => false,
            ],

            // Notification Settings
            [
                'key' => 'notification.enabled',
                'value' => '1',
                'type' => 'boolean',
                'group_name' => 'notification',
                'description' => 'Aktifkan sistem notifikasi',
                'is_public' => false,
            ],
            [
                'key' => 'notification.absent_notification_time',
                'value' => '09:00',
                'type' => 'string',
                'group_name' => 'notification',
                'description' => 'Jam kirim notifikasi tidak hadir',
                'is_public' => false,
            ],
            [
                'key' => 'notification.channels',
                'value' => '["whatsapp"]',
                'type' => 'json',
                'group_name' => 'notification',
                'description' => 'Channel notifikasi aktif',
                'is_public' => false,
            ],
            [
                'key' => 'wa_api_key',
                'value' => '',
                'type' => 'string',
                'group_name' => 'notification',
                'description' => 'WhatsApp API Key',
                'is_public' => false,
            ],
            [
                'key' => 'kesiswaan_whatsapp_number',
                'value' => '',
                'type' => 'string',
                'group_name' => 'notification',
                'description' => 'Nomor WhatsApp kesiswaan/BK',
                'is_public' => false,
            ],

            // System Settings
            [
                'key' => 'system.timezone',
                'value' => 'Asia/Jakarta',
                'type' => 'string',
                'group_name' => 'system',
                'description' => 'Timezone aplikasi',
                'is_public' => true,
            ],
            [
                'key' => 'system.date_format',
                'value' => 'd/m/Y',
                'type' => 'string',
                'group_name' => 'system',
                'description' => 'Format tampilan tanggal',
                'is_public' => true,
            ],
            [
                'key' => 'system.language',
                'value' => 'id',
                'type' => 'string',
                'group_name' => 'system',
                'description' => 'Bahasa default aplikasi',
                'is_public' => true,
            ],
            [
                'key' => 'system.maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group_name' => 'system',
                'description' => 'Mode maintenance',
                'is_public' => true,
            ],

            // WhatsApp Templates
            [
                'key' => 'wa_message_late',
                'value' => 'Halo {nama_siswa}, Anda terlambat masuk sekolah. Jam masuk: {jam_masuk}, seharusnya: {jam_seharusnya}',
                'type' => 'string',
                'group_name' => 'whatsapp',
                'description' => 'Template pesan terlambat',
                'is_public' => false,
            ],
            [
                'key' => 'wa_message_absent',
                'value' => 'Halo, {nama_siswa} tidak hadir ke sekolah pada tanggal {tanggal}. Mohon konfirmasi.',
                'type' => 'string',
                'group_name' => 'whatsapp',
                'description' => 'Template pesan tidak hadir',
                'is_public' => false,
            ],
            [
                'key' => 'wa_message_present',
                'value' => 'Halo, {nama_siswa} telah hadir ke sekolah pada jam {jam_masuk}.',
                'type' => 'string',
                'group_name' => 'whatsapp',
                'description' => 'Template pesan hadir',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Default settings created successfully!');
    }
}
