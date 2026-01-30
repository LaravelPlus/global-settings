<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LaravelPlus\GlobalSettings\Http\Controllers\Admin\SettingsController;

$config = config('global-settings.admin', []);
$prefix = $config['prefix'] ?? 'admin/settings';
$middleware = $config['middleware'] ?? ['web', 'auth'];

Route::middleware($middleware)
    ->prefix($prefix)
    ->name('admin.settings.')
    ->group(function (): void {
        Route::patch('bulk', [SettingsController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('create', [SettingsController::class, 'create'])->name('create');
        Route::post('/', [SettingsController::class, 'store'])->name('store');
        Route::get('{setting}/edit', [SettingsController::class, 'edit'])->name('edit');
        Route::put('{setting}', [SettingsController::class, 'update'])->name('update');
        Route::patch('{setting}', [SettingsController::class, 'update']);
        Route::delete('{setting}', [SettingsController::class, 'destroy'])->name('destroy');
    });
