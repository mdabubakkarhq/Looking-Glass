<?php

namespace Tests\Feature\Api;

use App\Models\Node;
use App\Models\NodeCredential;
use App\Models\RegistrationToken;
use App\Models\User;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    public function test_agent_can_send_heartbeat(): void
    {
        $node = Node::factory()->create(['status' => 'online']);
        $credential = NodeCredential::factory()->create(['node_id' => $node->id]);

        $payload = [
            'agent_version' => '1.0.0', 'hostname' => 'test-host',
            'os' => 'linux', 'cpu_usage_percent' => 15,
            'memory_usage_percent' => 45, 'disk_usage_percent' => 30,
            'uptime_seconds' => 86400, 'load_average' => [0.5, 0.3, 0.2],
        ];

        $response = $this->postJson('/api/v1/agent/heartbeat', $payload, $this->agentHeaders($credential, $payload));

        $response->assertOk()
            ->assertJsonStructure(['data' => ['received_at', 'pending_tests']]);

        $this->assertDatabaseHas('node_heartbeats', ['node_id' => $node->id, 'hostname' => 'test-host']);
    }

    public function test_heartbeat_rejects_missing_headers(): void
    {
        $this->postJson('/api/v1/agent/heartbeat', ['agent_version' => '1.0.0'])
            ->assertStatus(401);
    }

    public function test_heartbeat_rejects_invalid_signature(): void
    {
        $credential = NodeCredential::factory()->create();
        $this->postJson('/api/v1/agent/heartbeat', ['agent_version' => '1.0.0'], [
            'X-Node-Key' => $credential->node_key_id,
            'X-Signature' => 'invalid-sig',
            'X-Timestamp' => now()->toISOString(),
        ])->assertStatus(401);
    }

    public function test_agent_can_register_with_valid_token(): void
    {
        $node = Node::factory()->offline()->create();
        $user = User::factory()->create();
        $token = RegistrationToken::factory()->create([
            'node_id' => $node->id, 'created_by' => $user->id,
        ]);

        $response = $this->postJson('/api/v1/agent/register', [
            'registration_token' => $token->token,
            'hostname' => 'new-agent', 'agent_version' => '1.0.0',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['node_id', 'node_key_id', 'node_secret', 'controller_url'],
            ]);

        $this->assertDatabaseHas('node_credentials', ['node_id' => $node->id, 'active' => true]);
    }

    public function test_register_rejects_expired_token(): void
    {
        $node = Node::factory()->offline()->create();
        $token = RegistrationToken::factory()->expired()->create([
            'node_id' => $node->id, 'created_by' => User::factory()->create()->id,
        ]);

        $this->postJson('/api/v1/agent/register', [
            'registration_token' => $token->token,
            'hostname' => 'new-agent', 'agent_version' => '1.0.0',
        ])->assertStatus(422);
    }

    public function test_register_validates_required_fields(): void
    {
        $this->postJson('/api/v1/agent/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['registration_token', 'hostname', 'agent_version']);
    }
}
