<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Services\LatencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NodeController extends Controller
{
    public function __construct(
        private LatencyService $latencyService
    ) {}

    /**
     * List all public nodes.
     */
    public function index(): JsonResponse
    {
        $nodes = Node::public()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (Node $node) => [
                'id' => $node->slug,
                'uuid' => $node->uuid,
                'name' => $node->name,
                'hostname' => $node->hostname,
                'city' => $node->city,
                'country_code' => $node->country_code,
                'provider' => $node->provider,
                'asn' => $node->asn,
                'ipv4' => $node->ipv4,
                'ipv6' => $node->ipv6,
                'ipv4_enabled' => $node->ipv4_enabled,
                'ipv6_enabled' => $node->ipv6_enabled,
                'latitude' => $node->latitude,
                'longitude' => $node->longitude,
                'uplink_mbps' => $node->uplink_mbps,
                'status' => $node->status,
                'location' => $node->location,
                'latency_enabled' => $node->latency_enabled,
                'iperf3_enabled' => $node->iperf3_enabled,
                'iperf3_port' => $node->iperf3_port,
                'iperf3_status' => $node->iperf3_status,
                'capabilities' => $node->capabilities
                    ->where('enabled', true)
                    ->pluck('feature')
                    ->values(),
            ]);

        return response()->json(['data' => $nodes]);
    }

    /**
     * Get latency for all public nodes.
     */
    public function latency(): JsonResponse
    {
        $latencyData = $this->latencyService->getAllNodeLatency();

        return response()->json($latencyData);
    }

    /**
     * Show a single node.
     */
    public function show(Node $node): JsonResponse
    {
        if (!$node->public) {
            return response()->json(['message' => 'Node not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $node->slug,
                'uuid' => $node->uuid,
                'name' => $node->name,
                'hostname' => $node->hostname,
                'city' => $node->city,
                'country_code' => $node->country_code,
                'provider' => $node->provider,
                'asn' => $node->asn,
                'ipv4' => $node->ipv4,
                'ipv6' => $node->ipv6,
                'ipv4_enabled' => $node->ipv4_enabled,
                'ipv6_enabled' => $node->ipv6_enabled,
                'latitude' => $node->latitude,
                'longitude' => $node->longitude,
                'uplink_mbps' => $node->uplink_mbps,
                'status' => $node->status,
                'maintenance' => $node->maintenance,
                'location' => $node->location,
                'agent_version' => $node->agent_version,
                'last_seen_at' => $node->last_seen_at?->toISOString(),
                'latency_enabled' => $node->latency_enabled,
                'iperf3_enabled' => $node->iperf3_enabled,
                'iperf3_port' => $node->iperf3_port,
                'iperf3_status' => $node->iperf3_status,
                'last_ipv4_health_check_at' => $node->last_ipv4_health_check_at?->toISOString(),
                'last_ipv6_health_check_at' => $node->last_ipv6_health_check_at?->toISOString(),
                'last_iperf3_health_check_at' => $node->last_iperf3_health_check_at?->toISOString(),
                'capabilities' => $node->capabilities
                    ->where('enabled', true)
                    ->map(fn ($cap) => [
                        'feature' => $cap->feature,
                        'max_concurrent' => $cap->max_concurrent,
                        'timeout_seconds' => $cap->timeout_seconds,
                    ])
                    ->values(),
            ],
        ]);
    }
}
