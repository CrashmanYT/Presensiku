<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassesResource\Pages;
use App\Filament\Resources\ClassesResource\RelationManagers;
use App\Models\Classes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClassesResource extends Resource
{
    protected static ?string $model = Classes::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                Forms\Components\TextInput::make('level')
                    ->required()
                    ->numeric()
                    ->required()
                    ->label('Kelas'),
                Forms\Components\TextInput::make('major')
                    ->required()
                    ->required()
                    ->label('Jurusan'),
                Forms\Components\Select::make('homeroom_teacher_id')
                    ->relationship('homeroomTeacher', 'name')
                    ->required()
                    ->label('Wali Kelas'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nama Kelas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('level')
                    ->searchable()
                    ->label('Level Kelas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('major')
                    ->label('Jurusan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('homeroom_teacher_id.name')
                    ->label('Wali Kelas')
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
            'index' => Pages\ListClasses::route('/'),
            'create' => Pages\CreateClasses::route('/create'),
            'view' => Pages\ViewClasses::route('/{record}'),
            'edit' => Pages\EditClasses::route('/{record}/edit'),
        ];
    }
}
