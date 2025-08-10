# Presensiku

Sistem manajemen presensi dengan penjadwalan otomatis, notifikasi ketidakhadiran, dan integrasi antarmuka admin berbasis Laravel + Livewire + Filament.

## Dokumentasi
Seluruh dokumentasi dipusatkan di folder [`docs/`](./docs). Mulai dari:
- [Setup & Instalasi](./docs/setup.md)
- [Testing & CI](./docs/testing.md)
- [Artisan Commands](./docs/commands.md)
- [Arsitektur Aplikasi](./docs/architecture.md)
- [Settings & Konfigurasi](./docs/settings.md)
- [Data Model](./docs/data-model.md)

## Ringkasan Fitur
- Penandaan otomatis siswa yang belum memiliki catatan sebagai Tidak Hadir (`attendance:mark-absent`).
- Pengiriman notifikasi untuk siswa berstatus Tidak Hadir (`attendance:send-absent-notifications`).
- Penjadwalan via Laravel Scheduler (tiap menit, dengan logika waktu di dalam command).
- UI admin menggunakan Filament; dukungan Livewire 3.
- Integrasi WhatsApp (Kirimi) dirancang melalui service & kontrak (lihat dokumen di `docs/`).

## Pengembangan Cepat
- Pasang dependency: `composer install && npm install`
- Setup env & key: `cp .env.example .env && php artisan key:generate`
- Migrasi & seed: `php artisan migrate --seed`
- Jalankan: `composer run dev` (server, queue, logs, vite)

Detail lengkap ada di folder [docs](./docs).

