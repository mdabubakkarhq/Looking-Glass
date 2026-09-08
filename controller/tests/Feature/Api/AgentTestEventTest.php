<?php

namespace Tests\Feature\Api;

use App\Models\NetworkTest;
use App\Models\Node;
use App\Models\NodeCredential;
use Tests\TestCase;

class AgentTestEventTest extends TestCase
{
    public function test_agent_can_submit_test_event(): void
    {
        $node = Node::factory()->create();
        $credential = NodeCredential::factory()->create(['node_id' => $node->id]);
        $test = NetworkTest::factory()->running()->create(['node_id' => $node->id]);

        $payload = [
            'event_type' => 'output',
            'data' => ['line' => 'PING 1.1.1.1: 64 bytes, icmp_seq=1'],
            'occurred_at' => now()->toISOString(),
        ];

        $response = $this->postJson(
            "/api/v1/agent/tests/{$test->uuid}/events",
            $payload,
            $this->agentHeaders($credential, $payload),
        );

        $response->assertOk()->assertJson(['status' => 'ok']);
        $this->assertDatabaseHas('network_test_events', [
            'network_test_id' => $test->id, 'event_type' => 'output',
        ]);
    }

    public function test_agent_test_event_rejects_wrong_node(): void
    {
        $node1 = Node::factory()->create();
        $node2 = Node::factory()->create();
        $credential = NodeCredential::factory()->create(['node_id' => $node1->id]);
        $test = NetworkTest::factory()->running()->create(['node_id' => $node2->id]);

        $payload = ['event_type' => 'output', 'data' => []];
        $this->postJson(
            "/api/v1/agent/tests/{$test->uuid}/events",
            $payload,
            $this->agentHeaders($credential, $payload),
        )->assertStatus(403);
    }

    public function test_agent_can_mark_test_complete(): void
    {
        $node = Node::factory()->create();
        $credential = NodeCredential::factory()->create(['node_id' => $node->id]);
        $test = NetworkTest::factory()->running()->create(['node_id' => $node->id]);

        $payload = [
            'packet_loss' => 0.0, 'latency_min' => 1.2,
            'latency_avg' => 2.5, 'latency_max' => 5.1,
            'summary' => ['packets' => 10],
        ];

        $this->postJson(
            "/api/v1/agent/tests/{$test->uuid}/complete",
            $payload,
            $this->agentHeaders($credential, $payload),
        )->assertOk();

        $test->refresh();
        $this->assertEquals('completed', $test->status);
    }

    public function test_agent_can_mark_test_failed(): void
    {
        $node = Node::factory()->create();
        $credential = NodeCredential::factory()->create(['node_id' => $node->id]);
        $test = NetworkTest::factory()->running()->create(['node_id' => $node->id]);

        $payload = ['error_code' => 'timeout', 'error_message' => 'Test timed out.', 'summary' => []];

        $this->postJson(
            "/api/v1/agent/tests/{$test->uuid}/complete",
            $payload,
            $this->agentHeaders($credential, $payload),
        )->assertOk();

        $test->refresh();
        $this->assertEquals('failed', $test->status);
        $this->assertEquals('timeout', $test->error_code);
    }
}
