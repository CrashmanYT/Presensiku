<?php

namespace App\Filament\Imports;

use App\Models\Classes;
use App\Models\Teacher;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ClassesImporter extends Importer
{
    protected static ?string $model = Classes::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Nama Kelas')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
                
            ImportColumn::make('level')
                ->label('Level/Tingkat')
                ->requiredMapping()
                ->rules(['required', 'integer', 'min:1', 'max:12'])
                ->castStateUsing(function (string $state): int {
                    return (int) trim($state);
                }),
                
            ImportColumn::make('major')
                ->label('Jurusan')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
                
            ImportColumn::make('homeroom_teacher_name')
                ->label('Nama Wali Kelas')
                ->rules(['nullable', 'string']),
        ];
    }

    public function resolveRecord(): ?Classes
    {
        // Check if class already exists by name
        return Classes::firstOrNew([
            'name' => $this->data['name'],
        ]);
    }

    protected function beforeSave(): void
    {
        // Find homeroom teacher by name and set homeroom_teacher_id
        if (!empty($this->data['homeroom_teacher_name'])) {
            $teacher = Teacher::where('name', trim($this->data['homeroom_teacher_name']))->first();
            if ($teacher) {
                $this->data['homeroom_teacher_id'] = $teacher->id;
            } else {
                // If teacher not found, set to null
                $this->data['homeroom_teacher_id'] = null;
            }
        } else {
            $this->data['homeroom_teacher_id'] = null;
        }
        
        // Remove homeroom_teacher_name from data as it's not a database field
        unset($this->data['homeroom_teacher_name']);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data kelas selesai. ' . number_format($import->successful_rows) . ' ' . str('kelas')->plural($import->successful_rows) . ' berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('kelas')->plural($failedRowsCount) . ' gagal diimpor.';
        }

        return $body;
    }
}
