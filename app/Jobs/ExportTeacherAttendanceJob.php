<?php

namespace App\Jobs;

use App\Exports\TeacherAttendanceExport;
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

class ExportTeacherAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600; // 1 hour

    protected array $filters;
    protected User $user;
    protected string $fileName;

    public function __construct(array $filters, User $user)
    {
        $this->filters = $filters;
        $this->user = $user;
        $this->fileName = 'absensi-guru-' . now()->format('Ymd-His') . '-' . $this->user->id . '.xlsx';
        Log::info('ExportTeacherAttendanceJob: Job created for file ' . $this->fileName);
    }

    public function handle(): void
    {
        $context = [
            'filters' => $this->filters,
            'userId'    => $this->user->id ?? null,
        ];
        Log::info('ExportTeacherAttendanceJob: Starting handle process.', $context);

        try {
            $disk = 'public';
            $dir = 'exports/teacher-attendances/' . now()->format('Y') . '/' . now()->format('m');
            Storage::disk($disk)->makeDirectory($dir);
            Log::info('ExportTeacherAttendanceJob: Ensured public directory exists.', ['dir' => $dir]);

            $relativePath = $dir . '/' . $this->fileName;
            Log::info('ExportTeacherAttendanceJob: Storing file to (public disk)', ['path' => $relativePath]);

            $export = new TeacherAttendanceExport($this->filters);

            try {
                $storeOk = Excel::store($export, $relativePath, $disk);
                Log::info('ExportTeacherAttendanceJob: Excel::store result', ['ok' => $storeOk]);
            } catch (Throwable $excelEx) {
                Log::error('ExportTeacherAttendanceJob: Excel::store threw an exception: ' . $excelEx->getMessage());
                Log::error('ExportTeacherAttendanceJob: Excel exception trace: ' . $excelEx->getTraceAsString());

                Notification::make()
                    ->title('Ekspor Gagal')
                    ->body('Terjadi kesalahan saat membuat file ekspor.')
                    ->danger()
                    ->sendToDatabase($this->user);

                throw $excelEx;
            }

            $downloadUrl = Storage::disk($disk)->url($relativePath);
            Log::info('ExportTeacherAttendanceJob: File stored. Public URL: ' . $downloadUrl);

            Notification::make()
                ->title('Ekspor Selesai')
                ->body('File absensi guru siap diunduh.')
                ->success()
                ->actions([
                    NotificationAction::make('download')
                        ->label('Unduh File')
                        ->url($downloadUrl, shouldOpenInNewTab: true),
                ])
                ->sendToDatabase($this->user);

            $this->user->notify(new ExportReadyNotification($relativePath));
            Log::info('ExportTeacherAttendanceJob: Database notification sent.', ['path' => $relativePath]);
        } catch (Throwable $e) {
            Log::error('ExportTeacherAttendanceJob: Gagal mengekspor absensi guru: ' . $e->getMessage());
            Log::error('ExportTeacherAttendanceJob: Stack trace: ' . $e->getTraceAsString());

            try {
                Notification::make()
                    ->title('Ekspor Gagal')
                    ->body('Terjadi kesalahan saat membuat file ekspor: ' . $e->getMessage())
                    ->danger()
                    ->sendToDatabase($this->user);
            } catch (Throwable $notifyEx) {
                Log::error('ExportTeacherAttendanceJob: Gagal mengirim notifikasi kegagalan: ' . $notifyEx->getMessage());
            }
        }
    }
}
