<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Repositories;

use LaravelPlus\GlobalSettings\Contracts\SettingsRepositoryInterface;
use LaravelPlus\GlobalSettings\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Setting repository implementation.
 *
 * Provides data access methods for Setting models with self-contained base CRUD operations.
 */
final class SettingRepository implements SettingsRepositoryInterface
{
    /**
     * The model class name.
     *
     * @var class-string<Setting>
     */
    protected readonly string $modelClass;

    /**
     * Create a new setting repository instance.
     */
    public function __construct()
    {
        $this->modelClass = Setting::class;
    }

    /**
     * Get a new query builder instance.
     *
     * @return Builder<Setting>
     */
    public function query(): Builder
    {
        return $this->modelClass::query();
    }

    /**
     * Find a model by its primary key.
     *
     * @param  array<string>  $columns
     */
    public function find(mixed $id, array $columns = ['*']): ?Model
    {
        return $this->query()->find($id, $columns);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  array<string>  $columns
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(mixed $id, array $columns = ['*']): Model
    {
        return $this->query()->findOrFail($id, $columns);
    }

    /**
     * Find a model by given attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string>  $columns
     */
    public function findBy(array $attributes, array $columns = ['*']): ?Model
    {
        return $this->query()->where($attributes)->first($columns);
    }

    /**
     * Find all models by given attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string>  $columns
     * @return Collection<int, Setting>
     */
    public function findAllBy(array $attributes, array $columns = ['*']): Collection
    {
        return $this->query()->where($attributes)->get($columns);
    }

    /**
     * Create a new model instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model
    {
        return $this->modelClass::create($attributes);
    }

    /**
     * Update a model by its primary key.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(mixed $id, array $attributes): bool
    {
        $model = $this->find($id);

        if (!$model) {
            return false;
        }

        return $model->update($attributes);
    }

    /**
     * Delete a model by its primary key.
     */
    public function delete(mixed $id): bool
    {
        $model = $this->find($id);

        if (!$model) {
            return false;
        }

        return $model->delete();
    }

    /**
     * Get all models.
     *
     * @param  array<string>  $columns
     * @return Collection<int, Setting>
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    /**
     * Paginate the query results.
     *
     * @param  array<string>  $columns
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage, $columns);
    }

    /**
     * Get a setting value by key.
     *
     * Automatically decodes JSON strings and returns the appropriate value type.
     *
     * @param  string  $key  The setting key
     * @param  mixed  $default  The default value if not found
     * @return mixed The setting value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->findBy(['key' => $key]);

        if (!$setting) {
            return $default;
        }

        $value = $setting->value;

        // Try to decode JSON if it's a JSON string
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    /**
     * Set a setting value by key.
     *
     * Automatically converts booleans to strings and encodes arrays/objects as JSON.
     * Creates a new setting if it doesn't exist, otherwise updates the existing one.
     *
     * @param  string  $key  The setting key
     * @param  mixed  $value  The value to set
     * @return bool True if successful
     */
    public function set(string $key, mixed $value): bool
    {
        // Convert boolean to string for checkbox fields
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        // Encode arrays/objects as JSON
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        $setting = $this->findBy(['key' => $key]);

        if ($setting) {
            return $this->update($setting->id, ['value' => $value]);
        }

        return $this->create(['key' => $key, 'value' => $value]) !== null;
    }

    /**
     * Check if a setting exists.
     *
     * @param  string  $key  The setting key
     * @return bool True if the setting exists
     */
    public function has(string $key): bool
    {
        return $this->findBy(['key' => $key]) !== null;
    }
}
