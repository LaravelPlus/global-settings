<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Http\Controllers\Admin;

use LaravelPlus\GlobalSettings\Enums\SettingGroup;
use LaravelPlus\GlobalSettings\Enums\SettingRole;
use LaravelPlus\GlobalSettings\Http\Requests\SettingStoreRequest;
use LaravelPlus\GlobalSettings\Http\Requests\SettingsUpdateRequest;
use LaravelPlus\GlobalSettings\Http\Requests\SettingUpdateRequest;
use LaravelPlus\GlobalSettings\Models\Setting;
use LaravelPlus\GlobalSettings\Services\SettingsService;
use Closure;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin settings controller.
 *
 * Handles displaying and updating all application settings.
 * Middleware is applied via route definitions in routes/admin.php.
 */
final class SettingsController implements HasMiddleware
{
    /**
     * Create a new admin settings controller instance.
     */
    public function __construct(
        private(set) SettingsService $settingsService,
    ) {}

    /**
     * Require an admin or super-admin on every action.
     *
     * Runs as controller middleware, so it fires before FormRequest validation
     * and returns 404 to keep the admin surface undiscoverable.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware(static function (Request $request, Closure $next): mixed {
                $user = $request->user();

                if (!$user || !array_any(['super-admin', 'admin'], fn (string $role): bool => $user->hasRole($role))) {
                    abort(404);
                }

                return $next($request);
            }),
        ];
    }

    /**
     * Get the SettingGroup cases as value+label array for frontend.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function groupOptions(): array
    {
        return array_map(
            fn (SettingGroup $group): array => ['value' => $group->value, 'label' => $group->label()],
            SettingGroup::cases(),
        );
    }

    /**
     * Map a setting model to an array for the frontend.
     *
     * @return array<string, mixed>
     */
    private function mapSetting(Setting $setting): array
    {
        return [
            'uuid' => $setting->uuid,
            'key' => $setting->key,
            'value' => $setting->value,
            'field_type' => $setting->field_type ?? 'input',
            'options' => $setting->options,
            'label' => $setting->label ?? $setting->key,
            'description' => $setting->description,
            'role' => $setting->role?->value ?? SettingRole::User->value,
            'group' => $setting->group?->value,
        ];
    }

    /**
     * Show the admin settings page with paginated results.
     *
     * @param  Request  $request  The incoming request
     * @return Response The Inertia response with settings page data
     */
    public function index(Request $request): Response
    {
        $query = Setting::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search): void {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('id')->paginate(15);

        $paginated->getCollection()->transform(fn (Setting $setting) => $this->mapSetting($setting));

        return Inertia::render('admin/Settings', [
            'settings' => $paginated,
            'groups' => $this->groupOptions(),
            'status' => $request->session()->get('status'),
            'filters' => [
                'search' => $request->get('search', ''),
            ],
        ]);
    }

    /**
     * Show settings filtered by group with paginated results.
     *
     * @param  Request  $request  The incoming request
     * @param  string  $group  The group slug
     * @return Response The Inertia response with grouped settings
     */
    public function group(Request $request, string $group): Response
    {
        $settingGroup = SettingGroup::tryFrom($group);

        if (!$settingGroup) {
            abort(404, 'Invalid settings group.');
        }

        $query = Setting::query()->where('group', $group);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search): void {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('id')->paginate(15);

        $paginated->getCollection()->transform(fn (Setting $setting) => $this->mapSetting($setting));

        return Inertia::render('admin/Settings', [
            'settings' => $paginated,
            'groups' => $this->groupOptions(),
            'currentGroup' => [
                'value' => $settingGroup->value,
                'label' => $settingGroup->label(),
            ],
            'status' => $request->session()->get('status'),
            'filters' => [
                'search' => $request->get('search', ''),
            ],
        ]);
    }

    /**
     * Show the form for creating a new setting.
     *
     * @return Response The Inertia response with create form
     */
    public function create(): Response
    {
        return Inertia::render('admin/Settings/Create', [
            'groups' => $this->groupOptions(),
        ]);
    }

    /**
     * Store a newly created setting.
     *
     * @param  SettingStoreRequest  $request  The validated request
     * @return RedirectResponse Redirect to admin settings page
     */
    public function store(SettingStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Handle checkbox values
        if ($data['field_type'] === 'checkbox' && isset($data['value'])) {
            $data['value'] = filter_var($data['value'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        // Set default role to 'user' if not provided
        $data['role'] ??= SettingRole::User->value;

        $setting = $this->settingsService->create($data);

        if (class_exists(\App\Models\AuditLog::class)) {
            \App\Models\AuditLog::log('setting.created', $setting, null, [
                'key' => $setting->key,
                'value' => $setting->value,
            ]);
        }

        return redirect()->route('admin.settings.index')->with('status', 'Setting created successfully.');
    }

    /**
     * Show the form for editing a setting.
     *
     * @param  Setting  $setting  The setting to edit
     * @return Response The Inertia response with edit form
     */
    public function edit(Setting $setting): Response
    {
        return Inertia::render('admin/Settings/Edit', [
            'setting' => $this->mapSetting($setting),
            'groups' => $this->groupOptions(),
        ]);
    }

    /**
     * Update a specific setting.
     *
     * @param  SettingUpdateRequest  $request  The validated request
     * @param  Setting  $setting  The setting to update
     * @return RedirectResponse Redirect to admin settings page
     */
    public function update(SettingUpdateRequest $request, Setting $setting): RedirectResponse
    {
        $validated = $request->validated();

        // Prevent changing role of system settings
        if ($setting->role === SettingRole::System && isset($validated['role']) && $validated['role'] !== SettingRole::System->value) {
            $validated['role'] = SettingRole::System->value;
        }

        // Handle checkbox values
        if ($validated['field_type'] === 'checkbox' && isset($validated['value'])) {
            $validated['value'] = filter_var($validated['value'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        $oldValues = ['key' => $setting->key, 'value' => $setting->value];

        $this->settingsService->update($setting->id, $validated);

        $setting->refresh();

        if (class_exists(\App\Models\AuditLog::class)) {
            \App\Models\AuditLog::log('setting.updated', $setting, $oldValues, [
                'key' => $setting->key,
                'value' => $setting->value,
            ]);
        }

        return redirect()->route('admin.settings.index')->with('status', 'Setting updated successfully.');
    }

    /**
     * Update multiple settings at once (bulk update).
     *
     * @param  SettingsUpdateRequest  $request  The validated request
     * @return RedirectResponse Redirect to admin settings page
     */
    public function bulkUpdate(SettingsUpdateRequest $request): RedirectResponse
    {
        // Handle checkbox values - if it's a boolean or '1'/'0', convert properly
        $settings = array_map(fn (mixed $value): mixed => match (true) {
            is_bool($value), $value === 'true', $value === 'false', $value === '1', $value === '0' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        }, $request->validated()['settings']);

        $this->settingsService->updateExisting($settings);

        return redirect()->route('admin.settings.index')->with('status', 'Settings updated successfully.');
    }

    /**
     * Remove the specified setting.
     *
     * @param  Setting  $setting  The setting to delete
     * @return RedirectResponse Redirect to admin settings page
     */
    public function destroy(Setting $setting): RedirectResponse
    {
        try {
            if (class_exists(\App\Models\AuditLog::class)) {
                \App\Models\AuditLog::log('setting.deleted', $setting, [
                    'key' => $setting->key,
                    'value' => $setting->value,
                ]);
            }

            $this->settingsService->delete($setting->id);

            return redirect()->route('admin.settings.index')->with('status', 'Setting deleted successfully.');
        } catch (Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', $e->getMessage());
        }
    }
}
