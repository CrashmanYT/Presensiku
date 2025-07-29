<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherResource\Pages;
use App\Filament\Resources\TeacherResource\RelationManagers;
use App\Models\Teacher;
use App\Helpers\ExportColumnHelper;
use App\Filament\Imports\TeacherImporter;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;
    protected static ?string $navigationGroup = 'Manajemen Data';
    protected static ?string $navigationLabel = 'Data Guru';
    protected static ?string $label = 'Data Guru';
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                TextInput::make('nip')
                    ->unique(ignoreRecord: true)
                    ->label('Nomor Induk'),
                TextInput::make('fingerprint_id')
                    ->numeric()
                    ->label('ID Sidik Jari')
                    ->required(),
                FileUpload::make('photo')
                    ->image()
                    ->label('Foto Guru')
                    ->imageEditor()
                    ->openable()
                    ->downloadable(),
                TextInput::make('whatsapp_number')
                    ->label('Nomor Whatsapp')
                    ->tel()
                    ->prefix('+62')
                    ->required()


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('nip')
                    ->label('No Induk')
                    ->searchable(),
                TextColumn::make('whatsapp_number')
                    ->label('Nomor WA')
                    ->searchable(),

                ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('download_template')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(route('template.teacher'))
                    ->openUrlInNewTab(),
                Tables\Actions\ImportAction::make('import')
                    ->label('Import Data Guru')
                    ->importer(TeacherImporter::class)
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray'),
                ExportAction::make('export')
                    ->label('Export Data Guru')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->exports([
                        ExcelExport::make('form')->withColumns(
                            ExportColumnHelper::getTeacherColumns()
                        )->withFilename('Data Guru')
                    ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make('export')
                        ->color('info')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->exports([
                            ExcelExport::make('data-guru')
                                ->withColumns(
                                    ExportColumnHelper::getTeacherColumns()
                                )
                                ->withFilename('Data Guru.xlsx')
                        ]),
                ]),
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
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }
}
