<?php

namespace App\Repositories;

use App\Contracts\SettingsRepositoryInterface;
use App\Models\Setting;

class SettingsRepository implements SettingsRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }

    public function set(string $key, mixed $value, ?string $type = null, ?string $groupName = null): void
    {
        Setting::set($key, $value, $type, $groupName);
    }

    public function allAsNested(): array
    {
        return Setting::allAsNested();
    }

    public function getByGroup(string $group): array
    {
        return Setting::getByGroup($group);
    }

    public function getPublic(): array
    {
        return Setting::getPublic();
    }

    public function forget(string $key): void
    {
        Setting::forget($key);
    }
}
