<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => Auth::user()?->can('permissions.manage') ?? false),
        ];
    }

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('permissions.view'), 403);
        parent::mount();
    }
}
