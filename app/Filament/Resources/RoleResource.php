<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 7;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('roles.view') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Role')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('guard_name')
                            ->default('web')
                            ->hint('Guard default: web')
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Permissions')
                    ->schema([
                        Forms\Components\Placeholder::make('permissions_help')
                            ->label('Panduan')
                            ->content("Gunakan penamaan permission dot-case per modul, misalnya:\n- logs.view\n- logs.download\n- logs.manage\n- roles.view\n- roles.manage\n- permissions.view\n- permissions.manage\n\nTips: ketik prefix seperti 'logs.' pada kotak pencarian untuk menyaring cepat.")
                            ->columnSpanFull(),
                        Forms\Components\CheckboxList::make('permissions')
                            ->relationship(titleAttribute: 'name')
                            ->searchable()
                            ->columns(2)
                            ->bulkToggleable()
                            ->helperText('Centang izin yang dimiliki role ini.'),
                    ]),
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
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Jml Permission')
                    ->counts('permissions')
                    ->badge(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Jml User')
                    ->counts('users')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()?->can('roles.manage') ?? false),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()?->can('roles.manage') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->can('roles.manage') ?? false),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
