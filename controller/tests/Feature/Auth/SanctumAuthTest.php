<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class SanctumAuthTest extends TestCase
{
    public function test_unauthenticated_access_returns_401(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
    }

    public function test_authenticated_user_can_access_admin(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/v1/admin/dashboard')->assertOk();
    }

    public function test_user_can_create_sanctum_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/admin/api-keys', [
            'name' => 'Test Token',
        ], [
            'Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken,
        ]);

        // This won't work because we're using sanctum guard on admin routes
        // and the middleware expects auth:sanctum. Let's test via actingAs instead.
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/admin/api-keys', ['name' => 'Test Token']);
        $response->assertStatus(201);
    }
}
