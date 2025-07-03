<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScanLogResource\Pages;
use App\Filament\Resources\ScanLogResource\RelationManagers;
use App\Models\ScanLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScanLogResource extends Resource
{
    protected static ?string $model = ScanLog::class;
    protected static ?string $navigationGroup = 'Sistem';
    protected static ?string $navigationLabel = 'Log Absensi';
    protected static ?string $label = 'Log Absensi';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['device']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fingerprint_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event_type')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('scanned_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('device.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('result')
                    ->searchable()
                    ->badge(),
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
            'index' => Pages\ListScanLogs::route('/'),
            'create' => Pages\CreateScanLog::route('/create'),
            'view' => Pages\ViewScanLog::route('/{record}'),
            'edit' => Pages\EditScanLog::route('/{record}/edit'),
        ];
    }
}
