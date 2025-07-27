# Fitur Backup Database - Presensiku

## Deskripsi
Fitur backup memungkinkan Anda untuk membuat cadangan database aplikasi Presensiku secara manual melalui antarmuka web atau otomatis melalui command line.

## Fitur Utama

### 1. Backup Manual via Web Interface
- Akses melalui menu **Pengaturan Sistem** → **Cadangkan & Pulihkan**
- Dashboard status sistem backup
- Pembuatan backup dengan satu klik
- Progress indicator saat backup berjalan
- Daftar semua file backup dengan informasi lengkap

### 2. Manajemen File Backup
- Download file backup
- Restore database dari backup
- Hapus file backup yang tidak diperlukan
- Informasi ukuran file dan status

### 3. Backup Otomatis via Command Line
```bash
# Membuat backup database
php artisan backup:database

# Membuat backup dan cleanup otomatis file lama
php artisan backup:database --cleanup

# Tentukan berapa hari backup disimpan (default: 30 hari)
php artisan backup:database --cleanup --days=7
```

### 4. Auto Cleanup
- Otomatis menghapus backup lebih dari 30 hari
- Konfigurasi periode penyimpanan yang fleksibel
- Logging aktivitas cleanup

## Struktur File

### Backend Files
- `app/Filament/Pages/Backup.php` - Controller utama untuk halaman backup
- `app/Models/Backup.php` - Model untuk data backup
- `app/Console/Commands/BackupDatabase.php` - Command untuk backup otomatis
- `database/migrations/*_create_backups_table.php` - Migration tabel backup
- `database/migrations/*_add_columns_to_backups_table.php` - Migration kolom tambahan

### Frontend Files  
- `resources/views/filament/pages/backup.blade.php` - Template halaman backup

### Storage
- File backup disimpan di: `storage/app/backups/`
- Format nama file: `backup_presensiku_YYYY-MM-DD_HH-MM-SS.sql`

## Database Schema

Tabel `backups`:
- `id` - Primary key
- `file_path` - Nama file backup  
- `restored` - Status apakah pernah di-restore
- `file_size` - Ukuran file dalam bytes
- `description` - Deskripsi backup
- `created_at` - Waktu backup dibuat
- `updated_at` - Waktu terakhir diupdate

## Persyaratan Sistem

1. **MySQL Tools** (Opsional):
   - `mysqldump` untuk membuat backup (akan dicoba terlebih dahulu)
   - `mysql` untuk restore database
   - Jika tidak tersedia, sistem akan otomatis fallback ke metode Laravel

2. **PHP Extensions**:
   - `exec()` function enabled (untuk mysqldump, jika tersedia)
   - File system permissions untuk direktori `storage/`
   - PDO MySQL extension (untuk fallback method)

3. **Storage**:
   - Ruang disk yang cukup untuk menyimpan file backup
   - Backup database biasanya berukuran 10-50% dari ukuran database asli

## Metode Backup & Restore

Sistem menggunakan multiple metode dengan prioritas fallback untuk backup dan restore:

### Backup Methods
1. **mysqldump** (Prioritas Pertama)
   - Menggunakan tool mysqldump jika tersedia
   - Menghasilkan file SQL yang paling kompatibel
   - Mencari di path: `/usr/local/bin/`, `/usr/bin/`, `/opt/homebrew/bin/`, atau system PATH

2. **Laravel Schema Dump** (Fallback Kedua)
   - Menggunakan Laravel's built-in database functions
   - Membuat file SQL dengan struktur dan data lengkap
   - Tidak memerlukan mysqldump

3. **Custom SQL Export** (Fallback Terakhir)
   - Metode custom menggunakan raw SQL queries
   - Dipecah dalam chunks untuk menghindari memory issues
   - Selalu berhasil selama koneksi database tersedia

### Restore Methods
1. **mysql command** (Prioritas Pertama)
   - Menggunakan tool mysql jika tersedia untuk restore cepat
   - Mencari di path: `/usr/local/bin/`, `/usr/bin/`, `/opt/homebrew/bin/`, atau system PATH

2. **PHP-based Restore** (Fallback Robust)
   - Membaca dan parse file SQL menggunakan PHP
   - Menjalankan statement satu per satu menggunakan Laravel DB
   - Progress tracking real-time
   - Error handling per statement
   - Automatic foreign key constraint management
   - Selalu berhasil selama file SQL valid dan koneksi database tersedia

## Cara Setup

1. **Jalankan Migration**:
```bash
php artisan migrate
```

2. **Pastikan Direktori Storage Ada**:
```bash
mkdir -p storage/app/backups
chmod 755 storage/app/backups
```

3. **Test Backup Manual**:
- Login ke aplikasi sebagai admin
- Buka menu **Pengaturan Sistem** → **Cadangkan & Pulihkan**
- Klik **Buat Backup Sekarang**

4. **Setup Cron Job untuk Backup Otomatis** (Opsional):
```bash
# Tambahkan ke crontab untuk backup harian pada jam 2 pagi
0 2 * * * cd /path/to/your/app && php artisan backup:database --cleanup
```

## Keamanan

1. **File Backup**:
   - Disimpan di direktori `storage/` yang tidak dapat diakses langsung via web
   - Berisi data sensitif, pastikan server aman

2. **Database Credentials**:
   - Menggunakan konfigurasi database dari `.env`
   - Credentials tidak disimpan dalam file backup

3. **Access Control**:
   - Hanya admin yang dapat mengakses fitur backup
   - Semua operasi dicatat dalam log aplikasi

## Troubleshooting

### Error "mysqldump command not found"
- Install MySQL client tools di server
- Pastikan PATH mencakup direktori MySQL bin

### Error "Permission denied"
- Periksa permission direktori `storage/app/backups/`
- Pastikan user web server memiliki akses write

### File Backup Kosong
- Periksa koneksi database
- Cek log error di `storage/logs/laravel.log`

### Restore Gagal
- Pastikan file backup tidak corrupt
- Cek kompatibilitas versi MySQL
- Backup struktur tabel terlebih dahulu

## Monitoring

- Semua aktivitas backup dicatat dalam log Laravel
- Status backup dapat dilihat di dashboard
- Notifikasi real-time untuk sukses/error

## Best Practices

1. **Backup Rutin**:
   - Setup cron job untuk backup otomatis harian
   - Simpan backup di storage eksternal secara berkala

2. **Testing Restore**:
   - Test restore secara berkala di environment development
   - Verifikasi integritas data setelah restore

3. **Storage Management**:
   - Monitor penggunaan disk space
   - Atur periode cleanup sesuai kebutuhan

4. **Monitoring**:
   - Setup alert untuk gagalnya backup otomatis
   - Review log backup secara berkala
