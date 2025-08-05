<?php

namespace App\Filament\Resources\StudentLeaveRequestResource\Pages;

use App\Filament\Resources\StudentLeaveRequestResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditStudentLeaveRequest extends EditRecord
{
    protected static string $resource = StudentLeaveRequestResource::class;

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
            ->title('Izin siswa berhasil diperbarui')
            ->body('Data izin untuk siswa '.$this->record->student->name.' telah berhasil diperbarui.')
            ->duration(5000);
    }

    protected function afterSave(): void
    {
        // Send database notification to current user
        Notification::make()
            ->success()
            ->title('Izin Siswa Berhasil Diperbarui')
            ->body('Izin untuk siswa '.$this->record->student->name.' ('.ucfirst($this->record->type).') dari tanggal '.$this->record->start_date->format('d/m/Y').' sampai '.$this->record->end_date->format('d/m/Y').' telah berhasil diperbarui.')
            ->sendToDatabase(Auth::user());
    }
}
