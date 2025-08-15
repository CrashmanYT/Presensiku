<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Role')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function ($query) {
                        // Non-admins cannot grant 'admin' role
                        if (! Auth::user()?->hasRole('admin')) {
                            $query->where('name', '!=', 'admin');
                        }
                        return $query;
                    })
                    ->visible(function ($livewire) {
                        // Require roles.manage and prevent modifying own roles
                        $can = Auth::user()?->can('roles.manage') ?? false;
                        $isSelf = method_exists($livewire, 'getOwnerRecord')
                            ? ($livewire->getOwnerRecord()?->is(Auth::user()) ?? false)
                            : false;
                        return $can && ! $isSelf;
                    }),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->visible(function ($livewire) {
                        $can = Auth::user()?->can('roles.manage') ?? false;
                        $isSelf = method_exists($livewire, 'getOwnerRecord')
                            ? ($livewire->getOwnerRecord()?->is(Auth::user()) ?? false)
                            : false;
                        return $can && ! $isSelf;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->visible(function ($livewire) {
                            $can = Auth::user()?->can('roles.manage') ?? false;
                            $isSelf = method_exists($livewire, 'getOwnerRecord')
                                ? ($livewire->getOwnerRecord()?->is(Auth::user()) ?? false)
                                : false;
                            return $can && ! $isSelf;
                        }),
                ]),
            ]);
    }
}
