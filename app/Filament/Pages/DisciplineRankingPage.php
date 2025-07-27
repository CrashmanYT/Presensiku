<?php

namespace App\Filament\Pages;

use App\Models\DisciplineRanking;
use App\Models\Student;
use App\Models\StudentAttendance;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Filament\Widgets\TopDisciplinedStudentsTable;
use App\Filament\Widgets\LeastDisciplinedStudentsTable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DisciplineRankingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static string $view = 'filament.pages.discipline-ranking-page';
    protected static ?string $navigationGroup = 'Laporan & Analitik';
    protected static ?string $title = 'Peringkat Disiplin Siswa';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'laporan/peringkat-disiplin';

    public ?string $selectedMonth = null;
    public ?string $selectedYear = null;
    public ?string $selectedClass = null;

    public function mount(): void
    {
        $this->selectedMonth = now()->format('m');
        $this->selectedYear = now()->format('Y');
        $this->form->fill([
            'month' => $this->selectedMonth,
            'year' => $this->selectedYear,
            'class_id' => $this->selectedClass,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('month')
                    ->label('Bulan')
                    ->options([
                        '01' => 'Januari',
                        '02' => 'Februari',
                        '03' => 'Maret',
                        '04' => 'April',
                        '05' => 'Mei',
                        '06' => 'Juni',
                        '07' => 'Juli',
                        '08' => 'Agustus',
                        '09' => 'September',
                        '10' => 'Oktober',
                        '11' => 'November',
                        '12' => 'Desember',
                    ])
                    ->default(now()->format('m'))
                    ->reactive()
                    ->afterStateUpdated(fn ($state) => $this->updatedMonth($state)),

                Select::make('year')
                    ->label('Tahun')
                    ->options(function () {
                        $years = [];
                        for ($i = now()->year - 2; $i <= now()->year + 1; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    })
                    ->default(now()->format('Y'))
                    ->reactive()
                    ->afterStateUpdated(fn ($state) => $this->updatedYear($state)),

                Select::make('class_id')
                    ->label('Kelas (Opsional)')
                    ->options(function () {
                        return \App\Models\Classes::pluck('name', 'id')->toArray();
                    })
                    ->placeholder('Semua Kelas')
                    ->reactive()
                    ->afterStateUpdated(fn ($state) => $this->updatedClass($state)),
            ])
            ->columns(3);
    }

    public function updatedMonth($state): void
    {
        $this->selectedMonth = $state;
    }

    public function updatedYear($state): void
    {
        $this->selectedYear = $state;
    }

    public function updatedClass($state): void
    {
        $this->selectedClass = $state;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TopDisciplinedStudentsTable::make([
                'selectedMonth' => $this->selectedMonth,
                'selectedYear' => $this->selectedYear,
                'selectedClass' => $this->selectedClass,
            ]),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            LeastDisciplinedStudentsTable::make([
                'selectedMonth' => $this->selectedMonth,
                'selectedYear' => $this->selectedYear,
                'selectedClass' => $this->selectedClass,
            ]),
        ];
    }

    public function getFormStatePath(): ?string
    {
        return null;
    }
}
