<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LeastDisciplinedStudentsTable;
use App\Filament\Widgets\TopDisciplinedStudentsTable;
use Filament\Pages\Page;

class DisciplineRankingPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static string $view = 'filament.pages.discipline-ranking-page';

    protected static ?string $navigationGroup = 'Laporan & Analitik';

    protected static ?string $title = 'Peringkat Disiplin Siswa';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'laporan/peringkat-disiplin';

    protected function getHeaderWidgets(): array
    {
        return [
            TopDisciplinedStudentsTable::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            LeastDisciplinedStudentsTable::class,
        ];
    }
}
