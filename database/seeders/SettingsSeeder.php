<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

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
                'key' => 'dashboard.buttons.show_test',
                'value' => '1',
                'type' => 'boolean',
                'group_name' => 'dashboard',
                'description' => 'Tampilkan tombol tes di dashboard absensi realtime.',
                'is_public' => false,
            ],

            // Attendance Settings
            [
                'key' => 'attendance.defaults.time_in_start',
                'value' => '07:00',
                'type' => 'string',
                'group_name' => 'attendance',
                'description' => 'Default jam masuk mulai.',
                'is_public' => false,
            ],
            [
                'key' => 'attendance.defaults.time_in_end',
                'value' => '08:00',
                'type' => 'string',
                'group_name' => 'attendance',
                'description' => 'Default jam masuk selesai.',
                'is_public' => false,
            ],
            [
                'key' => 'attendance.defaults.time_out_start',
                'value' => '15:00',
                'type' => 'string',
                'group_name' => 'attendance',
                'description' => 'Default jam pulang mulai.',
                'is_public' => false,
            ],
            [
                'key' => 'attendance.defaults.time_out_end',
                'value' => '16:00',
                'type' => 'string',
                'group_name' => 'attendance',
                'description' => 'Default jam pulang selesai.',
                'is_public' => false,
            ],

            // Notification Settings
            [
                'key' => 'notifications.enabled',
                'value' => '1',
                'type' => 'boolean',
                'group_name' => 'notifications',
                'description' => 'Aktifkan sistem notifikasi.',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.absent.notification_time',
                'value' => '09:00',
                'type' => 'string',
                'group_name' => 'notifications',
                'description' => 'Waktu untuk mengirim notifikasi tidak hadir.',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.channels',
                'value' => '["whatsapp"]',
                'type' => 'json',
                'group_name' => 'notifications',
                'description' => 'Channel notifikasi yang aktif.',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.whatsapp.student_affairs_number',
                'value' => '',
                'type' => 'string',
                'group_name' => 'notifications',
                'description' => 'Nomor WhatsApp kesiswaan/BK.',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.whatsapp.administration_number',
                'value' => '',
                'type' => 'string',
                'group_name' => 'notifications',
                'description' => 'Nomor WhatsApp Tata Usaha (TU) untuk laporan guru.',
                'is_public' => false,
            ],

            // Monthly Summary to Student Affairs (Kesiswaan)
            [
                'key' => 'notifications.whatsapp.monthly_summary.enabled',
                'value' => '1',
                'type' => 'boolean',
                'group_name' => 'notifications',
                'description' => 'Aktifkan pengiriman ringkasan bulanan siswa ke kesiswaan.',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.whatsapp.monthly_summary.thresholds.min_total_late',
                'value' => '3',
                'type' => 'integer',
                'group_name' => 'notifications',
                'description' => 'Minimal jumlah keterlambatan per bulan agar siswa masuk ringkasan.',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.whatsapp.monthly_summary.thresholds.min_total_absent',
                'value' => '2',
                'type' => 'integer',
                'group_name' => 'notifications',
                'description' => 'Minimal jumlah ketidakhadiran (alpa) per bulan agar siswa masuk ringkasan.',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.whatsapp.monthly_summary.thresholds.min_score',
                'value' => '-5',
                'type' => 'integer',
                'group_name' => 'notifications',
                'description' => 'Skor disiplin minimum (lebih kecil/sama dari nilai ini) agar siswa masuk ringkasan.',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.whatsapp.monthly_summary.limit',
                'value' => '50',
                'type' => 'integer',
                'group_name' => 'notifications',
                'description' => 'Batas maksimal jumlah siswa dalam ringkasan bulanan.',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.whatsapp.monthly_summary.output',
                'value' => 'pdf_attachment',
                'type' => 'string',
                'group_name' => 'notifications',
                'description' => 'Format keluaran ringkasan bulanan: text | pdf_link | pdf_attachment.',
                'is_public' => false,
            ],
            [
                'key' => 'notifications.whatsapp.monthly_summary.send_time',
                'value' => '07:30',
                'type' => 'string',
                'group_name' => 'notifications',
                'description' => 'Waktu kirim otomatis pada tanggal 1 setiap bulan (format HH:MM).',
                'is_public' => false,
            ],

            // System Settings
            [
                'key' => 'system.localization.timezone',
                'value' => 'Asia/Jakarta',
                'type' => 'string',
                'group_name' => 'system',
                'description' => 'Zona waktu aplikasi.',
                'is_public' => true,
            ],
            [
                'key' => 'system.localization.date_format',
                'value' => 'd/m/Y',
                'type' => 'string',
                'group_name' => 'system',
                'description' => 'Format tampilan tanggal.',
                'is_public' => true,
            ],
            [
                'key' => 'system.localization.language',
                'value' => 'id',
                'type' => 'string',
                'group_name' => 'system',
                'description' => 'Bahasa default aplikasi.',
                'is_public' => true,
            ],
            [
                'key' => 'system.maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group_name' => 'system',
                'description' => 'Mode perawatan sistem.',
                'is_public' => true,
            ],

            // WhatsApp Templates
            [
                'key' => 'notifications.whatsapp.templates',
                'value' => json_encode([
                    'late' => [
                        ['message' => 'Yth. Bapak/Ibu, ananda {nama_siswa} tercatat terlambat hari ini. Jam masuk: {jam_masuk}, seharusnya: {jam_seharusnya}.'],
                        ['message' => 'Halo {nama_siswa}, kamu terlambat masuk sekolah hari ini.'],
                    ],
                    'absent' => [
                        ['message' => 'Yth. Bapak/Ibu, ananda {nama_siswa} tidak tercatat hadir di sekolah pada tanggal {tanggal}. Mohon konfirmasinya.'],
                        ['message' => 'Anak anda {nama_siswa} tidak hadir hari ini.'],
                    ],
                    'permit' => [
                        ['message' => 'Pengajuan Izin Ananda {nama_siswa} berhasil tercatat di sistem'],
                    ],
                ]),
                'type' => 'json',
                'group_name' => 'notifications',
                'description' => 'Template pesan keterlambatan.',
                'is_public' => false,
            ],
            // Discipline Score Settings
            [
                'key' => 'discipline.scores.hadir',
                'value' => '5',
                'type' => 'integer',
                'group_name' => 'discipline',
                'description' => 'Poin untuk status hadir.',
                'is_public' => false,
            ],
            [
                'key' => 'discipline.scores.terlambat',
                'value' => '-2',
                'type' => 'integer',
                'group_name' => 'discipline',
                'description' => 'Poin untuk status terlambat.',
                'is_public' => false,
            ],
            [
                'key' => 'discipline.scores.izin',
                'value' => '0',
                'type' => 'integer',
                'group_name' => 'discipline',
                'description' => 'Poin untuk status izin.',
                'is_public' => false,
            ],
            [
                'key' => 'discipline.scores.sakit',
                'value' => '0',
                'type' => 'integer',
                'group_name' => 'discipline',
                'description' => 'Poin untuk status sakit.',
                'is_public' => false,
            ],
            [
                'key' => 'discipline.scores.tidak_hadir',
                'value' => '-5',
                'type' => 'integer',
                'group_name' => 'discipline',
                'description' => 'Poin untuk status alpa (tidak hadir).',
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
