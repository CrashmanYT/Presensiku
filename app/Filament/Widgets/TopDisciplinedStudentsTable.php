<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\DB;

class TopDisciplinedStudentsTable extends BaseWidget
{
    protected static ?string $heading = 'Siswa Paling Disiplin';
    protected int | string | array $columnSpan = 'full';

    public ?string $selectedMonth = null;
    public ?string $selectedYear = null;
    public ?string $selectedClass = null;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Get the current month and year if not set
        $this->selectedMonth = $this->selectedMonth ?? now()->format('m');
        $this->selectedYear = $this->selectedYear ?? now()->format('Y');

        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();

        $query = Student::query()
            ->with(['class'])
            ->select([
                'students.*',
                DB::raw('COUNT(CASE WHEN student_attendances.status = "hadir" THEN 1 END) as total_present'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = "terlambat" THEN 1 END) as total_late'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = "tidak_hadir" THEN 1 END) as total_absent'),
                DB::raw('(
                    COUNT(CASE WHEN student_attendances.status = "hadir" THEN 1 END) * 100 +
                    COUNT(CASE WHEN student_attendances.status = "terlambat" THEN 1 END) * 70 +
                    COUNT(CASE WHEN student_attendances.status = "sakit" THEN 1 END) * 80 +
                    COUNT(CASE WHEN student_attendances.status = "izin" THEN 1 END) * 75 -
                    COUNT(CASE WHEN student_attendances.status = "tidak_hadir" THEN 1 END) * 50
                ) as discipline_score')
            ])
            ->leftJoin('student_attendances', 'students.id', '=', 'student_attendances.student_id')
            ->whereBetween('student_attendances.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('students.id', 'students.name', 'students.nis', 'students.class_id', 'students.gender', 'students.fingerprint_id', 'students.photo', 'students.parent_whatsapp', 'students.created_at', 'students.updated_at');

        if ($this->selectedClass) {
            $query->where('students.class_id', $this->selectedClass);
        }

        return $query->orderBy('discipline_score', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('rank')
                    ->label('Peringkat')
                    ->getStateUsing(function ($record, $rowLoop) {
                        return $rowLoop->iteration;
                    })
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state == 1 => 'warning',
                        $state == 2 => 'gray',
                        $state == 3 => 'orange',
                        default => 'primary'
                    })
                    ->width('80px'),

                TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('class.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('primary')
                    ->width('120px'),

                TextColumn::make('total_present')
                    ->label('Total Kehadiran')
                    ->getStateUsing(fn ($record) => $record->total_present ?? 0)
                    ->icon('heroicon-o-check-circle')
                    ->iconColor('success')
                    ->sortable()
                    ->width('140px')
                    ->alignCenter(),

                TextColumn::make('discipline_score')
                    ->label('Skor Disiplin')
                    ->getStateUsing(fn ($record) => round($record->discipline_score ?? 0, 1))
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 800 => 'success',
                        $state >= 600 => 'warning',
                        default => 'danger'
                    })
                    ->sortable()
                    ->width('120px')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->relationship('class', 'name')
                    ->placeholder('Semua Kelas'),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->defaultSort('discipline_score', 'desc')
            ->paginated([10, 25, 50])
            ->striped()
            ->defaultPaginationPageOption(10)
            ->extremePaginationLinks()
            ->poll('30s');
    }

    public function setFilters(?string $month, ?string $year, ?string $class): void
    {
        $this->selectedMonth = $month;
        $this->selectedYear = $year;
        $this->selectedClass = $class;
    }
}
