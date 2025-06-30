<?php

namespace App\Filament\Resources\TeacherLeaveRequestResource\Pages;

use App\Filament\Resources\TeacherLeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherLeaveRequests extends ListRecords
{
    protected static string $resource = TeacherLeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
