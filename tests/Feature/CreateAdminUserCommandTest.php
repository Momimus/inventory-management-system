<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_administrator(): void
    {
        $this->artisan('users:create-admin')
            ->expectsQuestion('Name', 'Store Owner')
            ->expectsQuestion('Email address', 'owner@example.com')
            ->expectsQuestion('Password', 'OwnerPass12345')
            ->assertSuccessful();

        $admin = User::where('email', 'owner@example.com')->sole();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertNotSame('OwnerPass12345', $admin->password);
    }

    public function test_it_rejects_weak_passwords(): void
    {
        $this->artisan('users:create-admin')
            ->expectsQuestion('Name', 'Store Owner')
            ->expectsQuestion('Email address', 'owner@example.com')
            ->expectsQuestion('Password', 'weak')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
