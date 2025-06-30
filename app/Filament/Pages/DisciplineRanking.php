<?php

namespace App\Filament\Pages;

use App\Models\DisciplineRanking as ModelsDisciplineRanking;
use Filament\Pages\Page;

class DisciplineRanking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $title = 'Peringkat Disiplin';
    protected static ?string $navigationGroup = 'Data Absensi';

    protected static string $view = 'filament.pages.discipline-ranking';

    public function getTopStudentsByDiscipline() {
        return ModelsDisciplineRanking::orderBy('total_present', 'desc')->limit(10)->get();
    
    }
    public function getTopStudentsByLate() {
        return ModelsDisciplineRanking::orderBy('total_late', 'desc')->limit(10)->get();
    }
}
