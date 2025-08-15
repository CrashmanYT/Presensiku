<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => Auth::user()?->can('roles.manage') ?? false)
                ->after(fn () => app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions()),
        ];
    }

    protected function afterSave(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function mount(int|string $record): void
    {
        abort_unless(Auth::user()?->can('roles.manage'), 403);
        parent::mount($record);
    }
}
