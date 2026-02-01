---
name: global-settings-development
description: >-
  Activate when working with the laravelplus/global-settings package — using the
  GlobalSettings facade, Setting model, SettingRole enum, managing application
  settings, or building admin pages under admin/settings.
---

# Global Settings Development

Package: `laravelplus/global-settings` — Location: `packages/laravelplus/global-settings/`
Namespace: `LaravelPlus\GlobalSettings` — Facade: `GlobalSettings` — Config: `global-settings`

## When to Apply

- Using `GlobalSettings::` facade or `Setting::get()` / `Setting::set()` static methods
- Creating or modifying settings controllers, services, repositories, or migrations in the package
- Working with the `SettingRole` enum or role-based setting access
- Building admin UI pages under `admin/settings`
- Reading or writing application-wide key-value settings
- Writing tests for settings functionality

## Repository + Service Pattern

Uses `SettingsRepositoryInterface` → `SettingRepository` → `SettingsService`:

<code-snippet name="GlobalSettings Facade Usage" lang="php">
use LaravelPlus\GlobalSettings\Facades\GlobalSettings;

// Key-value access
GlobalSettings::get('site.name', 'Default');
GlobalSettings::set('site.name', 'My App');
GlobalSettings::has('site.name');

// Batch operations
GlobalSettings::getMultiple(['site.name', 'site.tagline']);
GlobalSettings::setMultiple(['site.name' => 'App', 'site.tagline' => 'Hello']);

// CRUD
GlobalSettings::all();
GlobalSettings::findById($id);
GlobalSettings::create(['key' => 'site.name', 'value' => 'App', 'role' => 'user']);
GlobalSettings::update($id, ['value' => 'New Value']);
GlobalSettings::delete($id);  // System settings cannot be deleted

// Search & filter
GlobalSettings::search('site');
GlobalSettings::getByRole('system');
</code-snippet>

### Direct Model Access

The `Setting` model also provides static shortcuts:

<code-snippet name="Setting Model Static Methods" lang="php">
use LaravelPlus\GlobalSettings\Models\Setting;

// Simple get/set without going through the service
Setting::get('site.name', 'Default');
Setting::set('site.name', 'My App');  // Uses updateOrCreate
</code-snippet>

## Setting Model

| Field | Type | Notes |
|-------|------|-------|
| `key` | string | Unique identifier, used as route key |
| `value` | text (nullable) | Stored value — JSON auto-decoded by repository |
| `field_type` | string | `input`, `checkbox`, or `multioptions` |
| `options` | text (nullable) | Cast to array — for multioptions field type |
| `label` | string (nullable) | Human-readable label |
| `description` | text (nullable) | Help text |
| `role` | SettingRole enum | `system`, `user`, or `plugin` |

Route key name: `key` (not `id`) — routes bind by setting key.

## SettingRole Enum

<code-snippet name="SettingRole Enum" lang="php">
use LaravelPlus\GlobalSettings\Enums\SettingRole;

SettingRole::System;  // 'system' — protected, cannot be deleted
SettingRole::User;    // 'user' — standard user-editable settings
SettingRole::Plugin;  // 'plugin' — managed by plugins
</code-snippet>

## Service Methods

The `SettingsService` (accessed via `GlobalSettings` facade) provides:

| Method | Description |
|--------|-------------|
| `get(string $key, mixed $default)` | Get setting value (auto-decodes JSON) |
| `set(string $key, mixed $value)` | Set setting value (auto-encodes arrays/objects) |
| `has(string $key)` | Check if setting exists |
| `all()` | Get all settings |
| `findById(int $id)` | Find by ID |
| `create(array $data)` | Create setting (transactional) |
| `update(int $id, array $data)` | Update setting (transactional) |
| `delete(int $id)` | Delete setting — **throws for System role** |
| `search(string $search)` | Search by key, label, description, value |
| `getByRole(string $role)` | Filter settings by role |
| `getMultiple(array $keys)` | Batch get multiple keys |
| `setMultiple(array $settings)` | Batch set key-value pairs |

### Repository Value Handling

The `SettingRepository` handles type conversion:
- `get()` — Auto-decodes JSON strings
- `set()` — Converts booleans to `'1'`/`'0'`, encodes arrays/objects as JSON

## Admin Routes

Prefix: `admin/settings` — Middleware: `['web', 'auth']`

| Method | Path | Name | Description |
|--------|------|------|-------------|
| `GET` | `/` | `admin.settings.index` | List & search settings |
| `GET` | `/create` | `admin.settings.create` | Create form |
| `POST` | `/` | `admin.settings.store` | Store new setting |
| `GET` | `/{setting}/edit` | `admin.settings.edit` | Edit form |
| `PUT/PATCH` | `/{setting}` | `admin.settings.update` | Update setting |
| `DELETE` | `/{setting}` | `admin.settings.destroy` | Delete setting |
| `PATCH` | `/bulk` | `admin.settings.bulk-update` | Bulk update multiple |

The `{setting}` parameter binds by `key` (not `id`).

### Admin Controller

Renders Inertia views: `admin/Settings`, `admin/Settings/Create`, `admin/Settings/Edit`. The `bulkUpdate` action accepts an array of settings with nullable values. The `destroy` action logs to `AuditLog` if the model exists.

## Form Requests

| Request | Key Rules |
|---------|-----------|
| `SettingStoreRequest` | `key`: required, unique; `field_type`: input/checkbox/multioptions; `role`: user or plugin only (not system) |
| `SettingUpdateRequest` | Same as store but `key` unique excluding current record |
| `SettingsUpdateRequest` | Bulk: `settings` array with nullable values |

## Configuration

```php
// config/global-settings.php
return [
    'admin' => [
        'enabled' => true,                         // Enable admin routes
        'prefix' => 'admin/settings',              // Admin route prefix
        'middleware' => ['web', 'auth'],            // Admin middleware
    ],
];
```

## Conventions

- `declare(strict_types=1)` and `final class` on all concrete classes
- Form Request classes for validation (never inline)
- Config-driven admin route prefix and middleware
- Service wraps operations in transactions
- Casts use `casts()` method on model (not `$casts` property)

## Common Pitfalls

- **System settings cannot be deleted** — `SettingsService::delete()` enforces this; only `user` and `plugin` roles can be deleted
- **Route binds by `key`, not `id`** — `{setting}` in routes resolves via `getRouteKeyName() = 'key'`
- **Store request rejects `system` role** — Only `user` and `plugin` can be created via admin; system settings are seeded
- **Values are auto-converted** — Booleans become `'1'`/`'0'`, arrays/objects become JSON strings in the repository
- **Admin middleware differs from other packages** — Uses `['web', 'auth']` only, not `role:super-admin,admin`
