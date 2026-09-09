<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\NodeCredential;
use App\Models\NodeCapability;
use App\Models\RegistrationToken;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NodeController extends Controller
{
    public function __construct(
        private AgentService $agentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $nodes = Node::with(['latestHeartbeat', 'capabilities'])
            ->ordered()
            ->paginate($request->input('per_page', 25));

        return response()->json($nodes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hostname' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(?!https?:\/\/)(?!.*[?#\s])[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?$/i',
                'unique:nodes,hostname',
            ],
            'city' => 'nullable|string|max:255',
            'country_code' => 'nullable|string|size:2',
            'provider' => 'nullable|string|max:255',
            'asn' => 'nullable|string|max:20',
            'ipv4' => 'nullable|ip',
            'ipv6' => 'nullable|ipv6',
            'ipv4_enabled' => 'boolean',
            'ipv6_enabled' => 'boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'uplink_mbps' => 'nullable|integer|min:1',
            'public' => 'boolean',
            'sort_order' => 'integer|min:0',
            'download_host' => 'nullable|url',
            'latency_enabled' => 'boolean',
            'iperf3_enabled' => 'boolean',
            'iperf3_port' => 'nullable|integer|min:1|max:65535',
        ]);

        $node = Node::create($validated);

        // Create default capabilities
        foreach (config('looking-glass.supported_features', []) as $feature => $enabled) {
            if ($enabled) {
                NodeCapability::create([
                    'node_id' => $node->id,
                    'feature' => $feature,
                    'enabled' => true,
                    'max_concurrent' => config("looking-glass.concurrency_limits.{$feature}"),
                    'timeout_seconds' => config("looking-glass.test_runtime_limits.{$feature}"),
                ]);
            }
        }

        return response()->json(['data' => $node->load('capabilities')], 201);
    }

    public function show(Node $node): JsonResponse
    {
        return response()->json([
            'data' => $node->load([
                'credentials',
                'capabilities',
                'latestHeartbeat',
                'downloadFiles',
            ]),
        ]);
    }

    public function update(Request $request, Node $node): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'hostname' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(?!https?:\/\/)(?!.*[?#\s])[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?$/i',
                'unique:nodes,hostname,' . $node->id,
            ],
            'city' => 'nullable|string|max:255',
            'country_code' => 'nullable|string|size:2',
            'provider' => 'nullable|string|max:255',
            'asn' => 'nullable|string|max:20',
            'ipv4' => 'nullable|ip',
            'ipv6' => 'nullable|ipv6',
            'ipv4_enabled' => 'boolean',
            'ipv6_enabled' => 'boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'uplink_mbps' => 'nullable|integer|min:1',
            'public' => 'boolean',
            'sort_order' => 'integer|min:0',
            'download_host' => 'nullable|url',
            'status' => 'sometimes|in:online,offline,maintenance,error',
            'maintenance' => 'boolean',
            'latency_enabled' => 'boolean',
            'iperf3_enabled' => 'boolean',
            'iperf3_port' => 'nullable|integer|min:1|max:65535',
            'iperf3_status' => 'sometimes|in:available,busy,maintenance,unavailable',
        ]);

        $node->update($validated);

        return response()->json(['data' => $node->fresh()->load('capabilities')]);
    }

    public function destroy(Node $node): JsonResponse
    {
        $node->delete();

        return response()->json(['message' => 'Node deleted']);
    }

    /**
     * Generate a one-time registration token for a node.
     */
    public function generateToken(Node $node): JsonResponse
    {
        $token = RegistrationToken::create([
            'node_id' => $node->id,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'data' => [
                'token' => $token->token,
                'expires_at' => $token->expires_at->toISOString(),
                'node_id' => $node->slug,
            ],
        ]);
    }

    /**
     * Toggle maintenance mode for a node.
     */
    public function toggleMaintenance(Node $node): JsonResponse
    {
        $node->update([
            'maintenance' => !$node->maintenance,
            'status' => $node->maintenance ? 'offline' : $node->status,
        ]);

        return response()->json([
            'data' => [
                'maintenance' => $node->maintenance,
                'status' => $node->status,
            ],
        ]);
    }

    /**
     * Rotate node credentials.
     */
    public function rotateCredentials(Node $node): JsonResponse
    {
        $credentials = $this->agentService->rotateCredentials($node);

        return response()->json([
            'data' => [
                'node_key_id' => $credentials->node_key_id,
                'message' => 'Credentials rotated. The agent must re-register.',
            ],
        ]);
    }
}
