<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Node;
use App\Models\NodeCapability;
use Tests\TestCase;

class NodeManagementTest extends TestCase
{
    public function test_list_nodes_requires_auth(): void
    {
        $this->getJson('/api/v1/admin/nodes')->assertStatus(401);
    }

    public function test_list_nodes_returns_paginated(): void
    {
        $this->actingAsAdmin();
        Node::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/nodes');
        $response->assertOk()->assertJsonPath('total', 3);
    }

    public function test_store_node(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/admin/nodes', [
            'name' => 'Test Node', 'city' => 'Tokyo',
            'country_code' => 'JP', 'provider' => 'AWS',
            'ipv4' => '203.0.113.50', 'public' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Node');

        $this->assertDatabaseHas('nodes', ['name' => 'Test Node', 'city' => 'Tokyo']);
        $this->assertDatabaseHas('node_capabilities', ['feature' => 'ping']);
    }

    public function test_store_node_validates_required(): void
    {
        $this->actingAsAdmin();
        $this->postJson('/api/v1/admin/nodes', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_show_node(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();

        $response = $this->getJson("/api/v1/admin/nodes/{$node->id}");
        $response->assertOk()->assertJsonPath('data.id', $node->id);
    }

    public function test_update_node(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/v1/admin/nodes/{$node->id}", [
            'name' => 'New Name', 'status' => 'maintenance',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
        $this->assertDatabaseHas('nodes', ['id' => $node->id, 'name' => 'New Name']);
    }

    public function test_destroy_node(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();

        $this->deleteJson("/api/v1/admin/nodes/{$node->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Node deleted');
    }

    public function test_generate_registration_token(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();

        $response = $this->postJson("/api/v1/admin/nodes/{$node->id}/generate-token");
        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'expires_at', 'node_id']]);
    }

    public function test_toggle_maintenance(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create(['maintenance' => false]);

        $response = $this->postJson("/api/v1/admin/nodes/{$node->id}/maintenance");
        $response->assertOk()->assertJsonPath('data.maintenance', true);
    }

    public function test_rotate_credentials(): void
    {
        $this->actingAsAdmin();
        $node = Node::factory()->create();

        $response = $this->postJson("/api/v1/admin/nodes/{$node->id}/rotate-credentials");
        $response->assertOk()
            ->assertJsonStructure(['data' => ['node_key_id', 'message']]);
    }
}
