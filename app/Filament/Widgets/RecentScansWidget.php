<?php

namespace App\Filament\Widgets;

use App\Models\ScanLog;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentScansWidget extends BaseWidget
{
    protected static ?string $heading = 'Scan Terbaru';

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return StudentAttendance::query()->latest()->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('student.name')
                ->label('Nama'),
            Tables\Columns\TextColumn::make('student.class.name')
                ->label('Kelas'),
            Tables\Columns\TextColumn::make('date')
                ->label('Tanggal')
                ->sortable()
                ->date(),
            Tables\Columns\TextColumn::make('time_in')
                ->label('Jam Masuk')
                ->time('H:i'),
            Tables\Columns\TextColumn::make('time_out')
                ->label('Jam Keluar')
                ->time('H:i'),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge(),
            Tables\Columns\TextColumn::make('result')
                ->label('Hasil')
                ->badge(),
        ];
    }
}