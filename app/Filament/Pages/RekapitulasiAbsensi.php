<?php

namespace App\Filament\Pages;

use App\Filament\Exports\DailyAttendanceExporter;
use App\Filament\Exports\SummaryAttendanceExporter;
use App\Models\StudentAttendance;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class RekapitulasiAbsensi extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string $view = 'filament.pages.rekapitulasi-absensi';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $title = 'Rekapitulasi Absensi';

    protected static ?string $slug = 'laporan/rekapitulasi-absensi';

    public ?string $activeTab = 'harian';

    public $selectedMonth;
    public $selectedYear;
    public $startDate;
    public $endDate;

    public function mount(): void
    {
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
        $this->updateDateRange();
    }

    public function updatedActiveTab(): void
    {
        $this->updateDateRange();
        $this->resetTable();
    }

    public function updatedSelectedMonth(): void
    {
        $this->updateDateRange();
        $this->resetTable();
    }

    public function updatedSelectedYear(): void
    {
        $this->updateDateRange();
        $this->resetTable();
    }

    public function updatedStartDate(): void
    {
        $this->updateDateRange();
        $this->resetTable();
    }

    public function updatedEndDate(): void
    {
        $this->updateDateRange();
        $this->resetTable();
    }

    protected function updateDateRange(): void
    {
        switch ($this->activeTab) {
            case 'harian':
                $this->startDate = now()->toDateString();
                $this->endDate = now()->toDateString();
                break;
            case 'mingguan':
                // Jika startDate atau endDate belum diatur oleh user, gunakan minggu ini
                if (!$this->startDate || !$this->endDate) {
                    $this->startDate = now()->startOfWeek()->toDateString();
                    $this->endDate = now()->endOfWeek()->toDateString();
                }
                break;
            case 'bulanan':
                $this->startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth()->toDateString();
                $this->endDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth()->toDateString();
                break;
        }
    }

    public function getTableRecordKey(Model $record): string
    {
        return $record->student_id ?? $record->id;
    }

    public function table(Table $table): Table
    {
        $this->updateDateRange(); // Pastikan rentang tanggal selalu terbaru

        if ($this->activeTab === 'harian') {
            $query = StudentAttendance::query()->whereBetween('date', [$this->startDate, $this->endDate])->with(['student.class', 'device']);
            $columns = [
                TextColumn::make('student.name')->searchable()->label('Nama Siswa')->sortable(),
                TextColumn::make('student.class.name')->searchable()->label('Kelas')->sortable(),
                TextColumn::make('date')->date()->label('Tanggal')->sortable(),
                TextColumn::make('time_in')->time('H:i')->label('Jam Masuk'),
                TextColumn::make('status')->searchable()->label('Status')->badge(),
            ];
        } else { // Untuk 'mingguan' dan 'bulanan'
            $query = $this->getSummaryData($this->startDate, $this->endDate);
            $columns = [
                TextColumn::make('student_name')->label('Nama Siswa')->searchable()->sortable()->formatStateUsing(fn ($state, $record) => $record->student_name),
                TextColumn::make('class_name')->label('Kelas')->searchable()->sortable()->formatStateUsing(fn ($state, $record) => $record->class_name),
                TextColumn::make('total_hadir')->label('Hadir')->sortable()->formatStateUsing(fn ($state, $record) => $record->total_hadir),
                TextColumn::make('total_terlambat')->label('Terlambat')->sortable()->formatStateUsing(fn ($state, $record) => $record->total_terlambat),
                TextColumn::make('total_tidak_hadir')->label('Alpa')->sortable()->formatStateUsing(fn ($state, $record) => $record->total_tidak_hadir),
                TextColumn::make('total_sakit')->label('Sakit')->sortable()->formatStateUsing(fn ($state, $record) => $record->total_sakit),
                TextColumn::make('total_izin')
                    ->label('Izin')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->total_izin),

                // Kolom Persentase Kehadiran Total
                TextColumn::make('persentase_kehadiran_total')
                    ->label('Kehadiran Total (%)')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if (!isset($record->total_hari_absensi) || $record->total_hari_absensi == 0) {
                            return 0;
                        }
                        $total_hadir_tercatat = $record->total_hadir + $record->total_terlambat + $record->total_sakit + $record->total_izin;
                        return round(($total_hadir_tercatat / $record->total_hari_absensi) * 100, 2);
                    })
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(function ($state) {
                        if ($state >= 90) return 'success';
                        if ($state >= 75) return 'warning';
                        return 'danger';
                    }),
            ];
        }

        return $table
                ->query($query)
                ->columns($columns)
                ->paginated([10, 25, 50, 100])
                ->defaultSort('student_name', 'asc')
                ->recordUrl(
                    fn (object $record): string => \App\Filament\Resources\StudentResource::getUrl('view', ['record' => $record->student_id])
                );
    }

    public function getSummaryData(string $startDate, string $endDate): Builder
    {
        $query =  StudentAttendance::query()
            ->select(
                'students.id as student_id', // <-- TAMBAHKAN BARIS INI
                DB::raw('students.name as student_name'),
                DB::raw('classes.name as class_name'),
                DB::raw('COUNT(student_attendances.id) as total_hari_absensi'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = \'hadir\' THEN 1 END) as total_hadir'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = \'tidak_hadir\' THEN 1 END) as total_tidak_hadir'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = \'terlambat\' THEN 1 END) as total_terlambat'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = \'sakit\' THEN 1 END) as total_sakit'),
                DB::raw('COUNT(CASE WHEN student_attendances.status = \'izin\' THEN 1 END) as total_izin'),
            )
            ->join('students', 'student_attendances.student_id', '=', 'students.id')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->whereBetween('student_attendances.date', [$startDate, $endDate])
            // GROUP BY juga perlu menyertakan students.id
            ->groupBy('students.id', 'students.name', 'classes.name');

//       dd($query);
       return $query;
    }

    protected function getHeaderActions(): array
    {
        $this->updateDateRange(); // Pastikan rentang tanggal selalu terbaru
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        return [
            Action::make('export')
                ->label('Export Excel')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () use ($startDate, $endDate) {
                    $period = match ($this->activeTab) {
                        'harian' => 'Harian',
                        'mingguan' => 'Mingguan',
                        'bulanan' => 'Bulanan',
                        default => 'Rekap',
                    };
                    $fileName = 'Rekapitulasi_Absensi_' . $period . '_' . $startDate . '_sd_' . $endDate . '.xlsx';

                    if ($this->activeTab === 'harian') {
                        return Excel::download(new DailyAttendanceExporter(
                            StudentAttendance::query()->whereBetween('date', [$startDate, $endDate])->with(['student.class', 'device'])
                        ), $fileName);
                    } else {
                        return Excel::download(new SummaryAttendanceExporter(
                            $this->getSummaryData($startDate, $endDate)
                        ), $fileName);
                    }
                }),
        ];
    }
}
