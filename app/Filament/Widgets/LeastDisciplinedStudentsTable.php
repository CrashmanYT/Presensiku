<?php

namespace App\Filament\Widgets;

use App\Models\Classes;
use App\Models\DisciplineRanking;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LeastDisciplinedStudentsTable extends BaseWidget
{
    protected static ?string $heading = 'Siswa Perlu Perhatian Khusus';

    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return DisciplineRanking::query()
            ->join('students', 'discipline_rankings.student_id', '=', 'students.id')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->select('discipline_rankings.*', 'students.name as student_name', 'classes.name as class_name')
            ->where('score', '<', 0);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('rank')
                    ->label('Peringkat')
                    ->getStateUsing(fn (\Livewire\Component $livewire, object $rowLoop): string => (string) ($rowLoop->iteration + ($livewire->getTableRecordsPerPage() * ($livewire->getTablePage() - 1))))
                    ->badge()
                    ->color('danger')
                    ->width('80px'),

                TextColumn::make('student_name')
                    ->label('Nama Siswa')
                    ->searchable(isIndividual: true)
                    ->sortable()
                    ->wrap(),

                TextColumn::make('class_name')
                    ->label('Kelas')
                    ->badge()
                    ->color('primary')
                    ->width('120px'),

                TextColumn::make('total_late')
                    ->label('Total Terlambat')
                    ->icon('heroicon-o-clock')
                    ->iconColor('warning')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->width('140px')
                    ->alignCenter(),

                TextColumn::make('total_absent')
                    ->label('Total Alpa')
                    ->icon('heroicon-o-x-circle')
                    ->iconColor('danger')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->width('120px')
                    ->alignCenter(),

                TextColumn::make('score')
                    ->label('Skor Disiplin')
                    ->badge()
                    ->color('danger')
                    ->sortable()
                    ->width('120px')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('month')
                    ->label('Bulan & Tahun')
                    ->options(function () {
                        return DisciplineRanking::query()
                            ->select('month')
                            ->distinct()
                            ->orderBy('month', 'desc')
                            ->get()
                            ->mapWithKeys(function ($item) {
                                $date = Carbon::createFromFormat('Y-m', $item->month);

                                return [$item->month => $date->translatedFormat('F Y')];
                            })
                            ->toArray();
                    })
                    ->default(now()->format('Y-m'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'], fn ($query, $value) => $query->where('month', $value));
                    }),

                SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->options(Classes::pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'], fn ($query, $value) => $query->where('students.class_id', $value));
                    }),
            ])
            ->filtersLayout(filtersLayout: FiltersLayout::AboveContent)
            ->filtersFormColumns('2')
            ->defaultSort('score', 'asc')
            ->paginated([10, 25, 50])
            ->striped()
            ->emptyStateHeading('Hebat! Tidak ada siswa yang bermasalah')
            ->emptyStateDescription('Semua siswa menunjukkan kedisiplinan yang baik bulan ini.')
            ->emptyStateIcon('heroicon-o-face-smile');
    }
}
