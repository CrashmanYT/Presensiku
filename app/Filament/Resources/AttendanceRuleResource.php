<?php

namespace App\Filament\Resources;

use App\Enums\DayOfWeekEnum;
use App\Filament\Resources\AttendanceRuleResource\Pages;
use App\Filament\Resources\AttendanceRuleResource\RelationManagers;
use App\Models\AttendanceRule;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint\Operators\ContainsOperator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class AttendanceRuleResource extends Resource
{
    protected static ?string $model = AttendanceRule::class;
    protected static ?string $navigationGroup = 'Pengaturan Sistem';
    protected static ?string $label = 'Jadwal Absensi';
    protected static ?string $navigationLabel = 'Jadwal Absensi';
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?int $navigationSort = 1;


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['class']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('class_id')
                    ->label('Kelas')
                    ->preload()
                    ->searchable()
                    ->relationship('class', 'name')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                Forms\Components\Select::make('day_of_week')
                    ->options(DayOfWeekEnum::class)
                    ->multiple()
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
                    ->separator(',')
                    ->label('Jadwal Harian')
                    ->searchable()
                    ->formatStateUsing(fn ($state) =>
                        collect($state)
                            ->map(fn ($day) => \App\Enums\DayOfWeekEnum::tryFrom($day)?->getLabel() ?? $day)
                            ->implode(', ')
                    ),
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
                QueryBuilder::make()
                    ->constraints([
                        RelationshipConstraint::make('class')
                            ->label('Nama Kelas')
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->preload()
                            ),
                        TextConstraint::make('description')
                            ->label('Deskripsi'),
                        ]),
                Filter::make('day_of_week')
                    ->label('Jadwal Harian')
                    ->form([
                        Select::make('day_of_week')
                            ->multiple()
                            ->options(DayOfWeekEnum::class)
                            ->searchable()
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['day_of_week'])) {
                            return $query; // ⬅️ Tidak filter apapun jika belum pilih!
                        }

                        $query->where(function ($q) use ($data) {
                            foreach ((array) $data['day_of_week'] as $day) {
                                $q->orWhereJsonContains('day_of_week', $day);
                            }
                        });
                    })

            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormWidth('5xl')
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
