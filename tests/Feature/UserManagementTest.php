<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_list_and_search_users(): void
    {
        User::factory()->create(['name' => 'Alice Storekeeper']);
        User::factory()->create(['name' => 'Bob Packer']);

        $this->actingAs($this->admin)->get('/users?search=Alice')
            ->assertOk()
            ->assertSee('Alice Storekeeper')
            ->assertDontSee('Bob Packer');
    }

    public function test_admin_can_create_a_user_with_a_hashed_password(): void
    {
        $this->actingAs($this->admin)->post('/users', [
            'name' => 'New Staff',
            'email' => 'new.staff@example.com',
            'role' => 'staff',
            'password' => 'StaffPass12345',
            'password_confirmation' => 'StaffPass12345',
        ])->assertRedirect('/users')->assertSessionHasNoErrors();

        $user = User::where('email', 'new.staff@example.com')->sole();
        $this->assertSame(UserRole::Staff, $user->role);
        $this->assertTrue(Hash::check('StaffPass12345', $user->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.created', 'subject_id' => $user->id]);
    }

    public function test_user_creation_is_validated(): void
    {
        $this->actingAs($this->admin)->post('/users', [
            'name' => '',
            'email' => $this->admin->email,
            'role' => 'superuser',
            'password' => 'weak',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'role', 'password']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_can_change_another_users_role_and_password(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($this->admin)->put("/users/{$staff->id}", [
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => 'admin',
            'password' => 'ResetByAdmin123',
            'password_confirmation' => 'ResetByAdmin123',
        ])->assertRedirect('/users');

        $staff->refresh();
        $this->assertSame(UserRole::Admin, $staff->role);
        $this->assertTrue(Hash::check('ResetByAdmin123', $staff->password));
    }

    public function test_update_log_does_not_contain_the_password_hash(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($this->admin)->put("/users/{$staff->id}", [
            'name' => 'Renamed',
            'email' => $staff->email,
            'role' => 'staff',
            'password' => 'ResetByAdmin123',
            'password_confirmation' => 'ResetByAdmin123',
        ]);

        $properties = json_encode(ActivityLog::where('action', 'user.updated')->sole()->properties);

        $this->assertStringContainsString('Renamed', $properties);
        $this->assertStringNotContainsString('password":"', $properties);
        $this->assertStringNotContainsString($staff->fresh()->password, $properties);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $this->actingAs($this->admin)->put("/users/{$this->admin->id}", [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => 'staff',
        ])->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Admin, $this->admin->fresh()->role);
    }

    public function test_admin_can_deactivate_and_reactivate_another_user(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($this->admin)->patch("/users/{$staff->id}/status")->assertRedirect();
        $this->assertFalse($staff->fresh()->is_active);

        $this->actingAs($this->admin)->patch("/users/{$staff->id}/status");
        $this->assertTrue($staff->fresh()->is_active);

        $this->assertDatabaseHas('activity_logs', ['action' => 'user.deactivated', 'subject_id' => $staff->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.activated', 'subject_id' => $staff->id]);
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $this->actingAs($this->admin)->patch("/users/{$this->admin->id}/status")->assertForbidden();

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_staff_cannot_access_any_user_management_route(): void
    {
        $staff = User::factory()->create();
        $otherUser = User::factory()->create();
        $payload = ['name' => 'Hacked', 'email' => $otherUser->email, 'role' => 'admin'];

        $this->actingAs($staff)->get('/users')->assertForbidden();
        $this->actingAs($staff)->get('/users/create')->assertForbidden();
        $this->actingAs($staff)->post('/users', [...$payload, 'email' => 'x@example.com'])->assertForbidden();
        $this->actingAs($staff)->get("/users/{$otherUser->id}/edit")->assertForbidden();
        $this->actingAs($staff)->put("/users/{$otherUser->id}", $payload)->assertForbidden();
        $this->actingAs($staff)->patch("/users/{$otherUser->id}/status")->assertForbidden();

        // Staff also cannot promote themselves through the admin route (IDOR on their own ID).
        $this->actingAs($staff)->put("/users/{$staff->id}", [...$payload, 'email' => $staff->email])->assertForbidden();

        $this->assertSame('staff', $staff->fresh()->role->value);
        $this->assertNotSame('Hacked', $otherUser->fresh()->name);
        $this->assertTrue($otherUser->fresh()->is_active);
    }

    public function test_users_link_is_hidden_from_staff(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertDontSee(route('users.index'));
        $this->actingAs($this->admin)->get('/')->assertSee(route('users.index'));
    }
}
