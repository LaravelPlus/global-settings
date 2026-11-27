<?php

declare(strict_types=1);

namespace LaravelPlus\GlobalSettings\Tests\Feature\Admin;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use LaravelPlus\GlobalSettings\Tests\TestCase;

/** A host user model without spatie/laravel-permission. */
final class PlainUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}

final class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function plainUser(string $email = 'plain@example.com'): PlainUser
    {
        return PlainUser::create(['name' => 'Plain', 'email' => $email, 'password' => 'secret']);
    }

    public function test_host_without_spatie_and_no_gate_denies_with_404(): void
    {
        $this->actingAs($this->plainUser())->get(route('admin.settings.index'))->assertNotFound();
    }

    public function test_host_gate_grants_access_without_spatie(): void
    {
        Gate::define('global-settings.admin', fn ($user): bool => $user->email === 'boss@example.com');

        $this->actingAs($this->plainUser('boss@example.com'))->get(route('admin.settings.index'))->assertOk();
        $this->actingAs($this->plainUser('other@example.com'))->get(route('admin.settings.index'))->assertNotFound();
    }
}
