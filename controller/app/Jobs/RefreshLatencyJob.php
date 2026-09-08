<?php

namespace App\Jobs;

use App\Models\Node;
use App\Services\LatencyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RefreshLatencyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct()
    {
        $this->onQueue('latency');
    }

    public function handle(LatencyService $latencyService): void
    {
        $nodes = Node::public()->online()->get();

        foreach ($nodes as $node) {
            // In a full implementation, this would send ICMP pings to the node
            // and record actual latency. For now, it refreshes the cache structure.
            // The actual latency updates come from agent heartbeats.
        }

        // Clear the aggregated cache so it gets rebuilt on next request
        Cache::forget('public:node_latency');
    }
}
