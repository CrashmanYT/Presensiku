<?php

namespace App\Filament\Resources\TeacherAttendanceResource\Pages;

use App\Enums\AttendanceStatusEnum;
use App\Filament\Resources\TeacherAttendanceResource;
use App\Jobs\ExportTeacherAttendanceJob;
use App\Models\Teacher;
use Filament\Actions\Action;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTeacherAttendances extends ListRecords
{
    protected static string $resource = TeacherAttendanceResource::class;

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
                    Select::make('teacher_ids')
                        ->label('Guru')
                        ->multiple()
                        ->options(Teacher::pluck('name', 'id'))
                        ->placeholder('Semua Guru')
                        ->searchable(),
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
                        'teacher_ids' => $data['teacher_ids'],
                        'status' => $data['status'],
                    ];

                    ExportTeacherAttendanceJob::dispatch($filters, auth()->user());

                    Notification::make()
                        ->title('Ekspor Dimulai')
                        ->body('Proses ekspor absensi guru sedang berjalan di latar belakang. Anda akan diberi tahu jika sudah selesai.')
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
