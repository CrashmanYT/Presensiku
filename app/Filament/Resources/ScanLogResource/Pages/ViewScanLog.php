<?php

namespace App\Filament\Resources\ScanLogResource\Pages;

use App\Filament\Resources\ScanLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewScanLog extends ViewRecord
{
    protected static string $resource = ScanLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
