<?php

namespace App\Filament\Resources;

use App\Enums\DayOfWeekEnum;
use App\Filament\Resources\AttendanceRuleResource\Pages;
use App\Filament\Resources\AttendanceRuleResource\RelationManagers;
use App\Models\AttendanceRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceRuleResource extends Resource
{
    protected static ?string $model = AttendanceRule::class;
    protected static ?string $navigationGroup = 'Data Absensi';
    protected static ?string $navigationLabel = 'Jadwal Sekolah';
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('class_id')
                    ->label('Kelas')
                    // ->multiple()
                    ->relationship('class', 'name')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                Forms\Components\Select::make('day_of_week')
                    ->options(DayOfWeekEnum::class)
                    // ->multiple()
                    ->label('Jadwal Harian'),
                Forms\Components\DatePicker::make('date_override')
                    ->label('Jadwal Tanggal Tertentu'),
                Forms\Components\TimePicker::make('time_in_start')
                    ->required()
                    ->label('Awal Jam Masuk'),
                Forms\Components\TimePicker::make('time_in_end')
                    ->required()
                    ->label('Akhir Jam Masuk'),
                Forms\Components\TimePicker::make('time_out_start')
                    ->required()
                    ->label('Awal Jam Pulang'),
                Forms\Components\TimePicker::make('time_out_end')
                    ->required()
                    ->label('Akhir Jam Pulang'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('class.name')
                    ->numeric()
                    ->label('Kelas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->label('Deskripsi')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('day_of_week')
                    ->badge()
                    ->label('Jadwal Harian')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_override')
                    ->date()
                    ->label('Jadwal Tanggal Tertentu')
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_in_start')
                    ->toggleable()
                    ->label('Awal Jam Masuk')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('time_in_end')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Akhir Jam Masuk')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('time_out_start')
                    ->toggleable()
                    ->label('Awal Jam Pulang')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('time_out_end')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Akhir Jam Pulang')
                    ->time('H:i'),
                    
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
            'index' => Pages\ListAttendanceRules::route('/'),
            'create' => Pages\CreateAttendanceRule::route('/create'),
            'view' => Pages\ViewAttendanceRule::route('/{record}'),
            'edit' => Pages\EditAttendanceRule::route('/{record}/edit'),
        ];
    }
}
