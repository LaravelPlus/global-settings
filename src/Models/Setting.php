<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Models;

use LaravelPlus\GlobalSettings\Enums\SettingGroup;
use LaravelPlus\GlobalSettings\Enums\SettingRole;
use Illuminate\Database\Eloquent\Model;

final class Setting extends Model
{
    /**
     * In-memory cache of all settings keyed by setting key.
     *
     * @var array<string, string|null>|null
     */
    private static ?array $cache = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'field_type',
        'options',
        'label',
        'description',
        'role',
        'group',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => SettingRole::class,
            'group' => SettingGroup::class,
            'options' => 'array',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * Get a setting value by key.
     *
     * Uses in-memory cache to avoid repeated DB queries within a single request.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::loadCache();

        if (!array_key_exists($key, self::$cache)) {
            return $default;
        }

        return self::$cache[$key] ?? $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): bool
    {
        $result = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        ) !== null;

        // Cache is flushed by the saved event, update it immediately
        if ($result) {
            self::loadCache();
        }

        return $result;
    }

    /**
     * Flush the in-memory settings cache.
     */
    public static function flushCache(): void
    {
        self::$cache = null;
    }

    /**
     * Load all settings into the in-memory cache.
     */
    private static function loadCache(): void
    {
        if (self::$cache !== null) {
            return;
        }

        try {
            self::$cache = self::query()->pluck('value', 'key')->all();
        } catch (\Throwable) {
            // Table may not exist during migrations
            self::$cache = [];
        }
    }
}
