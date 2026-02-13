<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Tests;

use Illuminate\Support\Facades\Route;
use Inertia\ServiceProvider as InertiaServiceProvider;
use LaravelPlus\GlobalSettings\GlobalSettingsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            GlobalSettingsServiceProvider::class,
            PermissionServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('permission.testing', true);

        $app['config']->set('view.paths', [__DIR__ . '/resources/views']);

        $app['config']->set('inertia.testing.ensure_pages_exist', false);
    }

    protected function defineRoutes($router): void
    {
        Route::get('/login', fn () => 'login')->name('login');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
