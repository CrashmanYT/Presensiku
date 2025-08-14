<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group_name',
        'description',
        'is_public',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('settings');
            Cache::forget('settings_full');
        });

        static::deleted(function () {
            Cache::forget('settings');
            Cache::forget('settings_full');
        });
    }

    /**
     * Get the user who created this setting
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this setting
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get a setting value by key with optional default
     */
    public static function get(string $key, $default = null)
    {
        $settings = Cache::remember('settings_full', 3600, function () {
            return static::query()
                ->get(['key', 'value', 'type'])
                ->mapWithKeys(fn ($s) => [
                    $s->key => ['value' => $s->value, 'type' => $s->type],
                ])
                ->toArray();
        });

        $row = $settings[$key] ?? null;
        if ($row === null) {
            return $default;
        }

        return static::castValue($row['value'], $row['type'] ?? 'string');
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, ?string $type = null, ?string $groupName = null): void
    {
        $detectedType = $type ?? match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value), is_object($value) => 'json',
            default => 'string',
        };

        $storeValue = is_array($value) || is_object($value) ? json_encode($value) : $value;
        $resolvedGroup = $groupName ?? (str_contains($key, '.') ? explode('.', $key)[0] : 'general');

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storeValue,
                'type' => $detectedType,
                'group_name' => $resolvedGroup,
                'updated_by' => Auth::id(),
            ]
        );
    }

    /**
     * Cast value to proper type
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            'string' => (string) $value,
            default => $value,
        };
    }

    /**
     * Get settings by group
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group_name', $group)
            ->get(['key', 'value', 'type'])
            ->mapWithKeys(fn ($s) => [
                $s->key => static::castValue($s->value, $s->type),
            ])
            ->toArray();
    }

    /**
     * Get all public settings (for frontend use)
     */
    public static function getPublic(): array
    {
        return static::where('is_public', true)
            ->get(['key', 'value', 'type'])
            ->mapWithKeys(fn ($s) => [
                $s->key => static::castValue($s->value, $s->type),
            ])
            ->toArray();
    }

    /**
     * Check if a setting exists
     */
    public static function has(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    /**
     * Remove a setting
     */
    public static function forget(string $key): void
    {
        static::where('key', $key)->delete();
    }

    /**
     * Get all settings as nested array (keys expanded by dot-notation) with proper casting.
     *
     * @return array<string,mixed>
     */
    public static function allAsNested(): array
    {
        $result = [];
        static::query()->get(['key', 'value', 'type'])->each(function ($s) use (&$result) {
            Arr::set($result, $s->key, static::castValue($s->value, $s->type));
        });
        return $result;
    }
}
