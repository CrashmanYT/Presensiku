<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = $data['guard_name'] ?? 'web';
        return $data;
    }

    protected function afterCreate(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('permissions.manage'), 403);
        parent::mount();
    }
}
