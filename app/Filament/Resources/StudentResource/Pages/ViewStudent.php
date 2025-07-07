<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ViewStudent extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = StudentResource::class;

    protected static string $view = 'filament.student-resource.pages.view-student';

    public $selectedMonth;
    public $selectedYear;
    public $selectedChartYear;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
        $this->selectedChartYear = now()->year;
    }

    

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    

    public function updatedSelectedChartYear(): void
    {
        // Livewire akan secara otomatis me-refresh widget ketika properti berubah
    }


    public function table(Table $table): Table
    {
        return $this->getDailyHistoryTable(); // Default query, tidak akan terlihat
    }



    public function getMonthlyRecapData(int $month, int $year): Builder
    {
        if (!$this->record || !$this->record->id) {
            return \App\Models\StudentAttendance::query()->whereRaw('1 = 0'); // Return empty query
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        return \App\Models\StudentAttendance::query()
            ->select(
                'students.id as student_id',
                DB::raw('students.name as student_name'),
                DB::raw('classes.name as class_name'),
                DB::raw('COUNT(student_attendances.id) as total_hari_absensi'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = \'hadir\' THEN 1 END) as total_hadir'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = \'tidak_hadir\' THEN 1 END) as total_tidak_hadir'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = \'terlambat\' THEN 1 END) as total_terlambat'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = \'sakit\' THEN 1 END) as total_sakit'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = \'izin\' THEN 1 END) as total_izin')
            )
            ->join('students', 'student_attendances.student_id', '=', 'students.id')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->where('student_attendances.student_id', $this->record->id) // Filter berdasarkan siswa yang sedang dilihat
            ->whereBetween('student_attendances.date', [$startDate, $endDate])
            ->groupBy('students.id', 'students.name', 'classes.name');
    }

    public function getMonthlyRecapSummary(): ?object
    {
        if (!$this->record || !$this->record->id) {
            return null;
        }
        return $this->getMonthlyRecapData($this->selectedMonth, $this->selectedYear)->first();
    }

    public function getDailyHistoryTable(): Table
    {
        if (!$this->record || !$this->record->id) {
            return Table::make($this)->query(\App\Models\StudentAttendance::query()->whereRaw('1 = 0')); // Return empty table
        }

        $query = \App\Models\StudentAttendance::query()
            ->where('student_id', $this->record->id) // Filter berdasarkan siswa yang sedang dilihat
            ->orderBy('date', 'desc')
            ->with(['student.class', 'device']);

        return Table::make($this)
            ->query($query)
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->label('Tanggal')
                    ->sortable(),
                TextColumn::make('time_in')
                    ->time('H:i')
                    ->label('Jam Masuk'),
                TextColumn::make('time_out')
                    ->time('H:i')
                    ->label('Jam Keluar'),
                TextColumn::make('status')
                    ->searchable()
                    ->label('Status')
                    ->badge(),
                TextColumn::make('device.name')
                    ->label('Perangkat')
                    ->sortable(),
            ])
            ->paginated([5, 10, 25]); // Paginasi untuk histori harian
    }

    public function updatedSelectedMonth(): void
    {
        // Livewire akan secara otomatis me-refresh widget ketika properti berubah
    }

    public function updatedSelectedYear(): void
    {
        // Livewire akan secara otomatis me-refresh widget ketika properti berubah
    }
}
