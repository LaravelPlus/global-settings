# Global Settings for Laravel

A standalone settings management package for Laravel with an admin panel powered by Inertia.js.

## Features

- Key-value settings store with typed fields (input, checkbox, multioptions)
- Role-based settings (system, user, plugin) — system settings are protected from deletion
- Admin panel with search, create, edit, bulk update, and delete
- Facade for easy access (`GlobalSettings::get()`, `GlobalSettings::set()`)
- Configurable admin route prefix and middleware
- AuditLog integration (conditional, works when host app provides `App\Models\AuditLog`)

## Requirements

- PHP 8.4+
- Laravel 12+
- Inertia.js (for admin panel views)

## Installation

### As a local package (symlinked)

Add the repository and require the package in your root `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/laravelplus/global-settings",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "laravelplus/global-settings": "@dev"
    }
}
```

Then install:

```bash
composer update laravelplus/global-settings
```

### Run migrations

```bash
php artisan migrate
```

### Seed default settings

```bash
php artisan db:seed --class="LaravelPlus\\GlobalSettings\\Database\\Seeders\\SettingsSeeder"
```

Or add it to your `DatabaseSeeder.php`:

```php
$this->call([
    \LaravelPlus\GlobalSettings\Database\Seeders\SettingsSeeder::class,
]);
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=global-settings-config
```

```php
// config/global-settings.php
return [
    'admin' => [
        'enabled'    => true,
        'prefix'     => 'admin/settings',
        'middleware'  => ['web', 'auth'],
    ],
];
```

| Option | Description | Default |
|---|---|---|
| `admin.enabled` | Register admin routes | `true` |
| `admin.prefix` | URL prefix for admin routes | `admin/settings` |
| `admin.middleware` | Middleware applied to admin routes | `['web', 'auth']` |

## Usage

### Facade

```php
use LaravelPlus\GlobalSettings\Facades\GlobalSettings;

// Get a setting value
$siteName = GlobalSettings::get('site_name', 'Default');

// Set a setting value
GlobalSettings::set('maintenance_mode', true);

// Check if a setting exists
if (GlobalSettings::has('registration_enabled')) {
    // ...
}

// Bulk get/set
$values = GlobalSettings::getMultiple(['site_name', 'contact_email']);
GlobalSettings::setMultiple(['site_name' => 'My App', 'contact_email' => 'hi@example.com']);
```

### Model

```php
use LaravelPlus\GlobalSettings\Models\Setting;

$value = Setting::get('auth_layout', 'simple');
Setting::set('auth_layout', 'split');
```

### Service (dependency injection)

```php
use LaravelPlus\GlobalSettings\Services\SettingsService;

public function __construct(private SettingsService $settings) {}

public function index()
{
    $all = $this->settings->all();
    $byRole = $this->settings->getByRole('system');
    $results = $this->settings->search('email');
}
```

### Repository

```php
use LaravelPlus\GlobalSettings\Contracts\SettingsRepositoryInterface;

public function __construct(private SettingsRepositoryInterface $repo) {}
```

## Admin Routes

When `admin.enabled` is `true`, the following routes are registered:

| Method | URI | Name | Description |
|---|---|---|---|
| GET | `/admin/settings` | `admin.settings.index` | List all settings |
| GET | `/admin/settings/create` | `admin.settings.create` | Create form |
| POST | `/admin/settings` | `admin.settings.store` | Store new setting |
| GET | `/admin/settings/{setting}/edit` | `admin.settings.edit` | Edit form |
| PUT/PATCH | `/admin/settings/{setting}` | `admin.settings.update` | Update setting |
| DELETE | `/admin/settings/{setting}` | `admin.settings.destroy` | Delete setting |
| PATCH | `/admin/settings/bulk` | `admin.settings.bulk-update` | Bulk update values |

The controller renders Inertia pages at `admin/Settings`, `admin/Settings/Create`, and `admin/Settings/Edit`. The host application must provide these Vue page components.

## Setting Roles

Settings are scoped by role using the `SettingRole` enum:

| Role | Description |
|---|---|
| `system` | Core app settings — cannot be deleted via admin panel |
| `user` | User-configurable settings |
| `plugin` | Settings added by plugins/packages |

## Publishing

```bash
# Config
php artisan vendor:publish --tag=global-settings-config

# Migrations
php artisan vendor:publish --tag=global-settings-migrations

# Seeders
php artisan vendor:publish --tag=global-settings-seeders
```

## License

MIT
