<?php

namespace App\Http\Middleware;

use App\Models\NodeCredential;
use App\Services\AgentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgent
{
    public function __construct(
        private AgentService $agentService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $nodeKeyId = $request->header('X-Node-Key');
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        if (!$nodeKeyId || !$signature || !$timestamp) {
            return response()->json(['message' => 'Missing authentication headers.'], 401);
        }

        // Check timestamp freshness (prevent replay attacks)
        $requestTime = \Carbon\Carbon::parse($timestamp);
        if ($requestTime->diffInSeconds(now()) > 30) {
            \App\Models\SecurityEvent::create([
                'event_type' => 'replay_attack_detected',
                'severity' => 'critical',
                'source_ip' => $request->ip(),
                'description' => 'Request timestamp outside acceptable 30-second window.',
            ]);

            return response()->json(['message' => 'Request timestamp expired.'], 401);
        }

        $credential = NodeCredential::where('node_key_id', $nodeKeyId)
            ->where('active', true)
            ->first();

        if (!$credential) {
            return response()->json(['message' => 'Invalid node credentials.'], 401);
        }

        // Verify HMAC signature
        $payload = $request->except(['X-Node-Key', 'X-Signature', 'X-Timestamp']);
        $payload['timestamp'] = $timestamp;

        if (!$this->agentService->verifySignature($payload, $signature, $credential->node_secret)) {
            \App\Models\SecurityEvent::create([
                'event_type' => 'invalid_agent_signature',
                'severity' => 'warning',
                'source_ip' => $request->ip(),
                'node_id' => $credential->node_id,
                'description' => 'HMAC signature verification failed.',
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        // Update last authenticated time
        $credential->update([
            'last_authenticated_at' => now(),
            'agent_ip' => $request->ip(),
        ]);

        // Attach node to request
        $request->attributes->set('authenticated_node', $credential->node);

        return $next($request);
    }
}
