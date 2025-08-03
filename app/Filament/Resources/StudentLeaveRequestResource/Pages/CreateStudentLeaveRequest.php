<?php

namespace App\Filament\Resources\StudentLeaveRequestResource\Pages;

use App\Filament\Resources\StudentLeaveRequestResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStudentLeaveRequest extends CreateRecord
{
    protected static string $resource = StudentLeaveRequestResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Izin siswa berhasil ditambahkan')
            ->body('Data izin untuk siswa ' . $this->record->student->name . ' telah berhasil disimpan.')
            ->duration(5000);
    }

    protected function afterCreate(): void
    {
        // Send database notification to current user
        Notification::make()
            ->success()
            ->title('Izin Siswa Berhasil Ditambahkan')
            ->body('Izin untuk siswa ' . $this->record->student->name . ' (' . ucfirst($this->record->type) . ') dari tanggal ' . $this->record->start_date->format('d/m/Y') . ' sampai ' . $this->record->end_date->format('d/m/Y') . ' telah berhasil disimpan.')
            ->sendToDatabase(Auth::user());
    }
}
