<?php

namespace Tests\Feature\Api;

use App\Models\Node;
use App\Models\NodeCapability;
use App\Models\Setting;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    public function test_get_config(): void
    {
        Setting::factory()->public()->create([
            'group' => 'branding', 'key' => 'site_name',
            'value' => 'Test LG', 'type' => 'string',
        ]);

        $response = $this->getJson('/api/v1/config');

        $response->assertOk()
            ->assertJsonStructure([
                'site_name', 'organization', 'test_types',
                'ip_families', 'supported_features', 'download_sizes',
            ]);
    }

    public function test_list_public_nodes(): void
    {
        $node = Node::factory()->create(['public' => true, 'status' => 'online']);
        NodeCapability::factory()->create(['node_id' => $node->id, 'feature' => 'ping', 'enabled' => true]);
        Node::factory()->private()->create();

        $response = $this->getJson('/api/v1/nodes');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'uuid', 'name', 'city', 'country_code',
                        'provider', 'asn', 'ipv4', 'status', 'capabilities',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_list_nodes_excludes_offline(): void
    {
        Node::factory()->create(['public' => true, 'status' => 'online', 'maintenance' => false]);
        Node::factory()->offline()->create(['public' => true, 'maintenance' => false]);

        $response = $this->getJson('/api/v1/nodes');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_show_node_by_slug(): void
    {
        $node = Node::factory()->create(['public' => true, 'slug' => 'test-node-1']);
        NodeCapability::factory()->create(['node_id' => $node->id, 'feature' => 'ping', 'enabled' => true]);

        $response = $this->getJson('/api/v1/nodes/test-node-1');

        $response->assertOk()
            ->assertJsonPath('data.id', 'test-node-1')
            ->assertJsonStructure(['data' => ['id', 'name', 'status', 'capabilities']]);
    }

    public function test_show_private_node_returns_404(): void
    {
        Node::factory()->private()->create(['slug' => 'hidden-node']);
        $this->getJson('/api/v1/nodes/hidden-node')->assertNotFound();
    }

    public function test_show_nonexistent_node_returns_404(): void
    {
        $this->getJson('/api/v1/nodes/does-not-exist')->assertNotFound();
    }

    public function test_get_latency(): void
    {
        Node::factory()->create(['public' => true, 'status' => 'online']);

        $response = $this->getJson('/api/v1/nodes/latency');

        $response->assertOk()
            ->assertJsonStructure([
                'generated_at',
                'nodes' => [['id', 'name', 'location', 'status']],
            ]);
    }

    public function test_health_endpoint(): void
    {
        $this->getJson('/up')->assertOk()->assertJson(['status' => 'ok']);
    }
}
