<?php

namespace App\Filament\Resources\AttendanceRuleResource\Pages;

use App\Filament\Resources\AttendanceRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceRule extends ViewRecord
{
    protected static string $resource = AttendanceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
