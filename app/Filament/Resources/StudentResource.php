<?php

namespace App\Filament\Resources;

use App\Enums\GenderEnum;
use App\Filament\Exports\StudentExporter;
use App\Filament\Imports\StudentImporter;
use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Classes;
use App\Models\Student;
use Filament\Actions\ImportAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationGroup = 'Data Sekolah';
    protected static ?string $navigationLabel = 'Data Murid';
    protected static ?string $label = 'Data Murid';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';



    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['class']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('nis')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('class_id')
                    ->relationship('class', 'name')
                    ->required(),
                Forms\Components\Select::make('gender')
                    ->required()
                    ->options(GenderEnum::class),
                Forms\Components\TextInput::make('fingerprint_id')
                    ->label('ID Sidik Jari'),
                Forms\Components\FileUpload::make('photo')
                    ->image()
                    ->imageEditor(),
                Forms\Components\TextInput::make('parent_whatsapp')
                    ->tel()
                    ->prefix('+62'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\TextEntry::make('name'),
                Infolists\Components\TextEntry::make('nis'),
                Infolists\Components\TextEntry::make('class.name'),
                Infolists\Components\TextEntry::make('gender'),
                Infolists\Components\TextEntry::make('fingerprint_id'),
                Infolists\Components\ImageEntry::make('photo'),
                Infolists\Components\TextEntry::make('parent_whatsapp'),
                Infolists\Components\TextEntry::make('created_at')->dateTime(),
                Infolists\Components\TextEntry::make('updated_at')->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nis')
                    ->searchable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gender')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fingerprint_id')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('photo'),
                Tables\Columns\TextColumn::make('parent_whatsapp')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\QueryBuilder::make()
                    ->constraints([
                        Tables\Filters\QueryBuilder\Constraints\SelectConstraint::make('name')
                            ->multiple()
                            ->options(Student::pluck('name', 'name')->all())
                            ->label('Nama Siswa'),
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('nis')
                            ->label('NIS'),
                        Tables\Filters\QueryBuilder\Constraints\SelectConstraint::make('class_id')
                            ->label('Kelas')
                            ->options(Classes::pluck('name', 'id')->all())
                            ->multiple(),
                        Tables\Filters\QueryBuilder\Constraints\SelectConstraint::make('gender')
                            ->label('Jenis Kelamin')
                            ->options(GenderEnum::class)
                            ->multiple(),
                    ])
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::Modal)
            ->filtersFormWidth(MaxWidth::FiveExtraLarge)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                ExportBulkAction::make()
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make('singeldata')->withColumns([
                        Column::make('name')->heading('Nama'),
                        Column::make('nis')->heading('NIS'),
                        Column::make('class.name')->heading('Kelas'),
                        Column::make('gender')->heading('Jenis Kelamin'),
                        Column::make('fingerprint_id')->heading('ID Sidik Jari'),
                        Column::make('photo')->heading('Photo'),
                        Column::make('parent_whatsapp')->heading('Nomor WA Orang Tua'),
                        Column::make('created_at')->heading('Tanggal Dibuat'),
                        Column::make('updated_at')->heading('Tanggal Diubah'),
                    ])
                ])
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make('Import Data Murid')
                    ->importer(StudentImporter::class)
                    ->color('info')
                    ->icon('heroicon-o-arrow-down-tray'),
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make('export')
                    ->label('Export Data Murid')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->exports([
                        ExcelExport::make('form')->withColumns([
                            Column::make('name')->heading('Nama'),
                            Column::make('nis')->heading('NIS'),
                            Column::make('class.name')->heading('Kelas'),
                            Column::make('gender')->heading('Jenis Kelamin'),
                            Column::make('fingerprint_id')->heading('ID Sidik Jari'),
                            Column::make('photo')->heading('Photo'),
                            Column::make('parent_whatsapp')->heading('Nomor WA Orang Tua'),
                            Column::make('created_at')->heading('Tanggal Dibuat'),
                            Column::make('updated_at')->heading('Tanggal Diubah'),
                        ])
                    ])
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
