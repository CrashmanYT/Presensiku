<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => Auth::user()?->can('users.manage') ?? false),
        ];
    }

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('users.view'), 403);
        parent::mount();
    }
}
