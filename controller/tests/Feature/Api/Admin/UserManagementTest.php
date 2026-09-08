<?php

namespace Tests\Feature\Api\Admin;

use App\Models\User;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_list_users_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/users')->assertStatus(401);
    }

    public function test_list_users(): void
    {
        $this->actingAsAdmin();
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/users');
        $response->assertOk()->assertJsonPath('total', 4); // 3 + admin
    }

    public function test_store_user(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/admin/users', [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)->assertJsonPath('data.email', 'newuser@test.com');
        $this->assertDatabaseHas('users', ['email' => 'newuser@test.com']);
    }

    public function test_store_user_validates(): void
    {
        $this->actingAsAdmin();
        $this->postJson('/api/v1/admin/users', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_show_user(): void
    {
        $admin = $this->actingAsAdmin();
        $user = User::factory()->create();

        $response = $this->getJson("/api/v1/admin/users/{$user->id}");
        $response->assertOk()->assertJsonPath('data.email', $user->email);
    }

    public function test_update_user(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/v1/admin/users/{$user->id}", ['name' => 'New Name']);
        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_destroy_user(): void
    {
        $admin = $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->deleteJson("/api/v1/admin/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('message', 'User deleted');
    }

    public function test_cannot_delete_self(): void
    {
        $admin = $this->actingAsAdmin();

        $this->deleteJson("/api/v1/admin/users/{$admin->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete your own account.');
    }
}
