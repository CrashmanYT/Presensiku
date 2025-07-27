<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_path',
        'restored',
        'file_size',
        'description'
    ];

    protected $casts = [
        'restored' => 'boolean',
        'file_size' => 'integer'
    ];

    /**
     * Mendapatkan path lengkap ke file backup
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/backups/' . $this->file_path);
    }

    /**
     * Cek apakah file backup masih ada
     */
    public function getFileExistsAttribute(): bool
    {
        return File::exists($this->full_path);
    }

    /**
     * Mendapatkan ukuran file dalam format yang mudah dibaca
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_exists) {
            return 'N/A';
        }

        $bytes = File::size($this->full_path);
        return $this->formatBytes($bytes);
    }

    /**
     * Format bytes ke format yang mudah dibaca
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Scope untuk backup yang tersedia (file masih ada)
     */
    public function scopeAvailable($query)
    {
        return $query->whereRaw('1=1'); // Placeholder, karena kita perlu cek file existence di runtime
    }

    /**
     * Scope untuk backup yang sudah dipulihkan
     */
    public function scopeRestored($query)
    {
        return $query->where('restored', true);
    }

    /**
     * Scope untuk backup terbaru
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
