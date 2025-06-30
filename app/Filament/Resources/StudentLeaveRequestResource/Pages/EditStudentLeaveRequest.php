<?php

namespace App\Filament\Resources\StudentLeaveRequestResource\Pages;

use App\Filament\Resources\StudentLeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
}
