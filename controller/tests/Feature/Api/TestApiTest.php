<?php

namespace Tests\Feature\Api;

use App\Models\NetworkTest;
use App\Models\Node;
use App\Models\NodeCapability;
use App\Models\NodeCredential;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TestApiTest extends TestCase
{
    public function test_store_test_creates_pending_test(): void
    {
        Http::fake(); // Mock agent dispatch

        $node = Node::factory()->create(['public' => true, 'status' => 'online']);
        NodeCapability::factory()->create([
            'node_id' => $node->id, 'feature' => 'ping', 'enabled' => true,
            'max_concurrent' => 20, 'timeout_seconds' => 30,
        ]);
        NodeCredential::factory()->create(['node_id' => $node->id]);

        $response = $this->postJson('/api/v1/tests', [
            'node_id' => $node->slug,
            'test_type' => 'ping',
            'target' => '1.1.1.1',
            'ip_family' => 'auto',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'node_id', 'test_type', 'target',
                    'ip_family', 'status', 'stream_url', 'created_at',
                ],
            ])
            ->assertJsonPath('data.status', 'running')
            ->assertJsonPath('data.node_id', $node->slug);
    }

    public function test_store_test_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/tests', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['node_id', 'test_type', 'target']);
    }

    public function test_store_test_validates_test_type(): void
    {
        $node = Node::factory()->create(['public' => true, 'status' => 'online']);

        $response = $this->postJson('/api/v1/tests', [
            'node_id' => $node->slug,
            'test_type' => 'invalid_type',
            'target' => '1.1.1.1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['test_type']);
    }

    public function test_store_test_validates_node_exists(): void
    {
        $response = $this->postJson('/api/v1/tests', [
            'node_id' => 'nonexistent-node',
            'test_type' => 'ping',
            'target' => '1.1.1.1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['node_id']);
    }

    public function test_store_test_blocks_private_ip(): void
    {
        $node = Node::factory()->create(['public' => true, 'status' => 'online']);
        NodeCapability::factory()->create([
            'node_id' => $node->id, 'feature' => 'ping', 'enabled' => true,
        ]);

        $response = $this->postJson('/api/v1/tests', [
            'node_id' => $node->slug,
            'test_type' => 'ping',
            'target' => '192.168.1.1',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'invalid_target');
    }

    public function test_show_test(): void
    {
        $node = Node::factory()->create();
        $test = NetworkTest::factory()->completed()->create(['node_id' => $node->id]);

        $response = $this->getJson("/api/v1/tests/{$test->uuid}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'node_id', 'test_type', 'target', 'status',
                    'started_at', 'completed_at', 'runtime_ms',
                    'packet_loss', 'latency_min', 'latency_avg', 'latency_max',
                ],
            ])
            ->assertJsonPath('data.id', $test->uuid);
    }

    public function test_show_nonexistent_test_returns_404(): void
    {
        $this->getJson('/api/v1/tests/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    public function test_stream_returns_sse_headers(): void
    {
        $node = Node::factory()->create();
        $test = NetworkTest::factory()->completed()->create(['node_id' => $node->id]);

        $response = $this->get("/api/v1/tests/{$test->uuid}/stream");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
    }
}
