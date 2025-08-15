<?php

namespace App\Filament\Pages;

use App\Support\LogReader;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Filament\Actions;

class Logs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static ?string $title = 'Log Aplikasi';

    protected static ?string $navigationLabel = 'Log Aplikasi';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.logs';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('logs.view') ?? false;
    }

    // State
    public ?string $selectedFile = null;

    /** @var array<int, string> */
    public array $levels = [];

    public ?string $keyword = null;

    /** @var array<int, array{basename:string,path:string,size:int,mtime:int}> */
    public array $files = [];

    /** @var array<int, array<string, mixed>> */
    public array $entries = [];

    public int $limit = 200;

    public bool $live = false;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('logs.view'), 403);
        $this->files = LogReader::listFiles();
        if (!empty($this->files)) {
            $this->selectedFile = $this->files[0]['path'] ?? null;
        }
        $this->levels = [];
        $this->keyword = null;
        $this->refreshEntries();
    }

    protected function getHeaderActions(): array
    {
        $file = $this->resolveSelectedFile();
        $downloadUrl = $file ? route('admin.logs.download', ['name' => $file['basename']]) : null;
        $canDownload = Auth::user()?->can('logs.download') ?? false;
        $canManage = Auth::user()?->can('logs.manage') ?? false;
        return [
            Actions\Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible($canDownload && $downloadUrl !== null)
                ->url($downloadUrl)
                ->openUrlInNewTab(),
            Actions\Action::make('delete')
                ->label('Hapus')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible($canManage)
                ->requiresConfirmation()
                ->modalHeading('Hapus file log ini?')
                ->modalDescription('Tindakan tidak dapat dibatalkan.')
                ->action('deleteSelected'),
        ];
    }

    public function updatedSelectedFile(): void
    {
        $this->refreshEntries();
    }

    public function updatedLevels(): void
    {
        $this->refreshEntries();
    }

    public function updatedKeyword(): void
    {
        $this->refreshEntries();
    }

    public function updatedLimit(): void
    {
        $this->refreshEntries();
    }

    public function updatedLive(): void
    {
        // Refresh immediately when toggled to give instant feedback
        $this->refreshEntries();
    }

    public function refreshEntries(): void
    {
        $path = (string) $this->selectedFile;
        if ($path === '' || !is_file($path)) {
            $this->entries = [];
            return;
        }
        $raw = LogReader::tail($path, 524288); // 512KB
        $parsed = LogReader::parseEntries($raw);
        $filtered = LogReader::filter($parsed, $this->levels ?: null, $this->keyword ?: null);
        // Newest at bottom in tail, but for UX show newest first
        $filtered = array_reverse($filtered);
        if ($this->limit > 0) {
            $filtered = array_slice($filtered, 0, $this->limit);
        }
        $this->entries = $filtered;
    }

    protected function ensureCan(string $permission): void
    {
        $user = Auth::user();
        if (!$user || !$user->can($permission)) {
            abort(403);
        }
    }

    /**
     * @return array{basename:string,path:string,size:int,mtime:int}|null
     */
    protected function resolveSelectedFile(): ?array
    {
        $path = (string) $this->selectedFile;
        foreach ($this->files as $f) {
            if (($f['path'] ?? null) === $path) return $f;
        }
        return null;
    }

    public function downloadSelected(): StreamedResponse
    {
        $this->ensureCan('logs.download');
        $file = $this->resolveSelectedFile();
        if (!$file || !is_file($file['path']) || !is_readable($file['path'])) {
            Notification::make()->title('File log tidak ditemukan atau tidak dapat dibaca.')->danger()->send();
            // Fallback: return empty 204 to complete request gracefully
            return response()->streamDownload(function () {
                echo '';
            }, 'empty.txt', ['Content-Type' => 'text/plain']);
        }
        $path = $file['path'];
        $name = $file['basename'];
        return response()->streamDownload(function () use ($path) {
            $fh = fopen($path, 'rb');
            if ($fh) {
                while (!feof($fh)) {
                    echo fread($fh, 8192);
                }
                fclose($fh);
            }
        }, $name, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function deleteSelected(): void
    {
        $this->ensureCan('logs.manage');
        $file = $this->resolveSelectedFile();
        if (!$file) {
            Notification::make()->title('Tidak ada file yang dipilih.')->danger()->send();
            return;
        }
        $path = $file['path'];
        if (!is_file($path)) {
            Notification::make()->title('File tidak ditemukan.')->danger()->send();
            return;
        }
        if (!@unlink($path)) {
            Notification::make()->title('Gagal menghapus file log.')->danger()->send();
            return;
        }
        Notification::make()->title('File log dihapus.')->success()->send();
        // Refresh list & selection
        $this->files = LogReader::listFiles();
        $this->selectedFile = $this->files[0]['path'] ?? null;
        $this->refreshEntries();
    }

    /**
     * Helper to format bytes.
     */
    public function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, $i === 0 ? 0 : 2) . ' ' . $units[$i];
    }
}
