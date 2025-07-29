<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\TopDisciplinedStudentsTable;
use App\Filament\Widgets\LeastDisciplinedStudentsTable;

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