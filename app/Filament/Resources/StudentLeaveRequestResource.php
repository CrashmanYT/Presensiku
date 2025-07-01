<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatusEnum;
use App\Enums\LeaveRequestViaEnum;
use App\Filament\Resources\StudentLeaveRequestResource\Pages;
use App\Filament\Resources\StudentLeaveRequestResource\RelationManagers;
use App\Models\StudentLeaveRequest;
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
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudentLeaveRequestResource extends Resource
{
    protected static ?string $model = StudentLeaveRequest::class;
    protected static ?string $navigationGroup = 'Data Absensi';
    protected static ?string $navigationLabel = 'Perizinan Siswa';
    protected static ?string $label = "Perizinan Siswa";
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->required()
                    ->searchable()
                    ->relationship('student', 'name')
                    ->label('Nama Siswa'),
                Forms\Components\DatePicker::make('date')
                    ->label('Tanggal')
                    ->required(),
                Forms\Components\Textarea::make('reason')
                    ->required()
                    ->label('Alasan')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('submitted_by')
                    ->label('Dikirimkan Oleh '),
                Forms\Components\Select::make('via')
                    ->options(LeaveRequestViaEnum::class)
                    ->required(),
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
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->toggleable()
                    ->label('Tanggal')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->limit(50)
                    ->toggleable()
                    ->label('Alasan'),
                Tables\Columns\TextColumn::make('submitted_by')
                    ->label('Dikirimkan Oleh')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('via')
                    ->badge()
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
                        RelationshipConstraint::make('student')
                            ->label('Nama Siswa')
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
            ->filtersLayout(filtersLayout: FiltersLayout::Modal)
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
            'index' => Pages\ListStudentLeaveRequests::route('/'),
            'create' => Pages\CreateStudentLeaveRequest::route('/create'),
            'view' => Pages\ViewStudentLeaveRequest::route('/{record}'),
            'edit' => Pages\EditStudentLeaveRequest::route('/{record}/edit'),
        ];
    }
}
