<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatusEnum;
use App\Filament\Resources\StudentAttendanceResource\Pages;
use App\Helpers\ExportColumnHelper;
use App\Filament\Resources\StudentAttendanceResource\RelationManagers;
use App\Models\StudentAttendance;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class StudentAttendanceResource extends Resource
{
    protected static ?string $model = StudentAttendance::class;
    protected static ?string $navigationGroup = 'Manajemen Absensi';
    protected static ?string $navigationLabel = 'Absensi Murid';
    protected static ?string $label = 'Absensi Murid';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->label('Nama Siswa')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.class.name')
                    ->searchable()
                    ->label('Kelas')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->label('Tanggal')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_in')
                    ->time('H:i')
                    ->toggleable()
                    ->label('Jam Masuk'),
                Tables\Columns\TextColumn::make('time_out')
                    ->time('H:i')
                    ->toggleable()
                    ->label('Jam Keluar'),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->toggleable()
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('device.name')
                    ->label('Perangkat')
                    ->toggleable()
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
                SelectFilter::make('Nama')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),
                SelectFilter::make('Kelas')
                    ->relationship('student.class', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),
                SelectFilter::make('Status')
                    ->options(AttendanceStatusEnum::class)
                    ->multiple(),
                Filter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),

                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->whereDate('date', '<=', $date));

                    }),

                Filter::make('waktu_masuk')
                    ->label('Jam Masuk')
                    ->form([
                        TimePicker::make('jam_masuk'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['jam_masuk'], fn($q, $time) => $q->whereTime('time_in', $time));
                    }),
                Filter::make('waktu_keluar')
                    ->label('Jam Keluar')
                    ->form([
                        TimePicker::make('jam_keluar'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['jam_keluar'], fn($q, $time) => $q->whereTime('time_out', $time));
                    }),

            ])
            ->filtersLayout(filtersLayout: FiltersLayout::Modal)
            ->filtersFormWidth('5xl')
            ->filtersFormColumns(2)
            ->headerActions([
//                ExportAction::make('export')
//                    ->label('Export Absensi Murid')
//                    ->icon('heroicon-o-arrow-up-tray')
//                    ->exports([
//                        ExcelExport::make('absensimurid')->withColumns([
//                            Column::make('student.name')->heading('Nama Siswa'),
//                            Column::make('class.name')->heading('Kelas'),
//                            Column::make('status')->heading('Status'),
//                            Column::make('date')->heading('Tanggal'),
//                            Column::make('time_in')->heading('Jam Masuk'),
//                            Column::make('time_out')->heading('Jam Keluar'),
//                        ])->modifyQueryUsing(function ($query, array $data) {
//                            return $query
//                                ->join('students', 'student_attendances.student_id', '=', 'students.id')
//                                ->join('classes', 'students.class_id', '=', 'classes.id')
//                                ->leftJoin('devices', 'student_attendances.device_id', '=', 'devices.id')
//                                ->select(
//                                    'student_attendances.*',
//                                    'students.name as student_name',
//                                    'classes.name as class_name',
//                                    'devices.name as device_name',
//                                );
//                        })
//                            ->queue()
//                            ->chunkSize(200)
//                    ])
                ])
            ->headerActions([
                ExportAction::make('export')
                    ->label('Export Data Absensi Siswa')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->exports([
                        ExcelExport::make('absensi-siswa')
                            ->withColumns(
                                ExportColumnHelper::getStudentAttendanceColumns()
                            )
                            ->withFilename('Data Absensi Siswa.xlsx')
                            ->modifyQueryUsing(function ($query) {
                                return $query->with(['student.class', 'device'])
                                    ->select('student_attendances.*')
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
                        ExcelExport::make('absensi-siswa-bulk')
                            ->withColumns(
                                ExportColumnHelper::getStudentAttendanceColumns()
                            )
                            ->withFilename('Data Absensi Siswa (Terpilih).xlsx')
                            ->modifyQueryUsing(function ($query) {
                                return $query->with(['student.class', 'device'])
                                    ->select('student_attendances.*')
                                    ->orderBy('date', 'desc')
                                    ->orderBy('id', 'desc');
                            })
                            ->queue()
                            ->chunkSize(1000)
                    ])
                ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->required()
                    ->relationship('student', 'name')
                    ->label('Nama Siswa')
                    ->searchable(),
                Forms\Components\DatePicker::make('date')
                    ->label('Tanggal')
                    ->required(),
                Forms\Components\TimePicker::make('time_in')
                    ->label('Jam Masuk'),
                Forms\Components\TimePicker::make('time_out')
                    ->label('Jam Keluar'),
                Forms\Components\Select::make('status')
                    ->options(AttendanceStatusEnum::class)
                    ->label('Status Kehadiran')
                    ->required(),
                Forms\Components\FileUpload::make('photo_in')
                    ->image()
                    ->label('Log Foto'),
                Forms\Components\Select::make('device_id')
                    ->label('Nama Device')
                    ->relationship('device', 'name'),
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
            'index' => Pages\ListStudentAttendances::route('/'),
            'create' => Pages\CreateStudentAttendance::route('/create'),
            'view' => Pages\ViewStudentAttendance::route('/{record}'),
            'edit' => Pages\EditStudentAttendance::route('/{record}/edit'),
        ];
    }
}
