<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatusEnum;
use App\Filament\Resources\StudentAttendanceResource\Pages;
use App\Filament\Resources\StudentAttendanceResource\RelationManagers;
use App\Models\StudentAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentAttendanceResource extends Resource
{
    protected static ?string $model = StudentAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->label('Nama Siswa')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.class_id.name')
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
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListStudentAttendances::route('/'),
            'create' => Pages\CreateStudentAttendance::route('/create'),
            'view' => Pages\ViewStudentAttendance::route('/{record}'),
            'edit' => Pages\EditStudentAttendance::route('/{record}/edit'),
        ];
    }
}
