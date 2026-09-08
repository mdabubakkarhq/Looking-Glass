<?php

namespace Tests;

use App\Models\NodeCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Create a Sanctum-authenticated admin user.
     */
    protected function actingAsAdmin(?User $user = null): User
    {
        $user ??= User::factory()->create();
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    /**
     * Build HMAC auth headers for an agent request.
     */
    protected function agentHeaders(NodeCredential $credential, array $payload = []): array
    {
        $timestamp = now()->toISOString();
        $payload['timestamp'] = $timestamp;

        ksort($payload);
        $message = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $message, $credential->node_secret);

        return [
            'X-Node-Key' => $credential->node_key_id,
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ];
    }
}
