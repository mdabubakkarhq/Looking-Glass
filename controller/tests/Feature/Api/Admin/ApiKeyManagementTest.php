<?php

namespace Tests\Feature\Api\Admin;

use App\Models\ApiKey;
use App\Models\User;
use Tests\TestCase;

class ApiKeyManagementTest extends TestCase
{
    public function test_list_api_keys_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/api-keys')->assertStatus(401);
    }

    public function test_list_api_keys(): void
    {
        $admin = $this->actingAsAdmin();
        ApiKey::factory()->count(2)->create(['user_id' => $admin->id]);

        $response = $this->getJson('/api/v1/admin/api-keys');
        $response->assertOk()->assertJsonPath('total', 2);
    }

    public function test_store_api_key(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/admin/api-keys', [
            'name' => 'Test Key', 'abilities' => ['*'],
            'rate_limit_per_minute' => 60,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data', 'token', 'message']);
    }

    public function test_store_api_key_validates(): void
    {
        $this->actingAsAdmin();
        $this->postJson('/api/v1/admin/api-keys', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_show_api_key(): void
    {
        $admin = $this->actingAsAdmin();
        $key = ApiKey::factory()->create(['user_id' => $admin->id]);

        $response = $this->getJson("/api/v1/admin/api-keys/{$key->id}");
        $response->assertOk()->assertJsonPath('data.id', $key->id);
    }

    public function test_update_api_key(): void
    {
        $admin = $this->actingAsAdmin();
        $key = ApiKey::factory()->create(['user_id' => $admin->id, 'name' => 'Old Name']);

        $response = $this->putJson("/api/v1/admin/api-keys/{$key->id}", ['name' => 'New Name']);
        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_destroy_api_key(): void
    {
        $admin = $this->actingAsAdmin();
        $key = ApiKey::factory()->create(['user_id' => $admin->id]);

        $this->deleteJson("/api/v1/admin/api-keys/{$key->id}")
            ->assertOk()
            ->assertJsonPath('message', 'API key revoked');
    }
}
