<?php

namespace App\Jobs;

use App\Exports\RekapitulasiAbsensiExport;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportRekapitulasiAbsensiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600; // 1 hour

    protected string $activeTab;
    protected string $startDate;
    protected string $endDate;
    protected ?int $classId;
    protected User $user;
    protected string $fileName;

    public function __construct(string $activeTab, string $startDate, string $endDate, ?int $classId, User $user)
    {
        $this->activeTab = $activeTab;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->classId = $classId;
        $this->user = $user;

        // Build base filename with user id as required
        $this->fileName = 'rekapitulasi-absensi-' . now()->format('Ymd-His') . '-' . $this->user->id . '.xlsx';

        Log::info('ExportRekapitulasiAbsensiJob: Job created for file ' . $this->fileName);
    }

    public function handle(): void
    {
        // Context log for easier tracking
        $context = [
            'activeTab' => $this->activeTab,
            'startDate' => $this->startDate,
            'endDate'   => $this->endDate,
            'classId'   => $this->classId,
            'userId'    => $this->user->id ?? null,
        ];
        Log::info('ExportRekapitulasiAbsensiJob: Starting handle process.', $context);

        try {
            // Using public disk as requested for straightforward access
            $disk = 'public';
            
            // Directory path within the public disk (no "public/" prefix needed here)
            $dir = 'exports/rekapitulasi-absensi/' . now()->format('Y') . '/' . now()->format('m');
            
            // Ensure directory exists on the public disk
            Storage::disk($disk)->makeDirectory($dir);
            Log::info('ExportRekapitulasiAbsensiJob: Ensured public directory exists.', ['dir' => $dir]);

            // Relative path for storage and URL generation
            $relativePath = $dir . '/' . $this->fileName;

            Log::info('ExportRekapitulasiAbsensiJob: Storing file to (public disk)', ['path' => $relativePath]);

            $export = new RekapitulasiAbsensiExport($this->activeTab, $this->startDate, $this->endDate, $this->classId);
            
            try {
                // Explicitly store on the public disk
                $storeOk = Excel::store($export, $relativePath, $disk);
                Log::info('ExportRekapitulasiAbsensiJob: Excel::store result', ['ok' => $storeOk]);
            } catch (Throwable $excelEx) {
                Log::error('ExportRekapitulasiAbsensiJob: Excel::store threw an exception: ' . $excelEx->getMessage());
                Log::error('ExportRekapitulasiAbsensiJob: Excel exception trace: ' . $excelEx->getTraceAsString());

                Notification::make()
                    ->title('Ekspor Gagal')
                    ->body('Terjadi kesalahan saat membuat file ekspor.')
                    ->danger()
                    ->sendToDatabase($this->user);

                throw $excelEx;
            }

            // Build the public URL for the notification link
            $downloadUrl = Storage::disk($disk)->url($relativePath);
            Log::info('ExportRekapitulasiAbsensiJob: File stored. Public URL: ' . $downloadUrl);

            // Filament notification with direct download URL
            Notification::make()
                ->title('Ekspor Selesai')
                ->body('File rekapitulasi absensi siap diunduh.')
                ->success()
                ->actions([
                    NotificationAction::make('download')
                        ->label('Unduh File')
                        ->url($downloadUrl, shouldOpenInNewTab: true),
                ])
                ->sendToDatabase($this->user);

            // Laravel notification needs the relative path to build the same URL
            $this->user->notify(new ExportReadyNotification($relativePath));
            Log::info('ExportRekapitulasiAbsensiJob: Database notification sent.', ['path' => $relativePath]);
        } catch (Throwable $e) {
            Log::error('ExportRekapitulasiAbsensiJob: Gagal mengekspor rekapitulasi absensi: ' . $e->getMessage());
            Log::error('ExportRekapitulasiAbsensiJob: Stack trace: ' . $e->getTraceAsString());

            try {
                Notification::make()
                    ->title('Ekspor Gagal')
                    ->body('Terjadi kesalahan saat membuat file ekspor: ' . $e->getMessage())
                    ->danger()
                    ->sendToDatabase($this->user);
            } catch (Throwable $notifyEx) {
                Log::error('ExportRekapitulasiAbsensiJob: Gagal mengirim notifikasi kegagalan: ' . $notifyEx->getMessage());
            }
        }
    }
}
