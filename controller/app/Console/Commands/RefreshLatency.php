<?php

namespace App\Console\Commands;

use App\Models\Node;
use App\Services\LatencyService;
use Illuminate\Console\Command;

class RefreshLatency extends Command
{
    protected $signature = 'lg:refresh-latency';
    protected $description = 'Refresh latency cache for all public nodes';

    public function handle(LatencyService $latencyService): int
    {
        $nodes = Node::public()->online()->get();

        foreach ($nodes as $node) {
            // In a real implementation, this would dispatch a ping to each node
            // and update the latency cache with actual results.
            // For now, we ensure the cache structure is refreshed.
            $this->line("Refreshing latency for {$node->slug}...");
        }

        // Clear the aggregated cache so it gets rebuilt on next request
        \Illuminate\Support\Facades\Cache::forget('public:node_latency');

        $this->info("Latency cache refreshed for {$nodes->count()} nodes.");
        return self::SUCCESS;
    }
}
