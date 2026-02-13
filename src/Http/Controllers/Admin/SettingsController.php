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
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin settings controller.
 *
 * Handles displaying and updating all application settings.
 * Middleware is applied via route definitions in routes/admin.php.
 */
final class SettingsController
{
    /**
     * Create a new admin settings controller instance.
     */
    public function __construct(
        private(set) SettingsService $settingsService,
    ) {}

    /**
     * Check if user has admin or super-admin role.
     */
    private function authorizeAdmin(): void
    {
        $user = auth()->user();

        if (!$user || !array_any(['super-admin', 'admin'], fn (string $role): bool => $user->hasRole($role))) {
            abort(403, 'Unauthorized. Admin access required.');
        }
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
            'id' => $setting->id,
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
        $this->authorizeAdmin();

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
        $this->authorizeAdmin();

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
        $this->authorizeAdmin();

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
        $this->authorizeAdmin();

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
        $this->authorizeAdmin();

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
        $this->authorizeAdmin();

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
        $this->authorizeAdmin();

        $settings = $request->validated()['settings'];

        foreach ($settings as $key => $value) {
            // Handle checkbox values - if it's a boolean or '1'/'0', convert properly
            $value = match (true) {
                is_bool($value), $value === 'true', $value === 'false', $value === '1', $value === '0' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                default => $value,
            };
            $this->settingsService->set($key, $value);
        }

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
        $this->authorizeAdmin();

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
