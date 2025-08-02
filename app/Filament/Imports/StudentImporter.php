<?php

namespace App\Filament\Imports;

use App\Models\Classes;
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
                ->label('Nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('nis')
                ->label('NIS')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:20']),

            ImportColumn::make('class_name')
                ->label('Nama Kelas')
                ->requiredMapping()
                ->rules(['required', 'string']),

            ImportColumn::make('gender')
                ->label('Jenis Kelamin (L/P)')
                ->requiredMapping()
                ->rules(['required', 'in:L,P,LAKI-LAKI,PEREMPUAN,MALE,FEMALE'])
                ->castStateUsing(function (string $state): ?string {
                    return match (strtoupper(trim($state))) {
                        'L', 'LAKI-LAKI', 'MALE' => 'L',
                        'P', 'PEREMPUAN', 'FEMALE' => 'P',
                        default => $state,
                    };
                }),

            ImportColumn::make('fingerprint_id')
                ->label('ID Sidik Jari')
                ->rules(['nullable', 'string', 'max:50']),

            ImportColumn::make('parent_whatsapp')
                ->label('WhatsApp Orang Tua')
                ->rules(['nullable', 'string', 'max:20']),
        ];
    }

    public function resolveRecord(): ?Student
    {
        // Check if student already exists by NIS
        return Student::firstOrNew([
            'nis' => $this->data['nis'],
        ]);
    }

    protected function beforeSave(): void
    {
        // Find class by name and set class_id
        if (! empty($this->data['class_name'])) {
            $class = Classes::where('name', trim($this->data['class_name']))->first();
            if ($class) {
                $this->data['class_id'] = $class->id;
            } else {
                throw new \Exception("Kelas '{$this->data['class_name']}' tidak ditemukan.");
            }
        }

        // Remove class_name from data as it's not a database field
        unset($this->data['class_name']);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data siswa selesai. '.number_format($import->successful_rows).' '.str('siswa')->plural($import->successful_rows).' berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('siswa')->plural($failedRowsCount).' gagal diimpor.';
        }

        return $body;
    }
}
