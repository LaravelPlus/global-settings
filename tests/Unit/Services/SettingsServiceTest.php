<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Tests\Unit\Services;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelPlus\GlobalSettings\Enums\SettingRole;
use LaravelPlus\GlobalSettings\Models\Setting;
use LaravelPlus\GlobalSettings\Services\SettingsService;
use LaravelPlus\GlobalSettings\Tests\TestCase;

final class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SettingsService::class);
    }

    public function test_get_returns_setting_value(): void
    {
        Setting::create([
            'key' => 'test_key',
            'value' => 'test_value',
            'field_type' => 'input',
            'role' => SettingRole::User->value,
        ]);

        $this->assertEquals('test_value', $this->service->get('test_key'));
    }

    public function test_get_returns_default_when_not_found(): void
    {
        $this->assertEquals('default', $this->service->get('nonexistent', 'default'));
    }

    public function test_set_creates_or_updates_setting(): void
    {
        Setting::create([
            'key' => 'test_key',
            'value' => 'old_value',
            'field_type' => 'input',
            'role' => SettingRole::User->value,
        ]);

        $this->service->set('test_key', 'new_value');

        $this->assertEquals('new_value', Setting::where('key', 'test_key')->first()->value);
    }

    public function test_has_returns_true_for_existing_setting(): void
    {
        Setting::create([
            'key' => 'exists',
            'value' => 'yes',
            'field_type' => 'input',
            'role' => SettingRole::User->value,
        ]);

        $this->assertTrue($this->service->has('exists'));
    }

    public function test_has_returns_false_for_missing_setting(): void
    {
        $this->assertFalse($this->service->has('missing'));
    }

    public function test_create_setting(): void
    {
        $setting = $this->service->create([
            'key' => 'new_setting',
            'value' => 'some_value',
            'field_type' => 'input',
            'role' => SettingRole::User->value,
        ]);

        $this->assertInstanceOf(Setting::class, $setting);
        $this->assertEquals('new_setting', $setting->key);
        $this->assertDatabaseHas('settings', ['key' => 'new_setting']);
    }

    public function test_create_setting_validates_unique_key(): void
    {
        Setting::create([
            'key' => 'duplicate',
            'value' => 'first',
            'field_type' => 'input',
            'role' => SettingRole::User->value,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service->create([
            'key' => 'duplicate',
            'value' => 'second',
            'field_type' => 'input',
            'role' => SettingRole::User->value,
        ]);
    }

    public function test_update_setting(): void
    {
        $setting = Setting::create([
            'key' => 'update_me',
            'value' => 'old',
            'field_type' => 'input',
            'role' => SettingRole::User->value,
        ]);

        $this->service->update($setting->id, [
            'key' => 'update_me',
            'value' => 'new',
            'field_type' => 'input',
            'role' => SettingRole::User->value,
        ]);

        $this->assertEquals('new', $setting->fresh()->value);
    }

    public function test_delete_setting(): void
    {
        $setting = Setting::create([
            'key' => 'delete_me',
            'value' => 'bye',
            'field_type' => 'input',
            'role' => SettingRole::User->value,
        ]);

        $this->service->delete($setting->id);

        $this->assertDatabaseMissing('settings', ['key' => 'delete_me']);
    }

    public function test_delete_system_setting_throws_exception(): void
    {
        $setting = Setting::create([
            'key' => 'system_setting',
            'value' => 'protected',
            'field_type' => 'input',
            'role' => SettingRole::System->value,
        ]);

        $this->expectException(Exception::class);

        $this->service->delete($setting->id);
    }

    public function test_all_returns_all_settings(): void
    {
        Setting::create(['key' => 'a', 'value' => '1', 'field_type' => 'input', 'role' => SettingRole::User->value]);
        Setting::create(['key' => 'b', 'value' => '2', 'field_type' => 'input', 'role' => SettingRole::User->value]);

        $result = $this->service->all();

        $this->assertCount(2, $result);
    }

    public function test_search_finds_by_key(): void
    {
        Setting::create(['key' => 'app_name', 'value' => 'My App', 'field_type' => 'input', 'role' => SettingRole::User->value]);
        Setting::create(['key' => 'app_debug', 'value' => '1', 'field_type' => 'checkbox', 'role' => SettingRole::User->value]);
        Setting::create(['key' => 'site_url', 'value' => 'https://example.com', 'field_type' => 'input', 'role' => SettingRole::User->value]);

        $result = $this->service->search('app');

        $this->assertCount(2, $result);
    }

    public function test_get_by_role_returns_filtered_settings(): void
    {
        Setting::create(['key' => 'system_key', 'value' => 's', 'field_type' => 'input', 'role' => SettingRole::System->value]);
        Setting::create(['key' => 'user_key', 'value' => 'u', 'field_type' => 'input', 'role' => SettingRole::User->value]);

        $systemSettings = $this->service->getByRole(SettingRole::System->value);

        $this->assertCount(1, $systemSettings);
        $this->assertEquals('system_key', $systemSettings->first()->key);
    }
}
