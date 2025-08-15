<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => Auth::user()?->can('roles.manage') ?? false),
        ];
    }

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('roles.view'), 403);
        parent::mount();
    }
}
