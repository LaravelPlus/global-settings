<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Database\Seeders;

use LaravelPlus\GlobalSettings\Enums\SettingRole;
use LaravelPlus\GlobalSettings\Models\Setting;
use Illuminate\Database\Seeder;

final class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Authentication & Registration Settings
        Setting::updateOrCreate(
            ['key' => 'registration_enabled'],
            [
                'value' => '1',
                'field_type' => 'checkbox',
                'label' => 'Allow New User Registration',
                'description' => 'Enable or disable public user registration. When disabled, only administrators can create new user accounts.',
                'role' => SettingRole::System->value,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'email_verification_required'],
            [
                'value' => '1',
                'field_type' => 'checkbox',
                'label' => 'Require Email Verification',
                'description' => 'Require users to verify their email address before accessing the application.',
                'role' => SettingRole::System->value,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'two_factor_authentication_required'],
            [
                'value' => '0',
                'field_type' => 'checkbox',
                'label' => 'Require Two-Factor Authentication',
                'description' => 'Force all users to enable two-factor authentication.',
                'role' => SettingRole::System->value,
            ]
        );

        // Site Configuration
        Setting::updateOrCreate(
            ['key' => 'site_name'],
            [
                'value' => config('app.name', 'Laravel'),
                'field_type' => 'input',
                'label' => 'Site Name',
                'description' => 'The name of your application displayed throughout the site.',
                'role' => SettingRole::System->value,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'site_description'],
            [
                'value' => 'A modern Laravel starter kit with Vue 3 and Inertia.js',
                'field_type' => 'input',
                'label' => 'Site Description',
                'description' => 'A brief description of your application for SEO and display purposes.',
                'role' => SettingRole::System->value,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'contact_email'],
            [
                'value' => config('mail.from.address', 'hello@example.com'),
                'field_type' => 'input',
                'label' => 'Contact Email',
                'description' => 'Primary contact email address for support and inquiries.',
                'role' => SettingRole::System->value,
            ]
        );

        // Security Settings
        Setting::updateOrCreate(
            ['key' => 'session_timeout'],
            [
                'value' => '120',
                'field_type' => 'input',
                'label' => 'Session Timeout (minutes)',
                'description' => 'Automatically log out users after this many minutes of inactivity.',
                'role' => SettingRole::System->value,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'password_expires_days'],
            [
                'value' => '0',
                'field_type' => 'input',
                'label' => 'Password Expiry (days)',
                'description' => 'Force users to change password after this many days. Set to 0 to disable.',
                'role' => SettingRole::System->value,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'max_login_attempts'],
            [
                'value' => '5',
                'field_type' => 'input',
                'label' => 'Maximum Login Attempts',
                'description' => 'Number of failed login attempts before account lockout.',
                'role' => SettingRole::System->value,
            ]
        );

        // Feature Flags
        Setting::updateOrCreate(
            ['key' => 'maintenance_mode'],
            [
                'value' => '0',
                'field_type' => 'checkbox',
                'label' => 'Maintenance Mode',
                'description' => 'Put the application in maintenance mode. Only administrators can access.',
                'role' => SettingRole::System->value,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'user_impersonation_enabled'],
            [
                'value' => '1',
                'field_type' => 'checkbox',
                'label' => 'Enable User Impersonation',
                'description' => 'Allow administrators to impersonate other users for support purposes.',
                'role' => SettingRole::System->value,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'database_browser_enabled'],
            [
                'value' => '1',
                'field_type' => 'checkbox',
                'label' => 'Enable Database Browser',
                'description' => 'Allow administrators to browse database tables and data.',
                'role' => SettingRole::System->value,
            ]
        );

        // Email Settings
        Setting::updateOrCreate(
            ['key' => 'send_welcome_email'],
            [
                'value' => '1',
                'field_type' => 'checkbox',
                'label' => 'Send Welcome Email',
                'description' => 'Send a welcome email to new users after registration.',
                'role' => SettingRole::System->value,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'notify_admin_new_user'],
            [
                'value' => '0',
                'field_type' => 'checkbox',
                'label' => 'Notify Admin of New Users',
                'description' => 'Send email notification to administrators when new users register.',
                'role' => SettingRole::System->value,
            ]
        );

        // User Settings
        Setting::updateOrCreate(
            ['key' => 'default_timezone'],
            [
                'value' => 'UTC',
                'field_type' => 'input',
                'label' => 'Default Timezone',
                'description' => 'Default timezone for the application and new users.',
                'role' => SettingRole::System->value,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'items_per_page'],
            [
                'value' => '15',
                'field_type' => 'input',
                'label' => 'Items Per Page',
                'description' => 'Default number of items to display per page in lists and tables.',
                'role' => SettingRole::System->value,
            ]
        );

        // Appearance
        Setting::updateOrCreate(
            ['key' => 'auth_layout'],
            [
                'value' => 'simple',
                'field_type' => 'multioptions',
                'options' => json_encode(['simple', 'split']),
                'label' => 'Auth Page Layout',
                'description' => 'Choose the layout for authentication pages. "Simple" shows a centered card, "Split" shows a branded panel alongside the form.',
                'role' => SettingRole::System->value,
            ]
        );

        // GDPR & Privacy
        Setting::updateOrCreate(
            ['key' => 'cookie_consent_required'],
            [
                'value' => '1',
                'field_type' => 'checkbox',
                'label' => 'Require Cookie Consent',
                'description' => 'Show cookie consent banner and require user consent.',
                'role' => SettingRole::System->value,
            ]
        );

        Setting::updateOrCreate(
            ['key' => 'data_retention_days'],
            [
                'value' => '365',
                'field_type' => 'input',
                'label' => 'Data Retention Period (days)',
                'description' => 'Number of days to retain user data after account deletion.',
                'role' => SettingRole::System->value,
            ]
        );
    }
}
