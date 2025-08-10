# Data Model (Ringkasan)

Ringkasan tabel inti berdasarkan migrasi per progres saat ini. Gunakan ini sebagai referensi cepat; detail kolom lengkap dapat dilihat pada berkas migrasi di `database/migrations` atau model Eloquent terkait.

## Tabel Inti
- cache (framework)
- jobs (framework)
- teachers — data guru
- classes — data kelas/rombel
- students — data siswa
- users — akun pengguna aplikasi
- devices — perangkat (mis. perangkat WhatsApp atau perangkat terkait lainnya)
- student_attendances — catatan absensi siswa per tanggal (status hadir/terlambat/izin/sakit/tidak_hadir)
- teacher_attendances — catatan absensi guru
- student_leave_requests — pengajuan izin siswa
- teacher_leave_requests — pengajuan izin guru
- holidays — hari libur (rentang tanggal)
- discipline_rankings — agregasi/skor kedisiplinan
- backups — metadata cadangan data
- settings — konfigurasi dinamis aplikasi
- attendance_rules — aturan absensi
- scan_logs — log pemindaian
- permission_* (spatie/laravel-permission) — manajemen role/permission
- exports — riwayat ekspor data
- imports — riwayat impor data
- failed_import_rows — baris yang gagal saat impor
- filament-jobs-monitor — monitor pekerjaan antrian
- personal_access_tokens — token API (sanctum)
- notifications — notifikasi (struktur terbaru sesuai migrasi)

## Relasi Utama (Ringkas)
- students 1..* student_attendances (student_attendances.student_id → students.id)
- holidays memengaruhi alur operasional absensi (penandaan dan notifikasi)
- users ↔ roles/permissions (via spatie/laravel-permission)

## Status Field Penting
- student_attendances.status — menggunakan enum `AttendanceStatusEnum` (HADIR, TERLAMBAT, IZIN, SAKIT, TIDAK_HADIR)
- settings.key/value/type/group_name — pengelompokan dan tipe untuk casting

## Catatan
- Lihat `Database/Seeders/SettingsSeeder` untuk nilai default pada `settings` (zona waktu, waktu notifikasi, template WA, dsb.).
- Pastikan indeks dan foreign key sesuai kebutuhan performa (lihat migrasi terkait).
