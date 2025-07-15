<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class Backup extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Cadangkan & Pulihkan';
    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static string $view = 'filament.pages.backup';

    protected static ?string $title = 'Cadangkan & Pulihkan';

    protected static ?int $navigationSort = 7;

    // Anda bisa menambahkan logika untuk backup/restore di sini
    public function backupDatabase()
    {
        // Logika untuk menjalankan perintah backup database
        Artisan::call('backup:run');
        Notification::make()->title('Backup berhasil!')->success()->send();
    }

    public function restoreDatabase($filePath)
    {
        // Logika untuk menjalankan perintah restore database
        Artisan::call('backup:restore', ['--file' => $filePath]);
        Notification::make()->title('Restore berhasil!')->success()->send();
    }

    public function getBackups()
    {
        // Mengambil daftar file backup dari tabel backups
        return \App\Models\Backup::orderBy('created_at', 'desc')->get();
    }
}