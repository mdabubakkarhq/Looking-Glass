<?php

namespace App\Services;

use App\Models\Node;
use Illuminate\Support\Facades\Cache;

class LatencyService
{
    /**
     * Get latency data for all public nodes, using cache.
     */
    public function getAllNodeLatency(): array
    {
        $cacheKey = 'public:node_latency';
        $ttl = config('looking-glass.latency_cache_ttl_seconds', 15);

        return Cache::remember($cacheKey, $ttl, function () {
            $nodes = Node::public()->active()->ordered()->get();

            return [
                'generated_at' => now()->toISOString(),
                'nodes' => $nodes->map(fn (Node $node) => [
                    'id' => $node->slug,
                    'name' => $node->name,
                    'location' => $node->location,
                    'ipv4_latency_ms' => $this->getCachedLatency($node, 'ipv4'),
                    'ipv6_latency_ms' => $this->getCachedLatency($node, 'ipv6'),
                    'packet_loss_percent' => $this->getCachedPacketLoss($node),
                    'status' => $node->is_online ? 'online' : 'offline',
                    'last_checked_at' => $node->last_seen_at?->toISOString(),
                ]),
            ];
        });
    }

    /**
     * Get cached latency for a specific node and IP family.
     */
    private function getCachedLatency(Node $node, string $family): ?float
    {
        $cacheKey = "latency:{$node->id}:{$family}";

        return Cache::get($cacheKey);
    }

    /**
     * Get cached packet loss for a node.
     */
    private function getCachedPacketLoss(Node $node): ?float
    {
        return Cache::get("packet_loss:{$node->id}");
    }

    /**
     * Update cached latency for a node (called by scheduler/agent).
     */
    public function updateNodeLatency(Node $node, string $family, float $latencyMs): void
    {
        $ttl = config('looking-glass.latency_cache_ttl_seconds', 15) * 2;
        Cache::put("latency:{$node->id}:{$family}", $latencyMs, $ttl);
    }

    /**
     * Update cached packet loss for a node.
     */
    public function updatePacketLoss(Node $node, float $lossPercent): void
    {
        $ttl = config('looking-glass.latency_cache_ttl_seconds', 15) * 2;
        Cache::put("packet_loss:{$node->id}", $lossPercent, $ttl);
    }
}
