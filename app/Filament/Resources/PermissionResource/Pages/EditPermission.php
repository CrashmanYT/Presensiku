<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

class EditPermission extends EditRecord
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => Auth::user()?->can('permissions.manage') ?? false)
                ->after(fn () => app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions()),
        ];
    }

    protected function afterSave(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Prevent renaming the permission key in edit mode
        if (array_key_exists('name', $data)) {
            $data['name'] = $this->record->getOriginal('name');
        }
        return $data;
    }

    public function mount(int|string $record): void
    {
        abort_unless(Auth::user()?->can('permissions.manage'), 403);
        parent::mount($record);
    }
}
