<?php

namespace App\Console\Commands;

use App\Models\Node;
use Illuminate\Console\Command;

class CheckNodes extends Command
{
    protected $signature = 'lg:check-nodes';
    protected $description = 'Check for stale nodes and mark them offline';

    public function handle(): int
    {
        $staleThreshold = now()->subSeconds(
            config('looking-glass.heartbeat_stale_threshold_seconds', 90)
        );

        $staleNodes = Node::where('status', 'online')
            ->where('last_seen_at', '<', $staleThreshold)
            ->get();

        foreach ($staleNodes as $node) {
            $node->update(['status' => 'offline']);
            $this->warn("Node {$node->slug} marked offline (last seen: {$node->last_seen_at}).");
        }

        if ($staleNodes->isEmpty()) {
            $this->info('No stale nodes found.');
        } else {
            $this->info("Marked {$staleNodes->count()} node(s) as offline.");
        }

        return self::SUCCESS;
    }
}
