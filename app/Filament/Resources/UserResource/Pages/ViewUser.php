<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => Auth::user()?->can('users.manage') ?? false),
        ];
    }

    public function mount($record): void
    {
        abort_unless(Auth::user()?->can('users.view'), 403);
        parent::mount($record);
    }
}
