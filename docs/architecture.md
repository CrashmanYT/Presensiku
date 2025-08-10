# Arsitektur Aplikasi

Dokumen ini merangkum arsitektur Presensiku per progres saat ini.

## Stack Inti
- PHP 8.2+, Laravel 12
- Livewire 3, Filament 3 (UI admin, widgets, notifications)
- Queue: Laravel Queue (default: database/ sync untuk testing)
- Scheduler: Laravel Scheduler (didefinisikan di `routes/console.php`)
- Frontend build: Vite 6, TailwindCSS 3

Paket utama (composer):
- `filament/filament`, `filament/widgets`, `filament/notifications`
- `spatie/laravel-permission`
- `maatwebsite/excel` (import/export)
- `croustibat/filament-jobs-monitor` (monitor job)

## Komponen Kunci
- Commands (Artisan):
  - `attendance:mark-absent` — menandai siswa tanpa catatan sebagai Tidak Hadir.
  - `attendance:send-absent-notifications` — memicu notifikasi untuk siswa berstatus Tidak Hadir.
  - Penjadwalan: setiap menit via Laravel Scheduler; masing-masing command memeriksa waktunya sendiri.

- Events:
  - `StudentAttendanceUpdated` — digunakan sebagai trigger alur notifikasi ketika absensi berubah/ditandai.

- Settings (konfigurasi dinamis):
  - Disediakan melalui tabel `settings` (lihat [settings.md](./settings.md)) dan di-seed via `SettingsSeeder`.
  - Contoh: zona waktu (`system.localization.timezone`), waktu kirim notifikasi (`notifications.absent.notification_time`).

- Integrasi WhatsApp (Kirimi API):
  - Dirancang melalui service dan kontrak (lihat dokumen: [whatsapp-service-plan.md](./whatsapp-service-plan.md), [kirimi-whatsapp-api.md](./kirimi-whatsapp-api.md)).
  - Kredensial di `.env`: `KIRIMI_USER_CODE`, `KIRIMI_SECRET`, `KIRIMI_DEVICE_ID`.

## Data Model (Ringkas)
Lihat [data-model.md](./data-model.md) untuk ringkasan tabel dan hubungan, termasuk `students`, `student_attendances`, `holidays`, `settings`, dsb.

## Alur Harian (Ops)
1) Scheduler memanggil command setiap menit.
2) Sekitar `notifications.absent.notification_time - 5 menit`: `attendance:mark-absent` menandai siswa yang belum tercatat.
3) Sekitar `notifications.absent.notification_time`: `attendance:send-absent-notifications` memicu event untuk mengirim notifikasi.
4) Notifikasi dikirim melalui pipeline event/listener atau service WhatsApp (jika diaktifkan).

## Lingkungan & Build
- Development:
  - Jalankan `composer run dev` untuk server, queue listener, log pail, dan Vite secara paralel.
- Testing:
  - Menggunakan SQLite file; lihat [testing.md](./testing.md).

## Catatan Desain
- Command melakukan pengecekan waktu internal agar aman dijadwalkan setiap menit.
- Event-driven untuk pemisahan concern (penandaan hadir/tidak hadir vs pengiriman notifikasi).
- Settings sebagai kontrol operasional tanpa redeploy.

