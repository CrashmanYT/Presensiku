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

class LeastDisciplinedStudentsTable extends BaseWidget
{
    protected static ?string $heading = 'Siswa Perlu Perhatian Khusus';
    protected int | string | array $columnSpan = 'full';

    public ?string $selectedMonth = null;
    public ?string $selectedYear = null;
    public ?string $selectedClass = null;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Get the current month and year if not set
        $this->selectedMonth = $filters['bulan'] ?? now()->format('m');
        $this->selectedYear = $filters['tahun'] ?? now()->format('Y');

        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();

        // Calculate working days
        $totalWorkingDays = $this->getWorkingDaysInMonth($startDate, $endDate);

        $query = Student::query()
            ->with(['class'])
            ->select([
                'students.*',
                DB::raw('COUNT(CASE WHEN student_attendances.status = "hadir" THEN 1 END) as total_present'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = "terlambat" THEN 1 END) as total_late'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = "tidak_hadir" THEN 1 END) as total_absent'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = "sakit" THEN 1 END) as total_sick'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = "izin" THEN 1 END) as total_permission'),
                DB::raw("(
                    (COUNT(CASE WHEN student_attendances.status = 'hadir' THEN 1 END) +
                     COUNT(CASE WHEN student_attendances.status = 'terlambat' THEN 1 END) +
                     COUNT(CASE WHEN student_attendances.status = 'sakit' THEN 1 END) +
                     COUNT(CASE WHEN student_attendances.status = 'izin' THEN 1 END))
                    / {$totalWorkingDays} * 100
                ) as attendance_percentage")
            ])
            ->leftJoin('student_attendances', 'students.id', '=', 'student_attendances.student_id')
            ->whereBetween('student_attendances.date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('students.id', 'students.name', 'students.nis', 'students.class_id', 'students.gender', 'students.fingerprint_id', 'students.photo', 'students.parent_whatsapp', 'students.created_at', 'students.updated_at');

        if ($this->selectedClass) {
            $query->where('students.class_id', $this->selectedClass);
        }

        return $query->having('total_late', '>', 0)
            ->orHaving('total_absent', '>', 0)
            ->orderBy('total_late', 'desc')
            ->orderBy('total_absent', 'desc')
            ->orderBy('attendance_percentage', 'asc');
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
                    ->color('danger')
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

                TextColumn::make('total_late')
                    ->label('Total Terlambat')
                    ->getStateUsing(fn ($record) => $record->total_late ?? 0)
                    ->icon('heroicon-o-clock')
                    ->iconColor('warning')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->width('140px')
                    ->alignCenter(),

                TextColumn::make('total_absent')
                    ->label('Total Alpa')
                    ->getStateUsing(fn ($record) => $record->total_absent ?? 0)
                    ->icon('heroicon-o-x-circle')
                    ->iconColor('danger')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->width('120px')
                    ->alignCenter(),

                TextColumn::make('attendance_percentage')
                    ->label('Persentase Kehadiran')
                    ->getStateUsing(fn ($record) => round($record->attendance_percentage ?? 0, 1) . '%')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->attendance_percentage >= 90 => 'success',
                        $record->attendance_percentage >= 75 => 'warning',
                        default => 'danger'
                    })
                    ->width('160px')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->relationship('class', 'name')
                    ->placeholder('Semua Kelas'),
                SelectFilter::make('month')
                ->label('Bulan')
                ->options([
                    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                ])

            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::Modal)
            ->persistFiltersInSession()
            ->defaultSort('total_late', 'desc')
            ->paginated([10, 25, 50])
            ->striped()
            ->defaultPaginationPageOption(10)
            ->extremePaginationLinks()
            ->poll('30s')
            ->emptyStateHeading('Hebat! Tidak ada siswa yang bermasalah')
            ->emptyStateDescription('Semua siswa menunjukkan kedisiplinan yang baik')
            ->emptyStateIcon('heroicon-o-face-smile');
    }

    public function setFilters(?string $month, ?string $year, ?string $class): void
    {
        $this->selectedMonth = $month;
        $this->selectedYear = $year;
        $this->selectedClass = $class;
    }

    private function getWorkingDaysInMonth(Carbon $startDate, Carbon $endDate): int
    {
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            // Assume Monday-Friday are working days (1=Monday, 5=Friday)
            if ($current->dayOfWeek >= 1 && $current->dayOfWeek <= 5) {
                $workingDays++;
            }
            $current->addDay();
        }

        return max($workingDays, 1); // Minimum 1 to avoid division by 0
    }
}
