<?php

namespace App\Filament\Imports;

use App\Models\Teacher;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class TeacherImporter extends Importer
{
    protected static ?string $model = Teacher::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Nama')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('nip')
                ->label('NIP')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:30']),

            ImportColumn::make('fingerprint_id')
                ->label('ID Sidik Jari')
                ->rules(['nullable', 'string', 'max:50']),

            ImportColumn::make('whatsapp_number')
                ->label('Nomor WhatsApp')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:20'])
                ->castStateUsing(function (string $state): string {
                    // Clean up phone number format
                    $phone = preg_replace('/[^0-9+]/', '', trim($state));

                    // Add +62 prefix if it starts with 0
                    if (str_starts_with($phone, '0')) {
                        $phone = '62'.substr($phone, 1);
                    }

                    // Add + prefix if not present
                    if (! str_starts_with($phone, '+')) {
                        $phone = '+'.$phone;
                    }

                    return $phone;
                }),
        ];
    }

    public function resolveRecord(): ?Teacher
    {
        // Check if teacher already exists by NIP
        return Teacher::firstOrNew([
            'nip' => $this->data['nip'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data guru selesai. '.number_format($import->successful_rows).' '.str('guru')->plural($import->successful_rows).' berhasil diimpor.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('guru')->plural($failedRowsCount).' gagal diimpor.';
        }

        return $body;
    }
}
