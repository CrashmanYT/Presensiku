<?php

namespace App\Filament\Pages;

use App\Exports\RekapitulasiAbsensiExport;
use App\Models\StudentAttendance;
use App\Models\Classes;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Form;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RekapitulasiAbsensi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static string $view = 'filament.pages.rekapitulasi-absensi';

    protected static ?string $navigationGroup = 'Laporan & Analitik';

    protected static ?string $title = 'Rekapitulasi Absensi';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'laporan/rekapitulasi-absensi';

    public ?string $activeTab = 'harian';

    public $selectedMonth;

    public $selectedYear;

    public $startDate;

    public $endDate;

    public $selectedDate;

    public $selectedWeek; // Tambahkan properti untuk minggu yang dipilih

    public $weekMonth; // Tambahkan properti untuk bulan dari minggu yang dipilih

    public $selectedClassId; // filter kelas

    public function mount(): void
    {
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
        $this->selectedDate = now()->toDateString();
        $this->selectedWeek = now()->weekOfMonth;
        $this->weekMonth = now()->month;
        $this->selectedClassId = null;
        $this->updateDateRange();
    }

    public function updatedSelectedDate(): void
    {
        $this->updateDateRange();
        $this->resetTable();
    }

    public function updatedActiveTab(): void
    {
        $this->updateDateRange();
        $this->resetTable();
    }

    public function updatedSelectedClassId(): void
    {
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

    public function updatedSelectedWeek(): void
    {
        $this->updateDateRange();
        $this->resetTable();
    }

    public function updatedWeekMonth(): void
    {
        $this->updateDateRange();
        $this->resetTable();
    }

    protected function updateDateRange(): void
    {
        switch ($this->activeTab) {
            case 'harian':
                $this->startDate = $this->selectedDate;
                $this->endDate = $this->selectedDate;
                break;
            case 'mingguan':
                // Gunakan minggu yang dipilih dan bulan untuk menentukan rentang tanggal
                if ($this->selectedWeek && $this->weekMonth) {
                    $date = Carbon::create($this->selectedYear, $this->weekMonth, 1);
                    $date->addWeeks($this->selectedWeek - 1); // Minggu ke-1 dimulai dari 0
                    $this->startDate = $date->startOfWeek()->toDateString();
                    $this->endDate = $date->endOfWeek()->toDateString();
                } else {
                    // Jika belum dipilih, gunakan minggu ini
                    $this->startDate = now()->startOfWeek()->toDateString();
                    $this->endDate = now()->endOfWeek()->toDateString();
                    $this->selectedWeek = now()->weekOfMonth;
                    $this->weekMonth = now()->month;
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
            $query = StudentAttendance::query()
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->when($this->selectedClassId, function (Builder $q) {
                    $q->whereHas('student', function (Builder $qs) {
                        $qs->where('class_id', $this->selectedClassId);
                    });
                })
                ->with(['student.class', 'device']);
            $columns = [
                TextColumn::make('student.name')->searchable()->label('Nama Siswa')->sortable(),
                TextColumn::make('student.class.name')->searchable()->label('Kelas')->sortable(),
                TextColumn::make('date')->date()->label('Tanggal')->sortable(),
                TextColumn::make('time_in')->time('H:i')->label('Jam Masuk'),
                TextColumn::make('status')->searchable()->label('Status')->badge(),
            ];
        } else { // Untuk 'mingguan' dan 'bulanan'
            $query = $this->getSummaryData($this->startDate, $this->endDate)
                ->when($this->selectedClassId, function (Builder $q) {
                    $q->where('students.class_id', $this->selectedClassId);
                });
            $columns = [
                TextColumn::make('student_name')->label('Nama Siswa')->searchable(['students.name'])->sortable('students.name'),
                TextColumn::make('class_name')->label('Kelas')->searchable(['classes.name'])->sortable('classes.name'),
                TextColumn::make('total_hadir')->label('Hadir')->sortable(),
                TextColumn::make('total_terlambat')->label('Terlambat')->sortable(),
                TextColumn::make('total_tidak_hadir')->label('Alpa')->sortable(),
                TextColumn::make('total_sakit')->label('Sakit')->sortable(),
                TextColumn::make('total_izin')->label('Izin')->sortable(),

                // Kolom Persentase Kehadiran Total
                TextColumn::make('persentase_kehadiran_total')
                    ->label('Kehadiran Total (%)')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if (! isset($record->total_hari_absensi) || $record->total_hari_absensi == 0) {
                            return 0;
                        }
                        $total_hadir_tercatat = $record->total_hadir + $record->total_terlambat + $record->total_sakit + $record->total_izin;

                        return round(($total_hadir_tercatat / $record->total_hari_absensi) * 100, 2);
                    })
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->badge()
                    ->color(function ($state) {
                        if ($state >= 90) {
                            return 'success';
                        }
                        if ($state >= 75) {
                            return 'warning';
                        }

                        return 'danger';
                    }),
            ];
        }

        $this->updateDateRange();
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        $period = match ($this->activeTab) {
            'harian' => 'Harian',
            'mingguan' => 'Mingguan',
            'bulanan' => 'Bulanan',
            default => 'Rekap',
        };
        $classSuffix = $this->selectedClassId
            ? '_Kelas_' . optional(Classes::find($this->selectedClassId))->name
            : '';
        $fileName = 'Rekapitulasi_Absensi_'.$period.'_'.$startDate.'_sd_'.$endDate.$classSuffix.'.xlsx';

        return $table
            ->query($query)
            ->columns($columns)
            ->filters([
                // Filter Kelas di header tabel (muncul di semua tab)
                Filter::make('kelas')
                    ->form([
                        Select::make('selectedClassId')
                            ->label('Kelas')
                            ->options(fn () => Classes::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->placeholder('Semua Kelas')
                            ->native(false)
                            ->live(debounce: 200),
                    ])
                    ->indicateUsing(function (array $data) {
                        if (! empty($data['selectedClassId'])) {
                            $name = optional(Classes::find($data['selectedClassId']))->name;
                            return $name ? ['Kelas: '.$name] : [];
                        }
                        return [];
                    })
                    ->query(function (Builder $q, array $data) {
                        // sinkronkan state untuk dipakai export & query bawaan
                        $this->selectedClassId = $data['selectedClassId'] ?? null;
                        if ($this->activeTab === 'harian') {
                            if (! empty($this->selectedClassId)) {
                                $q->whereHas('student', function (Builder $qs) use ($data) {
                                    $qs->where('class_id', $data['selectedClassId']);
                                });
                            }
                        } else {
                            if (! empty($this->selectedClassId)) {
                                $q->where('students.class_id', $data['selectedClassId']);
                            }
                        }
                        return $q;
                    }),

                // Filter Waktu per tab (diringkas ke satu filter agar rapi)
                Filter::make('periode')
                    ->form(function () {
                        if ($this->activeTab === 'harian') {
                            return [
                                DatePicker::make('selectedDate')
                                    ->label('Tanggal')
                                    ->displayFormat('d F Y')
                                    ->format('Y-m-d')
                                    ->native(false)
                                    ->live(debounce: 200),
                            ];
                        }

                        if ($this->activeTab === 'mingguan') {
                            return [
                                Select::make('weekMonth')
                                    ->label('Bulan')
                                    ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F')])->toArray())
                                    ->native(false)
                                    ->live(debounce: 200),
                                Select::make('selectedWeek')
                                    ->label('Minggu')
                                    ->options([
                                        1 => 'Minggu 1',
                                        2 => 'Minggu 2',
                                        3 => 'Minggu 3',
                                        4 => 'Minggu 4',
                                        5 => 'Minggu 5',
                                    ])
                                    ->native(false)
                                    ->live(debounce: 200),
                                Select::make('selectedYear')
                                    ->label('Tahun')
                                    ->options(function () {
                                        $current = now()->year;
                                        $years = range($current - 5, $current + 1);
                                        return collect($years)->mapWithKeys(fn ($y) => [$y => (string) $y])->toArray();
                                    })
                                    ->native(false)
                                    ->live(debounce: 200),
                            ];
                        }

                        // bulanan
                        return [
                            Select::make('selectedMonth')
                                ->label('Bulan')
                                ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F')])->toArray())
                                ->native(false)
                                ->live(debounce: 200),
                            Select::make('selectedYear')
                                ->label('Tahun')
                                ->options(function () {
                                    $current = now()->year;
                                    $years = range($current - 5, $current + 1);
                                    return collect($years)->mapWithKeys(fn ($y) => [$y => (string) $y])->toArray();
                                })
                                ->native(false)
                                ->live(debounce: 200),
                        ];
                    })
                    ->indicateUsing(function (array $data) {
                        $badges = [];
                        if ($this->activeTab === 'harian' && ! empty($data['selectedDate'])) {
                            $badges[] = 'Tanggal: '.$data['selectedDate'];
                        }
                        if ($this->activeTab === 'mingguan') {
                            if (! empty($data['weekMonth'])) {
                                $badges[] = 'Bulan: '.\Carbon\Carbon::create(null, $data['weekMonth'], 1)->translatedFormat('F');
                            }
                            if (! empty($data['selectedWeek'])) {
                                $badges[] = 'Minggu: '.$data['selectedWeek'];
                            }
                            if (! empty($data['selectedYear'])) {
                                $badges[] = 'Tahun: '.$data['selectedYear'];
                            }
                        }
                        if ($this->activeTab === 'bulanan') {
                            if (! empty($data['selectedMonth'])) {
                                $badges[] = 'Bulan: '.\Carbon\Carbon::create(null, $data['selectedMonth'], 1)->translatedFormat('F');
                            }
                            if (! empty($data['selectedYear'])) {
                                $badges[] = 'Tahun: '.$data['selectedYear'];
                            }
                        }
                        return $badges;
                    })
                    ->query(function (Builder $q, array $data) {
                        // sinkronkan state agar updateDateRange() & export memakai nilai terbaru
                        if ($this->activeTab === 'harian') {
                            if (! empty($data['selectedDate'])) {
                                $this->selectedDate = $data['selectedDate'];
                            }
                        } elseif ($this->activeTab === 'mingguan') {
                            if (! empty($data['weekMonth'])) {
                                $this->weekMonth = (int) $data['weekMonth'];
                            }
                            if (! empty($data['selectedWeek'])) {
                                $this->selectedWeek = (int) $data['selectedWeek'];
                            }
                            if (! empty($data['selectedYear'])) {
                                $this->selectedYear = (int) $data['selectedYear'];
                            }
                        } else { // bulanan
                            if (! empty($data['selectedMonth'])) {
                                $this->selectedMonth = (int) $data['selectedMonth'];
                            }
                            if (! empty($data['selectedYear'])) {
                                $this->selectedYear = (int) $data['selectedYear'];
                            }
                        }

                        // perbarui rentang tanggal & refresh query yang sedang dibangun
                        $this->updateDateRange();

                        // terapkan whereBetween untuk harian ke query header-level (q)
                        if ($this->activeTab === 'harian') {
                            $q->whereBetween('date', [$this->startDate, $this->endDate]);
                        } else {
                            // untuk ringkasan, base query (getSummaryData) sudah pakai whereBetween
                            // di sini tidak menambah kondisi lagi agar tidak konflik
                        }

                        return $q;
                    }),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->paginated([10, 25, 50, 100])
            ->defaultSort($this->activeTab === 'harian' ? 'student.name' : 'students.name', 'asc')
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () use ($fileName) {
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new RekapitulasiAbsensiExport(
                                $this->activeTab,
                                $this->startDate,
                                $this->endDate,
                                $this->selectedClassId
                            ),
                            $fileName
                        );
                    }),
            ]);
    }


    public function getSummaryData(string $startDate, string $endDate): Builder
    {
        $query = StudentAttendance::query()
            ->select(
                'students.id as student_id',
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
            ->groupBy('students.id', 'students.name', 'classes.name');

        return $query;
    }


    protected function getFormSchema(): array
    {
        // Kelas selalu tampil di baris pertama
        $classSelect = Select::make('selectedClassId')
            ->label('Kelas')
            ->options(fn () => Classes::query()->orderBy('name')->pluck('name', 'id')->toArray())
            ->placeholder('Semua Kelas')
            ->searchable()
            ->native(false)
            ->live(debounce: 250)
            ->afterStateUpdated(function () {
                $this->updatedSelectedClassId();
            });

        // Grid 2 kolom untuk kontrol waktu, tergantung tab aktif
        $timeControls = match ($this->activeTab) {
            'harian' => [
                DatePicker::make('selectedDate')
                    ->label('Pilih Tanggal')
                    ->displayFormat('d F Y')
                    ->format('Y-m-d')
                    ->default(now())
                    ->native(false)
                    ->closeOnDateSelection()
                    ->required()
                    ->live(debounce: 250)
                    ->afterStateUpdated(function () {
                        $this->updatedSelectedDate();
                    }),
            ],
            'mingguan' => [
                Select::make('weekMonth')
                    ->label('Bulan')
                    ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F')])->toArray())
                    ->native(false)
                    ->live(debounce: 250)
                    ->afterStateUpdated(function () {
                        $this->updatedWeekMonth();
                    }),
                Select::make('selectedWeek')
                    ->label('Minggu')
                    ->options([
                        1 => 'Minggu 1',
                        2 => 'Minggu 2',
                        3 => 'Minggu 3',
                        4 => 'Minggu 4',
                        5 => 'Minggu 5',
                    ])
                    ->native(false)
                    ->live(debounce: 250)
                    ->afterStateUpdated(function () {
                        $this->updatedSelectedWeek();
                    }),
                Select::make('selectedYear')
                    ->label('Tahun')
                    ->options(function () {
                        $current = now()->year;
                        $years = range($current - 5, $current + 1);
                        return collect($years)->mapWithKeys(fn ($y) => [$y => (string) $y])->toArray();
                    })
                    ->native(false)
                    ->live(debounce: 250)
                    ->afterStateUpdated(function () {
                        $this->updatedSelectedYear();
                    }),
            ],
            'bulanan' => [
                Select::make('selectedMonth')
                    ->label('Bulan')
                    ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F')])->toArray())
                    ->native(false)
                    ->live(debounce: 250)
                    ->afterStateUpdated(function () {
                        $this->updatedSelectedMonth();
                    }),
                Select::make('selectedYear')
                    ->label('Tahun')
                    ->options(function () {
                        $current = now()->year;
                        $years = range($current - 5, $current + 1);
                        return collect($years)->mapWithKeys(fn ($y) => [$y => (string) $y])->toArray();
                    })
                    ->native(false)
                    ->live(debounce: 250)
                    ->afterStateUpdated(function () {
                        $this->updatedSelectedYear();
                    }),
            ],
            default => [],
        };

        // Susun schema: Kelas di baris pertama, lalu grid 2 kolom untuk kontrol waktu
        return [
            Grid::make()
                ->schema([
                    $classSelect,
                ])
                ->columns([
                    'default' => 1,
                    'sm' => 2,
                ]),
            Grid::make()
                ->schema($timeControls)
                ->columns([
                    'default' => 1,
                    'sm' => 2,
                ]),
        ];
    }
}
