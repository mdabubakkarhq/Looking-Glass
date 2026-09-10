<?php

namespace App\Services;

use App\Models\NetworkTest;
use App\Models\Node;
use App\Models\NodeCredential;
use App\Models\RegistrationToken;
use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AgentService
{
    /**
     * Dispatch a test to a node agent.
     */
    public function dispatchTest(NetworkTest $test, Node $node): void
    {
        $credential = $node->credentials;

        if (!$credential || !$credential->active) {
            $test->markFailed('no_credentials', 'Node has no active credentials.');
            return;
        }

        $test->markRunning();

        $payload = [
            'test_id' => $test->uuid,
            'test_type' => $test->test_type,
            'target' => $test->target,
            'ip_family' => $test->ip_family,
            'timeout' => config("looking-glass.test_runtime_limits.{$test->test_type}", 60),
            'timestamp' => now()->toISOString(),
        ];

        // Sign the request with HMAC
        $signature = $this->signPayload($payload, $credential->node_secret);

        try {
            $agentUrl = "http://{$node->ipv4}:8443/api/tests";

            Http::timeout(5)
                ->withHeaders([
                    'X-Node-Key' => $credential->node_key_id,
                    'X-Signature' => $signature,
                    'X-Timestamp' => $payload['timestamp'],
                ])
                ->post($agentUrl, $payload);
        } catch (\Exception $e) {
            $test->markFailed('dispatch_failed', $e->getMessage());
        }
    }

    /**
     * Sign a payload using HMAC-SHA256.
     */
    public function signPayload(array $payload, string $secret): string
    {
        ksort($payload);
        $message = json_encode($payload, JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', $message, $secret);
    }

    /**
     * Verify an incoming HMAC signature from an agent.
     */
    public function verifySignature(array $payload, string $signature, string $secret): bool
    {
        $expected = $this->signPayload($payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Get pending test count for a node.
     */
    public function getPendingTestCount(Node $node): int
    {
        return NetworkTest::where('node_id', $node->id)
            ->whereIn('status', ['pending', 'running'])
            ->count();
    }

    /**
     * Register a new agent using a registration token.
     */
    public function registerAgent(RegistrationToken $token, string $agentIp, array $data): array
    {
        $node = $token->node;

        if (!$node) {
            throw new \RuntimeException('Registration token has no associated node.');
        }

        // Generate credentials
        $nodeKeyId = 'lk_' . Str::random(32);
        $nodeSecret = Str::random(config('looking-glass.node_secret_length', 64));

        // Store hashed secret
        NodeCredential::updateOrCreate(
            ['node_id' => $node->id],
            [
                'node_key_id' => $nodeKeyId,
                'node_secret' => hash('sha256', $nodeSecret),
                'agent_ip' => $agentIp,
                'active' => true,
            ]
        );

        // Update node info
        $node->update([
            'ipv4' => $data['ipv4'] ?? $node->ipv4,
            'ipv6' => $data['ipv6'] ?? $node->ipv6,
            'agent_version' => $data['agent_version'] ?? $node->agent_version,
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        // Mark token as used
        $token->markUsed($agentIp);

        SecurityEvent::create([
            'event_type' => 'registration_token_used',
            'severity' => 'info',
            'source_ip' => $agentIp,
            'node_id' => $node->id,
            'description' => "Agent registered on node {$node->slug} using one-time token.",
        ]);

        return [
            'node_id' => $node->slug,
            'node_key_id' => $nodeKeyId,
            'node_secret' => $nodeSecret, // Shown once, agent must store it
            'controller_url' => config('app.url'),
            'heartbeat_interval' => config('looking-glass.heartbeat_interval_seconds', 30),
        ];
    }

    /**
     * Rotate credentials for a node.
     */
    public function rotateCredentials(Node $node): NodeCredential
    {
        $nodeKeyId = 'lk_' . Str::random(32);
        $nodeSecret = Str::random(config('looking-glass.node_secret_length', 64));

        $credential = NodeCredential::updateOrCreate(
            ['node_id' => $node->id],
            [
                'node_key_id' => $nodeKeyId,
                'node_secret' => hash('sha256', $nodeSecret),
                'active' => true,
            ]
        );

        SecurityEvent::create([
            'event_type' => 'credential_rotation',
            'severity' => 'info',
            'node_id' => $node->id,
            'description' => "Agent credentials rotated for node {$node->slug}.",
        ]);

        return $credential;
    }
}
