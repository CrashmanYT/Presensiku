<?php

namespace App\Filament\Resources\AttendanceRuleResource\Pages;

use App\Filament\Resources\AttendanceRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceRule extends EditRecord
{
    protected static string $resource = AttendanceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
