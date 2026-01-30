<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Facades;

use Illuminate\Support\Facades\Facade;
use LaravelPlus\GlobalSettings\Services\SettingsService;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool set(string $key, mixed $value)
 * @method static bool has(string $key)
 * @method static \Illuminate\Database\Eloquent\Collection all()
 * @method static \LaravelPlus\GlobalSettings\Models\Setting|null findById(int $id)
 * @method static \LaravelPlus\GlobalSettings\Models\Setting create(array $data)
 * @method static bool update(int $id, array $data)
 * @method static bool delete(int $id)
 * @method static \Illuminate\Database\Eloquent\Collection search(string $search)
 * @method static \Illuminate\Database\Eloquent\Collection getByRole(string $role)
 * @method static array getMultiple(array $keys)
 * @method static bool setMultiple(array $settings)
 *
 * @see \LaravelPlus\GlobalSettings\Services\SettingsService
 */
final class GlobalSettings extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return SettingsService::class;
    }
}
