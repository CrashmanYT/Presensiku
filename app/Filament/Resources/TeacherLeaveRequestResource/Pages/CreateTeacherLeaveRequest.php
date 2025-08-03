<?php

namespace App\Filament\Resources\TeacherLeaveRequestResource\Pages;

use App\Filament\Resources\TeacherLeaveRequestResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTeacherLeaveRequest extends CreateRecord
{
    protected static string $resource = TeacherLeaveRequestResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Izin guru berhasil ditambahkan')
            ->body('Data izin untuk guru ' . $this->record->teacher->name . ' telah berhasil disimpan.')
            ->duration(5000);
    }

    protected function afterCreate(): void
    {
        // Send database notification to current user
        Notification::make()
            ->success()
            ->title('Izin Guru Berhasil Ditambahkan')
            ->body('Izin untuk guru ' . $this->record->teacher->name . ' (' . ucfirst($this->record->type) . ') dari tanggal ' . $this->record->start_date->format('d/m/Y') . ' sampai ' . $this->record->end_date->format('d/m/Y') . ' telah berhasil disimpan.')
            ->sendToDatabase(Auth::user());
    }
}
