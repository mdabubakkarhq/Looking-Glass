<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NetworkTest;
use App\Models\NetworkTestEvent;
use App\Models\Node;
use App\Models\NodeCredential;
use App\Models\NodeHeartbeat;
use App\Models\RegistrationToken;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __construct(
        private AgentService $agentService
    ) {}

    /**
     * Handle agent heartbeat.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $node = $request->attributes->get('authenticated_node');

        if (!$node) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $heartbeat = NodeHeartbeat::create([
            'node_id' => $node->id,
            'agent_version' => $request->input('agent_version'),
            'hostname' => $request->input('hostname'),
            'os' => $request->input('os'),
            'cpu_usage_percent' => $request->input('cpu_usage_percent'),
            'memory_usage_percent' => $request->input('memory_usage_percent'),
            'disk_usage_percent' => $request->input('disk_usage_percent'),
            'active_tests' => $request->input('active_tests', 0),
            'uptime_seconds' => $request->input('uptime_seconds'),
            'load_average' => $request->input('load_average'),
            'sent_at' => $request->input('sent_at', now()),
        ]);

        // Update node status and metadata
        $node->update([
            'status' => 'online',
            'last_seen_at' => now(),
            'agent_version' => $request->input('agent_version'),
        ]);

        return response()->json([
            'data' => [
                'received_at' => now()->toISOString(),
                'pending_tests' => $this->agentService->getPendingTestCount($node),
            ],
        ]);
    }

    /**
     * Handle agent registration using a one-time token.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'registration_token' => 'required|string',
            'hostname' => 'required|string|max:255',
            'os' => 'nullable|string|max:255',
            'agent_version' => 'required|string|max:50',
            'ipv4' => 'nullable|ip',
            'ipv6' => 'nullable|ipv6',
        ]);

        $token = RegistrationToken::where('token', $request->input('registration_token'))
            ->first();

        if (!$token || !$token->isValid()) {
            return response()->json([
                'message' => 'Invalid or expired registration token.',
            ], 422);
        }

        $result = $this->agentService->registerAgent($token, $request->ip(), $request->all());

        return response()->json([
            'data' => $result,
        ], 201);
    }

    /**
     * Receive test output events from agent.
     */
    public function testEvent(Request $request, NetworkTest $test): JsonResponse
    {
        $node = $request->attributes->get('authenticated_node');

        if (!$node || $test->node_id !== $node->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        NetworkTestEvent::create([
            'network_test_id' => $test->id,
            'event_type' => $request->input('event_type', 'output'),
            'data' => $request->input('data'),
            'occurred_at' => $request->input('occurred_at', now()),
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Mark a test as complete with final statistics.
     */
    public function testComplete(Request $request, NetworkTest $test): JsonResponse
    {
        $node = $request->attributes->get('authenticated_node');

        if (!$node || $test->node_id !== $node->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $stats = $request->only([
            'packet_loss', 'latency_min', 'latency_avg', 'latency_max',
            'latency_stddev', 'hop_count', 'resolved_ip',
        ]);

        if ($request->input('error_code')) {
            $test->markFailed(
                $request->input('error_code'),
                $request->input('error_message', '')
            );
        } else {
            $test->markCompleted($stats);
        }

        // Record completion event
        NetworkTestEvent::create([
            'network_test_id' => $test->id,
            'event_type' => $request->input('error_code') ? 'error' : 'complete',
            'data' => $request->input('summary', []),
            'occurred_at' => now(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}
