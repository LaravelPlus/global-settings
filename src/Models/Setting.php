<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Models;

use LaravelPlus\GlobalSettings\Enums\SettingRole;
use Illuminate\Database\Eloquent\Model;

final class Setting extends Model
{
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
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => SettingRole::class,
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
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        return $setting?->value ?? $default;
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
}
