<?php

namespace App\Filament\Resources\TeacherLeaveRequestResource\Pages;

use App\Filament\Resources\TeacherLeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
}
