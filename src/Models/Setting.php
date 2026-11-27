<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelPlus\GlobalSettings\Enums\SettingGroup;
use LaravelPlus\GlobalSettings\Enums\SettingRole;

final class Setting extends Model
{
    /**
     * Container key of the per-request settings cache.
     *
     * Bound as a scoped instance, so Octane and queue workers drop it between
     * requests and jobs instead of serving values loaded by an earlier one.
     */
    public const string CACHE = 'global-settings.cache';

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
        self::saved(fn () => self::flushCache());
        self::deleted(fn () => self::flushCache());
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
     * Uses a request-scoped cache to avoid repeated DB queries within a single request.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return app(self::CACHE)[$key] ?? $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): bool
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        ) !== null;
    }

    /**
     * Flush the request-scoped settings cache.
     */
    public static function flushCache(): void
    {
        app()->forgetInstance(self::CACHE);
    }

    /**
     * Load all settings keyed by setting key.
     *
     * @return array<string, string|null>
     */
    public static function loadAll(): array
    {
        try {
            return self::query()->pluck('value', 'key')->all();
        } catch (\Throwable) {
            // Table may not exist during migrations
            return [];
        }
    }
}
