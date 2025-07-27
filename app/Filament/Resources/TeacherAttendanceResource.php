<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatusEnum;
use App\Filament\Resources\TeacherAttendanceResource\Pages;
use App\Filament\Resources\TeacherAttendanceResource\RelationManagers;
use App\Models\TeacherAttendance;
use Filament\Forms;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Helpers\ExportColumnHelper;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class TeacherAttendanceResource extends Resource
{
    protected static ?string $model = TeacherAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Manajemen Absensi';
    protected static ?string $navigationLabel = 'Absensi Guru';
    protected static ?string $label = 'Absensi Guru';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['teacher', 'device']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('teacher_id')
                    ->required()
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->label('Nama Guru'),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->label('Tanggal'),
                Forms\Components\TimePicker::make('time_in')
                    ->required()
                    ->label('Jam Masuk'),
                Forms\Components\TimePicker::make('time_out')
                    ->required()
                    ->label('Jam Keluar'),
                Forms\Components\Select::make('status')
                    ->options(AttendanceStatusEnum::class)
                    ->required(),
                Forms\Components\FileUpload::make('photo_in')
                    ->image()
                    ->imageEditor(),
                Forms\Components\Select::make('device_id')
                    ->relationship('device', 'name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->label('Tanggal')
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_in')
                    ->label('Jam Masuk'),
                Tables\Columns\TextColumn::make('time_out')
                    ->label('Jam Keluar'),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('photo_in')
                    ->searchable(),
                Tables\Columns\TextColumn::make('device.name')
                    ->numeric()
                    ->sortable(),
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
                QueryBuilder::make()
                    ->constraints([
                        RelationshipConstraint::make('teacher')
                            ->label('Nama Guru')
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->preload()
                            ),
                        DateConstraint::make('date')
                            ->label('Tanggal'),
                        SelectConstraint::make('status')
                            ->label('Status')
                            ->options(AttendanceStatusEnum::class)
                            ->multiple(),
                        RelationshipConstraint::make('device')
                            ->label('Nama Device')
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->preload()
                            ),
                        ]),
                Filter::make('waktu_masuk')
                    ->label('Jam Masuk')
                    ->form([
                        TimePicker::make('jam_masuk'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['jam_masuk'], fn ($q, $time) => $q->whereTime('time_in', $time));
                    }),
                Filter::make('waktu_keluar')
                    ->label('Jam Keluar')
                    ->form([
                        TimePicker::make('jam_keluar'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['jam_keluar'], fn ($q, $time) => $q->whereTime('time_out', $time));
                    }),
            ])
            ->filtersLayout(filtersLayout: FiltersLayout::Modal)
            ->filtersFormWidth('5xl')
            ->filtersFormColumns(2)
            ->headerActions([
                ExportAction::make('export')
                    ->label('Export Data Absensi Guru')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->exports([
                        ExcelExport::make('absensi-guru')
                            ->withColumns(
                                ExportColumnHelper::getTeacherAttendanceColumns()
                            )
                            ->withFilename('Data Absensi Guru.xlsx')
                            ->modifyQueryUsing(function ($query) {
                                return $query->with(['teacher', 'device'])
                                    ->select('teacher_attendances.*')
                                    ->orderBy('date', 'desc')
                                    ->orderBy('id', 'desc');
                            })
                            ->queue()
                            ->chunkSize(1000)
                    ])
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make('export')
                    ->color('info')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->exports([
                        ExcelExport::make('absensi-guru-bulk')
                            ->withColumns(
                                ExportColumnHelper::getTeacherAttendanceColumns()
                            )
                            ->withFilename('Data Absensi Guru (Terpilih).xlsx')
                            ->modifyQueryUsing(function ($query) {
                                return $query->with(['teacher', 'device'])
                                    ->select('teacher_attendances.*')
                                    ->orderBy('date', 'desc')
                                    ->orderBy('id', 'desc');
                            })
                            ->queue()
                            ->chunkSize(1000)
                    ])
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
            'index' => Pages\ListTeacherAttendances::route('/'),
            'create' => Pages\CreateTeacherAttendance::route('/create'),
            'view' => Pages\ViewTeacherAttendance::route('/{record}'),
            'edit' => Pages\EditTeacherAttendance::route('/{record}/edit'),
        ];
    }
}
