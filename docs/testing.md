# Testing & CI

Panduan menjalankan test secara lokal dan konfigurasi CI saat ini.

## Menjalankan Test Lokal
- Pastikan dependency sudah terpasang:
```
composer install
```
- Jalankan migrasi untuk database testing jika menggunakan SQLite file:
```
mkdir -p database
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan migrate
```
- Jalankan test:
```
./vendor/bin/phpunit
```

Catatan:
- phpunit.xml telah mengatur variabel ENV default untuk testing:
  - DB_CONNECTION=sqlite
  - DB_DATABASE=database/database.sqlite
  - DB_FOREIGN_KEYS=true
  - QUEUE_CONNECTION=sync, SESSION_DRIVER=array, CACHE_STORE=array
- Test menggunakan trait `RefreshDatabase` sehingga skema akan direset per test suite.

## Konfigurasi CI (GitHub Actions)
- Workflow: `.github/workflows/tests.yml`
- Ringkasan langkah:
  1. Setup PHP 8.4 & Node 22
  2. Install composer & npm dependencies
  3. Copy `.env.example` ke `.env` dan generate APP_KEY
  4. Set ENV testing spesifik:
     - DB_CONNECTION=sqlite
     - DB_DATABASE=${{ github.workspace }}/database/database.sqlite
     - DB_FOREIGN_KEYS=false (override dari phpunit.xml)
     - CACHE_DRIVER=array, SESSION_DRIVER=array, QUEUE_CONNECTION=sync
  5. Siapkan file database SQLite: `database/database.sqlite`
  6. Clear cache (config, cache, route, view)
  7. Verifikasi koneksi DB via `php artisan tinker` (opsional debug)
  8. Jalankan migrasi
  9. Build assets (vite)
  10. Jalankan test via `./vendor/bin/phpunit`

Catatan:
- Saat ini CI menggunakan SQLite file (bukan `:memory:`). Dokumentasi ini mencerminkan konfigurasi terkini di workflow dan phpunit.xml.
- Jika ingin beralih ke in-memory SQLite, Anda perlu mengubah phpunit.xml dan workflow untuk mengatur `DB_DATABASE=:memory:` dan menyesuaikan langkah migrasi.
