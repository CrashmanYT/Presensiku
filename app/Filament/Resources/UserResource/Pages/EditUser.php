<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => Auth::user()?->can('users.view') ?? false),
            Actions\DeleteAction::make()
                ->visible(fn () => Auth::user()?->can('users.manage') ?? false),
        ];
    }

    public function mount($record): void
    {
        abort_unless(Auth::user()?->can('users.manage'), 403);
        parent::mount($record);
    }
}
