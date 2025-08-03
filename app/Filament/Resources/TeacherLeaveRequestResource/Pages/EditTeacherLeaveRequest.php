<?php

namespace App\Filament\Resources\TeacherLeaveRequestResource\Pages;

use App\Filament\Resources\TeacherLeaveRequestResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTeacherLeaveRequest extends EditRecord
{
    protected static string $resource = TeacherLeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Izin guru berhasil diperbarui')
            ->body('Data izin untuk guru ' . $this->record->teacher->name . ' telah berhasil diperbarui.')
            ->duration(5000);
    }

    protected function afterSave(): void
    {
        // Send database notification to current user
        Notification::make()
            ->success()
            ->title('Izin Guru Berhasil Diperbarui')
            ->body('Izin untuk guru ' . $this->record->teacher->name . ' (' . ucfirst($this->record->type) . ') dari tanggal ' . $this->record->start_date->format('d/m/Y') . ' sampai ' . $this->record->end_date->format('d/m/Y') . ' telah berhasil diperbarui.')
            ->sendToDatabase(Auth::user());
    }
}
