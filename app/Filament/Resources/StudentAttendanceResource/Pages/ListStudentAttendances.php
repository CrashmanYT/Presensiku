<?php

namespace App\Filament\Resources\StudentAttendanceResource\Pages;

use App\Filament\Resources\StudentAttendanceResource;
use App\Enums\AttendanceStatusEnum;
use App\Jobs\ExportStudentAttendanceJob;
use App\Models\Classes;
use Filament\Actions\Action;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class ListStudentAttendances extends ListRecords
{
    protected static string $resource = StudentAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export Excel')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    DatePicker::make('from_date')
                        ->label('Dari Tanggal')
                        ->required(),
                    DatePicker::make('to_date')
                        ->label('Sampai Tanggal')
                        ->required(),
                    Select::make('class_ids')
                        ->label('Kelas')
                        ->multiple()
                        ->options(Classes::pluck('name', 'id'))
                        ->placeholder('Semua Kelas'),
                    Select::make('status')
                        ->label('Status')
                        ->multiple()
                        ->options(collect(AttendanceStatusEnum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                        ->placeholder('Semua Status'),
                ])
                ->action(function (array $data) {
                    $filters = [
                        'from_date' => $data['from_date'],
                        'to_date' => $data['to_date'],
                        'class_ids' => $data['class_ids'],
                        'status' => $data['status'],
                    ];

                    ExportStudentAttendanceJob::dispatch($filters, auth()->user());

                    Notification::make()
                        ->title('Ekspor Dimulai')
                        ->body('Proses ekspor absensi siswa sedang berjalan di latar belakang. Anda akan diberi tahu jika sudah selesai.')
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        $activeTab = $this->activeTab ?? 'harian';
        if ($activeTab === 'mingguan') {
            // Query untuk rekap mingguan
            // Contoh agregasi:
            $query->selectRaw('student_id,
                COUNT(*) as total,
                COUNT(IF(status = "hadir", 1, NULL)) as total_hadir,
                COUNT(IF(status = "terlambat", 1, NULL)) as total_terlambat,
                COUNT(IF(status = "izin", 1, NULL)) as total_izin,
                COUNT(IF(status = "sakit", 1, NULL)) as total_sakit,
                COUNT(IF(status = "alpa", 1, NULL)) as total_alpa
            ')
                ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                ->groupBy('student_id');
        }
        if ($activeTab === 'bulanan') {
            // Query untuk rekap bulanan
            $query->selectRaw('student_id,
                COUNT(*) as total,
                COUNT(IF(status = "hadir", 1, NULL)) as total_hadir,
                COUNT(IF(status = "terlambat", 1, NULL)) as total_terlambat,
                COUNT(IF(status = "izin", 1, NULL)) as total_izin,
                COUNT(IF(status = "sakit", 1, NULL)) as total_sakit,
                COUNT(IF(status = "alpa", 1, NULL)) as total_alpa
            ')
                ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
                ->groupBy('student_id');
        }

        return $query;
    }

    protected function getTableColumns(): array
    {
        $tab = $this->activeTab ?? 'harian';

        if (in_array($tab, ['mingguan', 'bulanan'])) {
            return [
                TextColumn::make('student.name')->label('Nama'),
                TextColumn::make('total_hadir')->label('Hadir (H)'),
                TextColumn::make('total_terlambat')->label('Terlambat (T)'),
                TextColumn::make('total_sakit')->label('Sakit (S)'),
                TextColumn::make('total_izin')->label('Izin (I)'),
                TextColumn::make('total_alpa')->label('Alpa (A)'),
                TextColumn::make('persen')
                    ->label('%')
                    ->formatStateUsing(fn ($state, $record) => $record->total > 0
                        ? number_format(($record->total_hadir / $record->total) * 100, 2).'%'
                        : '-'
                    ),
                TextColumn::make('action')
                    ->label('Action')
                    ->html()
                    ->formatStateUsing(fn () => '<a href="#" class="underline text-blue-500">Download</a>'
                    ),
            ];
        }

        // Default: tab harian
        return [
            TextColumn::make('student.name')->label('Nama'),
            TextColumn::make('status')->label('Status'),
            TextColumn::make('date')->label('Tanggal'),
        ];
    }
}
