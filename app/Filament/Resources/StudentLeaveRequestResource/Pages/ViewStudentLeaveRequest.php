<?php

namespace App\Filament\Resources\StudentLeaveRequestResource\Pages;

use App\Filament\Resources\StudentLeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentLeaveRequest extends ViewRecord
{
    protected static string $resource = StudentLeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
