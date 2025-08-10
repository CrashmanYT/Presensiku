# Setup & Instalasi

Dokumen ini menjelaskan cara menyiapkan lingkungan pengembangan Presensiku.

## Prasyarat
- PHP 8.2+
- Composer 2.x
- Node.js 22.x dan npm
- SQLite (untuk testing) atau MySQL (untuk aplikasi lokal/produksi)

## Langkah Instalasi
1) Clone repo dan pasang dependency
```
composer install
npm install
```

2) Salin file environment dan generate key
```
cp .env.example .env
php artisan key:generate
```

3) Konfigurasi database aplikasi
- Default `.env.example` menggunakan MySQL dengan kredensial lokal:
  - DB_CONNECTION=mysql
  - DB_HOST=127.0.0.1
  - DB_PORT=8889 (ubah sesuai lingkungan Anda)
  - DB_DATABASE=presensiku
  - DB_USERNAME=root
  - DB_PASSWORD=root
- Sesuaikan nilai di atas dengan environment lokal Anda.

4) Migrasi dan seeding
```
php artisan migrate --seed
```
Seeder akan mengisi pengaturan awal (Settings) termasuk jam default, zona waktu, dan pengaturan notifikasi.

5) Menjalankan aplikasi untuk pengembangan
- Server saja:
```
php artisan serve
npm run dev
```
- Atau gunakan skrip dev terintegrasi (server + queue + logs + vite) melalui Composer:
```
composer run dev
```

6) Storage link (jika diperlukan)
```
php artisan storage:link
```

## Kredensial WhatsApp (Kirimi)
Isi variabel berikut di `.env` jika menggunakan integrasi WhatsApp Kirimi:
```
KIRIMI_USER_CODE=
KIRIMI_SECRET=
KIRIMI_DEVICE_ID=
```
Lihat dokumen [Kirimi WhatsApp API](./kirimi-whatsapp-api.md) untuk detail endpoint dan payload.
