<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?int $navigationSort = 8;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('permissions.view') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Permission')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->placeholder('contoh: logs.view')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama permission tidak dapat diubah setelah dibuat.')
                            ->disabledOn('edit'),
                        Forms\Components\TextInput::make('guard_name')
                            ->default('web')
                            ->hint('Guard default: web')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->sortable(),
                Tables\Columns\TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->state(fn (Permission $record) => Str::before($record->name, '.') ?: '-'),
                Tables\Columns\TextColumn::make('roles_count')
                    ->label('Dipakai Role')
                    ->counts('roles')
                    ->badge(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Langsung ke User')
                    ->counts('users')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Group')
                    ->options(function () {
                        $groups = Permission::query()
                            ->pluck('name')
                            ->map(fn ($n) => Str::before($n, '.'))
                            ->filter()
                            ->unique()
                            ->sort()
                            ->values()
                            ->all();

                        return array_combine($groups, $groups);
                    })
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $query->where('name', 'like', $value . '.%');
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()?->can('permissions.manage') ?? false),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()?->can('permissions.manage') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->can('permissions.manage') ?? false),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
