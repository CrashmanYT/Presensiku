<?php

namespace App\Filament\Resources\TeacherAttendanceResource\Pages;

use App\Filament\Resources\TeacherAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherAttendances extends ListRecords
{
    protected static string $resource = TeacherAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
