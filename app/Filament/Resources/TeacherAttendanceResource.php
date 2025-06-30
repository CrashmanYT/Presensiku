<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatusEnum;
use App\Filament\Resources\TeacherAttendanceResource\Pages;
use App\Filament\Resources\TeacherAttendanceResource\RelationManagers;
use App\Models\TeacherAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeacherAttendanceResource extends Resource
{
    protected static ?string $model = TeacherAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Data Absensi';
    protected static ?string $navigationLabel = 'Absensi Guru';
    protected static ?string $label = 'Absensi Guru';


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
            'index' => Pages\ListTeacherAttendances::route('/'),
            'create' => Pages\CreateTeacherAttendance::route('/create'),
            'view' => Pages\ViewTeacherAttendance::route('/{record}'),
            'edit' => Pages\EditTeacherAttendance::route('/{record}/edit'),
        ];
    }
}
