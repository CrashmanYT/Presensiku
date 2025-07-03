<?php

namespace App\Filament\Imports;

use App\Models\Student;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class StudentImporter extends Importer
{
    protected static ?string $model = Student::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('nis')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('class_id')
                ->label('Kelas')
                ->requiredMapping()
                ->relationship(resolveUsing: 'name')
                ->rules(['required']),
            ImportColumn::make('gender')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('fingerprint_id')
                ->rules(['max:255']),
            ImportColumn::make('photo')
                ->rules(['max:255']),
            ImportColumn::make('parent_whatsapp')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?Student
    {
        return Student::firstOrNew([
            // Update existing records, matching them by `nis`
            'nis' => $this->data['nis'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your student import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
