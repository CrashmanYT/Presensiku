# Settings & Konfigurasi Dinamis

Aplikasi menggunakan tabel `settings` untuk menyimpan konfigurasi operasional yang dapat diubah tanpa redeploy. Nilai-nilai awal disediakan oleh `Database\Seeders\SettingsSeeder`.

Gunakan helper `SettingsHelper::get(string $key, mixed $default = null)` untuk membaca nilai.

## Kelompok Pengaturan

### 1) attendance (defaults)
- `attendance.defaults.time_in_start` (string, default `07:00`) — Jam masuk mulai
- `attendance.defaults.time_in_end` (string, default `08:00`) — Jam masuk selesai
- `attendance.defaults.time_out_start` (string, default `15:00`) — Jam pulang mulai
- `attendance.defaults.time_out_end` (string, default `16:00`) — Jam pulang selesai

### 2) notifications
- `notifications.enabled` (boolean, default `1`) — Aktifkan sistem notifikasi
- `notifications.absent.notification_time` (string, default `09:00`) — Waktu kirim notifikasi alpa
- `notifications.channels` (json, default `["whatsapp"]`) — Kanal aktif
- `notifications.whatsapp.student_affairs_number` (string, default ``) — Nomor WA Kesiswaan/BK
- `notifications.whatsapp.administration_number` (string, default ``) — Nomor WA Tata Usaha (TU)
- `notifications.whatsapp.templates` (json) — Template pesan (late/absent/permit)
- `notifications.whatsapp.monthly_summary.enabled` (boolean, default `1`) — Aktifkan kirim ringkasan bulanan ke kesiswaan
- `notifications.whatsapp.monthly_summary.thresholds.min_total_late` (int, default `3`) — Minimal terlambat/bulan agar masuk ringkasan
- `notifications.whatsapp.monthly_summary.thresholds.min_total_absent` (int, default `2`) — Minimal alpa/bulan agar masuk ringkasan
- `notifications.whatsapp.monthly_summary.thresholds.min_score` (int, default `-5`) — Skor maksimum (<=) agar masuk ringkasan
- `notifications.whatsapp.monthly_summary.limit` (int, default `50`) — Batas jumlah siswa dalam ringkasan

### 3) system (localization)
- `system.localization.timezone` (string, default `Asia/Jakarta`) — Zona waktu aplikasi
- `system.localization.date_format` (string, default `d/m/Y`) — Format tanggal
- `system.localization.language` (string, default `id`) — Bahasa default
- `system.maintenance_mode` (boolean, default `0`) — Mode perawatan sistem

### 4) discipline (scores)
- `discipline.scores.hadir` (int, default `5`)
- `discipline.scores.terlambat` (int, default `-2`)
- `discipline.scores.izin` (int, default `0`)
- `discipline.scores.sakit` (int, default `0`)
- `discipline.scores.tidak_hadir` (int, default `-5`)

### 5) dashboard
- `dashboard.buttons.show_test` (boolean, default `1`) — Tampilkan tombol tes di dashboard absensi realtime.

## Cara Mengubah Settings
- Via UI (jika tersedia): gunakan halaman pengaturan.
- Via seeder/migrasi: ubah `SettingsSeeder` atau tambahkan migrasi yang memodifikasi data.
- Via Tinker (contoh):
```
php artisan tinker
>>> \App\Models\Setting::updateOrCreate(['key' => 'notifications.absent.notification_time'], [
...     'value' => '09:30', 'type' => 'string', 'group_name' => 'notifications', 'description' => 'Waktu notifikasi', 'is_public' => false
... ]);
```

## Keterkaitan dengan Fitur
- Command `attendance:mark-absent` dan `attendance:send-absent-notifications` menggunakan `notifications.absent.notification_time` untuk menentukan kapan dijalankan.
- Zona waktu `system.localization.timezone` memengaruhi seluruh kalkulasi waktu.
- Template WhatsApp digunakan oleh service/pekerjaan terkait notifikasi.
