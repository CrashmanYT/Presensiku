<?php

namespace App\Filament\Resources\TeacherLeaveRequestResource\Pages;

use App\Filament\Resources\TeacherLeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTeacherLeaveRequest extends ViewRecord
{
    protected static string $resource = TeacherLeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
