# Artisan Commands

Dokumen ini merangkum perintah Artisan yang berkaitan dengan proses absensi harian dan notifikasi.

## Penjadwalan
Penjadwalan didefinisikan di `routes/console.php` dan dijalankan setiap menit. Masing-masing command memiliki logika waktu internal untuk menentukan kapan efektif bekerja.

```
// routes/console.php
Schedule::command(\App\Console\Commands\MarkStudentsAsAbsent::class)->everyMinute();
Schedule::command(\App\Console\Commands\SendAbsentNotifications::class)->everyMinute();
Schedule::command(\App\Console\Commands\SendClassLeaveSummaryToHomeroomTeacher::class)->everyMinute();
```

Konfigurasi waktu utama diambil dari pengaturan:
- `notifications.absent.notification_time` (default: `09:00`)

## attendance:mark-absent
Menandai siswa yang belum memiliki catatan absensi pada hari berjalan sebagai Tidak Hadir.

Signature:
```
php artisan attendance:mark-absent [--force]
```
Perilaku:
- Secara default, hanya akan berjalan mendekati waktu `notifications.absent.notification_time - 5 menit`.
- Dengan opsi `--force`, command akan mem-bypass pengecekan waktu dan langsung dieksekusi.
- Mengabaikan hari libur dan akhir pekan.
- Membuat record `student_attendances` dengan status `TIDAK_HADIR` untuk siswa tanpa catatan pada tanggal hari ini.

Output yang diharapkan (contoh dari test):
- "Today is a weekend. No action taken."
- "Today is a holiday. No action taken."
- "All students have an attendance record for today. Nothing to do."
- "Found X students without an attendance record. Marking them as absent..."
- "Finished marking absent students."

## attendance:send-absent-notifications
Mengirim notifikasi (via event) untuk siswa yang berstatus Tidak Hadir pada hari berjalan.

Signature:
```php
php artisan attendance:send-absent-notifications [--force]
```
Perilaku:
- Secara default, hanya akan berjalan mendekati waktu `notifications.absent.notification_time` (toleransi ~1 menit).
- Dengan opsi `--force`, command akan mem-bypass pengecekan waktu dan langsung dieksekusi.
- Mengambil `student_attendances` berstatus `TIDAK_HADIR` di tanggal hari ini.
- Melakukan dispatch event `StudentAttendanceUpdated` per record, untuk memicu alur notifikasi.

Output yang diharapkan (contoh dari test):
- "No absent students found for today. Nothing to do."
- "Found 1 absent students. Dispatching events..."
- "Finished processing absent students."

## attendance:send-leave-summary
Mengirim ringkasan harian siswa yang izin/sakit per kelas kepada wali kelas masing-masing via WhatsApp.

Signature:
```php
php artisan attendance:send-leave-summary
```
Perilaku:
- Berjalan otomatis mendekati waktu `attendance.defaults.time_in_end` (default `08:00`, toleransi ~1 menit).
- Mengelompokkan kehadiran hari ini dengan status `izin` atau `sakit` berdasarkan kelas.
- Membutuhkan relasi kelas memiliki wali kelas dengan `whatsapp_number` yang valid.
- Mengirim 1 pesan berisi daftar siswa izin/sakit untuk tiap kelas melalui `WhatsappService`.

Output yang diharapkan (contoh):
- "No students with leave today. Nothing to send."
- "Found X classes with students on leave. Processing each class..."
- "Finished processing daily leave summaries."

Catatan:
- Zona waktu mengacu ke `system.localization.timezone`.
- Format dan isi pesan dapat disesuaikan pada implementasi layanan WhatsApp.

## backup:database
Membuat backup database dan menyimpannya ke `storage/app/backups`, lalu mencatat metadata ke tabel `backups`.

Signature:
```php
php artisan backup:database [--cleanup] [--days=30]
```
Perilaku:
- Mencoba membuat backup menggunakan `mysqldump`. Jika tidak tersedia, fallback ke skema dump Laravel, lalu ke metode custom SQL.
- Menyimpan berkas dengan pola nama `backup_presensiku_YYYY-mm-dd_HH-ii-ss.sql`.
- Mencatat `file_path`, `file_size`, dan deskripsi ke tabel `backups`.
- Opsi `--cleanup` akan menghapus berkas backup lama yang melebihi `--days` (default 30 hari) serta menghapus catatannya di DB.

Catatan:
- Pastikan kredensial DB pada `.env` telah benar agar `mysqldump` dan koneksi database berhasil.
- Direktori tujuan akan otomatis dibuat jika belum ada: `storage/app/backups`.

## Tips Operasional
- Pastikan pengaturan waktu `notifications.absent.notification_time` sesuai zona waktu aplikasi (`system.localization.timezone`, default: `Asia/Jakarta`).
- Jalankan `php artisan schedule:work` atau konfigurasikan cron Laravel Scheduler di server produksi agar penjadwalan berjalan.
- Gunakan opsi `--force` saat pengujian manual di luar rentang waktu terjadwal.
