<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelPlus\GlobalSettings\Enums\SettingRole;
use LaravelPlus\GlobalSettings\Models\Setting;
use LaravelPlus\GlobalSettings\Tests\TestCase;
use LaravelPlus\GlobalSettings\Tests\User;
use Spatie\Permission\Models\Role;

final class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        // Create users with roles
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    // ========================================
    // AUTHORIZATION TESTS
    // ========================================

    public function test_guests_cannot_access_settings_index(): void
    {
        $response = $this->get(route('admin.settings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_users_cannot_access_settings_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.settings.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_settings_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));

        $response->assertOk();
    }

    public function test_super_admin_can_access_settings_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.settings.index'));

        $response->assertOk();
    }

    // ========================================
    // INDEX PAGE TESTS
    // ========================================

    public function test_settings_index_displays_all_settings(): void
    {
        Setting::create([
            'key' => 'test_setting_1',
            'value' => 'value1',
            'label' => 'Test Setting 1',
            'role' => SettingRole::User->value,
        ]);

        Setting::create([
            'key' => 'test_setting_2',
            'value' => 'value2',
            'label' => 'Test Setting 2',
            'role' => SettingRole::System->value,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Settings')
            ->has('settings', 2)
            ->where('settings.0.key', 'test_setting_1')
            ->where('settings.1.key', 'test_setting_2')
        );
    }

    public function test_settings_index_search_filters_by_key(): void
    {
        Setting::create(['key' => 'email_enabled', 'value' => '1']);
        Setting::create(['key' => 'sms_enabled', 'value' => '0']);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.settings.index', ['search' => 'email']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('settings', 1)
            ->where('settings.0.key', 'email_enabled')
        );
    }

    public function test_settings_index_search_filters_by_label(): void
    {
        Setting::create([
            'key' => 'setting1',
            'value' => '1',
            'label' => 'Email Configuration',
        ]);
        Setting::create([
            'key' => 'setting2',
            'value' => '0',
            'label' => 'SMS Configuration',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.settings.index', ['search' => 'Email']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('settings', 1)
            ->where('settings.0.label', 'Email Configuration')
        );
    }

    public function test_settings_index_search_filters_by_value(): void
    {
        Setting::create(['key' => 'api_key', 'value' => 'secret123']);
        Setting::create(['key' => 'db_name', 'value' => 'production']);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.settings.index', ['search' => 'secret']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('settings', 1));
    }

    // ========================================
    // CREATE TESTS
    // ========================================

    public function test_admin_can_access_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('admin/Settings/Create'));
    }

    public function test_regular_user_cannot_access_create_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.settings.create'));

        $response->assertStatus(403);
    }

    // ========================================
    // STORE TESTS
    // ========================================

    public function test_admin_can_create_new_setting(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.store'), [
            'key' => 'new_setting',
            'value' => 'test_value',
            'field_type' => 'input',
            'label' => 'New Setting',
            'description' => 'A test setting',
            'role' => SettingRole::User->value,
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('status', 'Setting created successfully.');

        $this->assertDatabaseHas('settings', [
            'key' => 'new_setting',
            'value' => 'test_value',
        ]);
    }

    public function test_store_handles_checkbox_field_type(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.store'), [
            'key' => 'checkbox_setting',
            'value' => true,
            'field_type' => 'checkbox',
            'label' => 'Checkbox Setting',
        ]);

        $response->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('settings', [
            'key' => 'checkbox_setting',
            'value' => '1',
            'field_type' => 'checkbox',
        ]);
    }

    public function test_store_sets_default_role_to_user(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.store'), [
            'key' => 'role_test',
            'value' => 'value',
            'field_type' => 'input',
            'label' => 'Role Test',
        ]);

        $response->assertRedirect(route('admin.settings.index'));

        $setting = Setting::where('key', 'role_test')->first();
        $this->assertEquals(SettingRole::User->value, $setting->role->value);
    }

    public function test_store_validates_unique_key(): void
    {
        Setting::create(['key' => 'existing_key', 'value' => 'value']);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.store'), [
            'key' => 'existing_key',
            'value' => 'new_value',
            'field_type' => 'input',
            'label' => 'Test',
        ]);

        $response->assertSessionHasErrors('key');
    }

    public function test_regular_user_cannot_create_setting(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.settings.store'), [
            'key' => 'unauthorized',
            'value' => 'value',
            'field_type' => 'input',
            'label' => 'Test',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('settings', ['key' => 'unauthorized']);
    }

    // ========================================
    // EDIT TESTS
    // ========================================

    public function test_admin_can_access_edit_form(): void
    {
        $setting = Setting::create([
            'key' => 'editable_setting',
            'value' => 'original',
            'label' => 'Editable Setting',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.settings.edit', $setting));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Settings/Edit')
            ->where('setting.key', 'editable_setting')
            ->where('setting.value', 'original')
        );
    }

    public function test_regular_user_cannot_access_edit_form(): void
    {
        $setting = Setting::create(['key' => 'test', 'value' => 'value']);

        $response = $this->actingAs($this->user)->get(route('admin.settings.edit', $setting));

        $response->assertStatus(403);
    }

    // ========================================
    // UPDATE TESTS
    // ========================================

    public function test_admin_can_update_setting(): void
    {
        $setting = Setting::create([
            'key' => 'updateable',
            'value' => 'old_value',
            'field_type' => 'input',
            'label' => 'Test',
            'role' => SettingRole::User->value,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.settings.update', $setting), [
            'key' => 'updateable',
            'value' => 'new_value',
            'field_type' => 'input',
            'label' => 'Updated Label',
            'role' => SettingRole::User->value,
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('status', 'Setting updated successfully.');

        $this->assertDatabaseHas('settings', [
            'key' => 'updateable',
            'value' => 'new_value',
            'label' => 'Updated Label',
        ]);
    }

    public function test_update_handles_checkbox_conversion(): void
    {
        $setting = Setting::create([
            'key' => 'checkbox_test',
            'value' => '0',
            'field_type' => 'checkbox',
            'role' => SettingRole::User->value,
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('admin.settings.update', $setting), [
            'key' => 'checkbox_test',
            'value' => 'true',
            'field_type' => 'checkbox',
            'role' => SettingRole::User->value,
        ]);

        $response->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('settings', [
            'key' => 'checkbox_test',
            'value' => '1',
        ]);
    }

    public function test_update_prevents_changing_system_setting_role(): void
    {
        $setting = Setting::create([
            'key' => 'system_setting',
            'value' => 'value',
            'field_type' => 'input',
            'role' => SettingRole::System->value,
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('admin.settings.update', $setting), [
            'key' => 'system_setting',
            'value' => 'new_value',
            'field_type' => 'input',
            'role' => SettingRole::User->value, // Try to change role
        ]);

        $response->assertRedirect(route('admin.settings.index'));

        $setting->refresh();
        $this->assertEquals(SettingRole::System->value, $setting->role->value);
    }

    public function test_update_validates_unique_key(): void
    {
        Setting::create(['key' => 'existing', 'value' => 'value']);
        $setting = Setting::create(['key' => 'to_update', 'value' => 'value']);

        $response = $this->actingAs($this->superAdmin)->put(route('admin.settings.update', $setting), [
            'key' => 'existing', // Try to use existing key
            'value' => 'value',
            'field_type' => 'input',
        ]);

        $response->assertSessionHasErrors('key');
    }

    public function test_update_allows_same_key(): void
    {
        $setting = Setting::create([
            'key' => 'my_key',
            'value' => 'old',
            'role' => SettingRole::User->value,
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('admin.settings.update', $setting), [
            'key' => 'my_key', // Same key
            'value' => 'new',
            'field_type' => 'input',
            'role' => SettingRole::User->value,
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $this->assertDatabaseHas('settings', ['key' => 'my_key', 'value' => 'new']);
    }

    public function test_regular_user_cannot_update_setting(): void
    {
        $setting = Setting::create(['key' => 'test', 'value' => 'original']);

        $response = $this->actingAs($this->user)->put(route('admin.settings.update', $setting), [
            'key' => 'test',
            'value' => 'hacked',
            'field_type' => 'input',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('settings', ['key' => 'test', 'value' => 'original']);
    }

    // ========================================
    // BULK UPDATE TESTS
    // ========================================

    public function test_admin_can_bulk_update_settings(): void
    {
        Setting::create(['key' => 'setting1', 'value' => 'old1']);
        Setting::create(['key' => 'setting2', 'value' => 'old2']);

        $response = $this->actingAs($this->admin)->patch(route('admin.settings.bulk-update'), [
            'settings' => [
                'setting1' => 'new1',
                'setting2' => 'new2',
            ],
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('status', 'Settings updated successfully.');

        $this->assertDatabaseHas('settings', ['key' => 'setting1', 'value' => 'new1']);
        $this->assertDatabaseHas('settings', ['key' => 'setting2', 'value' => 'new2']);
    }

    public function test_bulk_update_handles_boolean_values(): void
    {
        Setting::create(['key' => 'bool1', 'value' => '0', 'field_type' => 'checkbox']);
        Setting::create(['key' => 'bool2', 'value' => '1', 'field_type' => 'checkbox']);

        $response = $this->actingAs($this->superAdmin)->patch(route('admin.settings.bulk-update'), [
            'settings' => [
                'bool1' => true,
                'bool2' => '0',
            ],
        ]);

        $response->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'bool1', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['key' => 'bool2', 'value' => '0']);
    }

    public function test_regular_user_cannot_bulk_update(): void
    {
        Setting::create(['key' => 'protected', 'value' => 'original']);

        $response = $this->actingAs($this->user)->patch(route('admin.settings.bulk-update'), [
            'settings' => ['protected' => 'hacked'],
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('settings', ['key' => 'protected', 'value' => 'original']);
    }

    // ========================================
    // DELETE TESTS
    // ========================================

    public function test_admin_can_delete_user_setting(): void
    {
        $setting = Setting::create([
            'key' => 'deletable',
            'value' => 'value',
            'role' => SettingRole::User->value,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.settings.destroy', $setting));

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('status', 'Setting deleted successfully.');

        $this->assertDatabaseMissing('settings', ['key' => 'deletable']);
    }

    public function test_cannot_delete_system_setting(): void
    {
        $setting = Setting::create([
            'key' => 'system_setting',
            'value' => 'value',
            'role' => SettingRole::System->value,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.settings.destroy', $setting));

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('settings', ['key' => 'system_setting']);
    }

    public function test_regular_user_cannot_delete_setting(): void
    {
        $setting = Setting::create(['key' => 'test', 'value' => 'value']);

        $response = $this->actingAs($this->user)->delete(route('admin.settings.destroy', $setting));

        $response->assertStatus(403);
        $this->assertDatabaseHas('settings', ['key' => 'test']);
    }
}
