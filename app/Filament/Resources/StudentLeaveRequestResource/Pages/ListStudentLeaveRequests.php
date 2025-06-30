<?php

namespace App\Filament\Resources\StudentLeaveRequestResource\Pages;

use App\Filament\Resources\StudentLeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentLeaveRequests extends ListRecords
{
    protected static string $resource = StudentLeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
