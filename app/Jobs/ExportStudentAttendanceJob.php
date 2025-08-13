<?php

namespace App\Jobs;

use App\Exports\StudentAttendanceExport;
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
use Carbon\CarbonImmutable;

class ExportStudentAttendanceJob implements ShouldQueue
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
        $now = CarbonImmutable::now();
        $this->fileName = 'absensi-siswa-' . $now->format('Ymd-His') . '-' . $this->user->id . '.xlsx';
        Log::info('ExportStudentAttendanceJob: Job created for file ' . $this->fileName);
    }

    public function handle(): void
    {
        $context = [
            'filters' => $this->filters,
            'userId'    => $this->user->id ?? null,
        ];
        Log::info('ExportStudentAttendanceJob: Starting handle process.', $context);

        try {
            $now = CarbonImmutable::now();
            $disk = 'public';
            $dir = 'exports/student-attendances/' . $now->format('Y') . '/' . $now->format('m');
            Storage::disk($disk)->makeDirectory($dir);
            Log::info('ExportStudentAttendanceJob: Ensured public directory exists.', ['dir' => $dir]);

            $relativePath = $dir . '/' . $this->fileName;
            Log::info('ExportStudentAttendanceJob: Storing file to (public disk)', ['path' => $relativePath]);

            $export = new StudentAttendanceExport($this->filters);

            try {
                $storeOk = Excel::store($export, $relativePath, $disk);
                Log::info('ExportStudentAttendanceJob: Excel::store result', ['ok' => $storeOk]);
            } catch (Throwable $excelEx) {
                Log::error('ExportStudentAttendanceJob: Excel::store threw an exception: ' . $excelEx->getMessage());
                Log::error('ExportStudentAttendanceJob: Excel exception trace: ' . $excelEx->getTraceAsString());

                Notification::make()
                    ->title('Ekspor Gagal')
                    ->body('Terjadi kesalahan saat membuat file ekspor.')
                    ->danger()
                    ->sendToDatabase($this->user);

                throw $excelEx;
            }

            $downloadUrl = Storage::disk($disk)->url($relativePath);
            Log::info('ExportStudentAttendanceJob: File stored. Public URL: ' . $downloadUrl);

            Notification::make()
                ->title('Ekspor Selesai')
                ->body('File absensi siswa siap diunduh.')
                ->success()
                ->actions([
                    NotificationAction::make('download')
                        ->label('Unduh File')
                        ->url($downloadUrl, shouldOpenInNewTab: true),
                ])
                ->sendToDatabase($this->user);

            $this->user->notify(new ExportReadyNotification($relativePath));
            Log::info('ExportStudentAttendanceJob: Database notification sent.', ['path' => $relativePath]);
        } catch (Throwable $e) {
            Log::error('ExportStudentAttendanceJob: Gagal mengekspor absensi siswa: ' . $e->getMessage());
            Log::error('ExportStudentAttendanceJob: Stack trace: ' . $e->getTraceAsString());

            try {
                Notification::make()
                    ->title('Ekspor Gagal')
                    ->body('Terjadi kesalahan saat membuat file ekspor: ' . $e->getMessage())
                    ->danger()
                    ->sendToDatabase($this->user);
            } catch (Throwable $notifyEx) {
                Log::error('ExportStudentAttendanceJob: Gagal mengirim notifikasi kegagalan: ' . $notifyEx->getMessage());
            }
        }
    }
}
