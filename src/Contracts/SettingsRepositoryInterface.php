<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Settings repository interface.
 *
 * Provides data access methods for Setting models with base CRUD operations.
 */
interface SettingsRepositoryInterface
{
    /**
     * Get a new query builder instance.
     */
    public function query(): Builder;

    /**
     * Find a model by its primary key.
     *
     * @param  array<string>  $columns
     */
    public function find(mixed $id, array $columns = ['*']): ?Model;

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  array<string>  $columns
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(mixed $id, array $columns = ['*']): Model;

    /**
     * Find a model by given attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string>  $columns
     */
    public function findBy(array $attributes, array $columns = ['*']): ?Model;

    /**
     * Find all models by given attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string>  $columns
     * @return Collection<int, Model>
     */
    public function findAllBy(array $attributes, array $columns = ['*']): Collection;

    /**
     * Create a new model instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model;

    /**
     * Update a model by its primary key.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(mixed $id, array $attributes): bool;

    /**
     * Delete a model by its primary key.
     */
    public function delete(mixed $id): bool;

    /**
     * Get all models.
     *
     * @param  array<string>  $columns
     * @return Collection<int, Model>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Paginate the query results.
     *
     * @param  array<string>  $columns
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Get a setting value by key.
     *
     * @param  string  $key  The setting key
     * @param  mixed  $default  The default value if not found
     * @return mixed The setting value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a setting value by key.
     *
     * @param  string  $key  The setting key
     * @param  mixed  $value  The value to set
     * @return bool True if successful
     */
    public function set(string $key, mixed $value): bool;

    /**
     * Check if a setting exists.
     *
     * @param  string  $key  The setting key
     * @return bool True if the setting exists
     */
    public function has(string $key): bool;
}
