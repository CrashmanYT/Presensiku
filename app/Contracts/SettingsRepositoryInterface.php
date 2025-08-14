<?php

namespace App\Contracts;

interface SettingsRepositoryInterface
{
    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a setting value. Type and group can be auto-resolved by implementation.
     */
    public function set(string $key, mixed $value, ?string $type = null, ?string $groupName = null): void;

    /**
     * Get all settings as nested array (dot-keys expanded) with casting.
     *
     * @return array<string,mixed>
     */
    public function allAsNested(): array;

    /**
     * Get settings by group name with casting.
     *
     * @return array<string,mixed>
     */
    public function getByGroup(string $group): array;

    /**
     * Get all public settings with casting.
     *
     * @return array<string,mixed>
     */
    public function getPublic(): array;

    /**
     * Forget a key.
     */
    public function forget(string $key): void;
}
