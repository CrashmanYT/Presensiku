<?php

namespace App\Filament\Resources;

use App\Enums\LeaveRequestViaEnum;
use App\Filament\Resources\TeacherLeaveRequestResource\Pages;
use App\Filament\Resources\TeacherLeaveRequestResource\RelationManagers;
use App\Models\TeacherLeaveRequest;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeacherLeaveRequestResource extends Resource
{
    protected static ?string $model = TeacherLeaveRequest::class;

    protected static ?string $navigationGroup = 'Manajemen Absensi';
    protected static ?string $navigationLabel = 'Izin Guru';
    protected static ?string $label = "Izin Guru";
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['teacher']);
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
                Forms\Components\Select::make('type')
                    ->options([
                        'sakit' => 'Sakit',
                        'izin' => 'Izin',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->required(),
                Forms\Components\Textarea::make('reason')
                    ->label('Alasan')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('attachment')
                    ->label('Surat Keterangan'),
                Forms\Components\TextInput::make('submitted_by')
                    ->label('Dikirimkan Oleh'),
                Forms\Components\Select::make('via')
                    ->options(LeaveRequestViaEnum::class)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->numeric()
                    ->searchable()
                    ->label('Nama Guru')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'warning' => 'sakit',
                        'info' => 'izin',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->toggleable()
                    ->label('Tanggal Mulai')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->toggleable()
                    ->label('Tanggal Selesai')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->columnSpanFull(),
                Tables\Columns\TextColumn::make('submitted_by')
                    ->searchable()
                    ->label('Dikirimkan Oleh'),
                Tables\Columns\TextColumn::make('via')
                    ->searchable(),
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
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->preload()
                            ),
                        DateConstraint::make('date')
                            ->label('Tanggal'),
                        TextConstraint::make('reason')
                            ->label('Alasan'),
                        TextConstraint::make('submitte_by')
                            ->label('Dikirimkan Oleh'),
                        SelectConstraint::make('via')
                            ->label('Via')
                            ->options(LeaveRequestViaEnum::class)
                            ->multiple()
                    ])
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
            'index' => Pages\ListTeacherLeaveRequests::route('/'),
            'create' => Pages\CreateTeacherLeaveRequest::route('/create'),
            'view' => Pages\ViewTeacherLeaveRequest::route('/{record}'),
            'edit' => Pages\EditTeacherLeaveRequest::route('/{record}/edit'),
        ];
    }
}
