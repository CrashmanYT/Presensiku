<?php

namespace App\Filament\Resources\StudentAttendanceResource\Pages;

use App\Filament\Resources\StudentAttendanceResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListStudentAttendances extends ListRecords
{
    protected static string $resource = StudentAttendanceResource::class;


    public function getTabs(): array
    {
        return [
            'daily'=> Tab::make('Harian')
                ->badge(fn() => $this->getModel()::count())
                ->label('Harian'),
            'weekly' => Tab::make('Mingguan')
                ->label('Mingguan'),
            'monthly'=> Tab::make('Bulanan')
                ->label('Bulanan'),

        ];
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        $activeTab = $this->activeTab ?? 'harian';
        if ($activeTab === 'mingguan') {
            // Query untuk rekap mingguan
            // Contoh agregasi:
            $query->selectRaw('student_id,
                COUNT(*) as total,
                COUNT(IF(status = "hadir", 1, NULL)) as total_hadir,
                COUNT(IF(status = "terlambat", 1, NULL)) as total_terlambat,
                COUNT(IF(status = "izin", 1, NULL)) as total_izin,
                COUNT(IF(status = "sakit", 1, NULL)) as total_sakit,
                COUNT(IF(status = "alpa", 1, NULL)) as total_alpa
            ')
                ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                ->groupBy('student_id');
        }
        if ($activeTab === 'bulanan') {
            // Query untuk rekap bulanan
            $query->selectRaw('student_id,
                COUNT(*) as total,
                COUNT(IF(status = "hadir", 1, NULL)) as total_hadir,
                COUNT(IF(status = "terlambat", 1, NULL)) as total_terlambat,
                COUNT(IF(status = "izin", 1, NULL)) as total_izin,
                COUNT(IF(status = "sakit", 1, NULL)) as total_sakit,
                COUNT(IF(status = "alpa", 1, NULL)) as total_alpa
            ')
                ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
                ->groupBy('student_id');
        }
        return $query;
    }

    protected function getTableColumns(): array
    {
        $tab = $this->activeTab ?? 'harian';

        if (in_array($tab, ['mingguan', 'bulanan'])) {
            return [
                TextColumn::make('student.name')->label('Nama'),
                TextColumn::make('total_hadir')->label('Hadir (H)'),
                TextColumn::make('total_terlambat')->label('Terlambat (T)'),
                TextColumn::make('total_sakit')->label('Sakit (S)'),
                TextColumn::make('total_izin')->label('Izin (I)'),
                TextColumn::make('total_alpa')->label('Alpa (A)'),
                TextColumn::make('persen')
                    ->label('%')
                    ->formatStateUsing(fn ($state, $record) => $record->total > 0
                        ? number_format(($record->total_hadir / $record->total) * 100, 2) . '%'
                        : '-'
                    ),
                TextColumn::make('action')
                    ->label('Action')
                    ->html()
                    ->formatStateUsing(fn () =>
                    '<a href="#" class="underline text-blue-500">Download</a>'
                    ),
            ];
        }

        // Default: tab harian
        return [
            TextColumn::make('student.name')->label('Nama'),
            TextColumn::make('status')->label('Status'),
            TextColumn::make('date')->label('Tanggal'),
        ];
    }
}
