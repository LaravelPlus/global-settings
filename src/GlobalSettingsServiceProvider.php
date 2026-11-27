<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use LaravelPlus\GlobalSettings\Contracts\SettingsRepositoryInterface;
use LaravelPlus\GlobalSettings\Models\Setting;
use LaravelPlus\GlobalSettings\Repositories\SettingRepository;
use LaravelPlus\GlobalSettings\Services\SettingsService;

final class GlobalSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/global-settings.php', 'global-settings');

        $this->app->scoped(Setting::CACHE, fn (): array => Setting::loadAll());

        $this->app->bind(SettingsRepositoryInterface::class, SettingRepository::class);

        $this->app->singleton(SettingsService::class, fn ($app) => new SettingsService(
            $app->make(SettingsRepositoryInterface::class),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerResources();
        $this->registerGate();
        $this->registerRoutes();
    }

    /**
     * Default admin check: Spatie-style roles when the host's user model has
     * them, deny otherwise (fail closed). A host defines its own
     * `global-settings.admin` gate (it boots after this provider) to decide.
     */
    private function registerGate(): void
    {
        if (! Gate::has('global-settings.admin')) {
            Gate::define('global-settings.admin', fn ($user): bool => method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'admin']));
        }
    }

    /**
     * Register the package's publishable resources.
     */
    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/global-settings.php' => config_path('global-settings.php'),
            ], 'global-settings-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'global-settings-migrations');

            $this->publishes([
                __DIR__.'/../database/seeders' => database_path('seeders'),
            ], 'global-settings-seeders');

            $this->publishes([
                __DIR__.'/../skills/global-settings-development' => base_path('.claude/skills/global-settings-development'),
            ], 'global-settings-skills');

            $this->publishes([
                __DIR__.'/../skills/global-settings-development' => base_path('.github/skills/global-settings-development'),
            ], 'global-settings-skills-github');
        }
    }

    /**
     * Register the package's resources.
     */
    private function registerResources(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Register the package's routes.
     */
    private function registerRoutes(): void
    {
        if ($this->isAdminEnabled()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        }
    }

    /**
     * Check if admin routes should be enabled.
     * GlobalSettings is a required package and cannot be disabled via the DB toggle.
     * The admin pages render through Inertia, an optional dependency: without
     * it the routes are skipped instead of failing on every request.
     */
    private function isAdminEnabled(): bool
    {
        return (bool) config('global-settings.admin.enabled', true) && class_exists(Inertia::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            SettingsRepositoryInterface::class,
            SettingsService::class,
        ];
    }
}
