<?php

namespace App\Console\Commands;

use App\Models\Node;
use App\Services\LatencyService;
use Illuminate\Console\Command;

class RefreshLatency extends Command
{
    protected $signature = 'lg:refresh-latency';

    protected $description = 'Refresh latency cache for all public nodes by pinging them';

    public function handle(LatencyService $latencyService): int
    {
        $nodes = Node::public()->online()->get();

        if ($nodes->isEmpty()) {
            $this->info('No online public nodes found.');
            return self::SUCCESS;
        }

        $this->info("Pinging {$nodes->count()} node(s)...");

        foreach ($nodes as $node) {
            $this->pingNode($latencyService, $node);
        }

        // Clear the aggregated cache so it rebuilds on next request
        \Illuminate\Support\Facades\Cache::forget('public:node_latency');

        $this->info('Latency cache refreshed.');
        return self::SUCCESS;
    }

    /**
     * Ping a node's IPv4 and IPv6 addresses and update latency cache.
     */
    private function pingNode(LatencyService $latencyService, Node $node): void
    {
        // Ping IPv4
        if ($node->ipv4) {
            $result = $this->ping($node->ipv4, false);
            if ($result !== null) {
                $latencyService->updateNodeLatency($node, 'ipv4', $result['avg_ms']);
                $latencyService->updatePacketLoss($node, $result['loss_pct']);
                $this->line("  [{$node->slug}] IPv4 {$node->ipv4}: {$result['avg_ms']} ms, {$result['loss_pct']}% loss");
            } else {
                $latencyService->updateNodeLatency($node, 'ipv4', -1);
                $latencyService->updatePacketLoss($node, 100);
                $this->warn("  [{$node->slug}] IPv4 {$node->ipv4}: unreachable");
            }
        }

        // Ping IPv6
        if ($node->ipv6) {
            $result = $this->ping($node->ipv6, true);
            if ($result !== null) {
                $latencyService->updateNodeLatency($node, 'ipv6', $result['avg_ms']);
                $this->line("  [{$node->slug}] IPv6 {$node->ipv6}: {$result['avg_ms']} ms");
            } else {
                $latencyService->updateNodeLatency($node, 'ipv6', -1);
                $this->warn("  [{$node->slug}] IPv6 {$node->ipv6}: unreachable");
            }
        }
    }

    /**
     * Execute a ping command and parse the result.
     *
     * @return array{avg_ms: float, loss_pct: float}|null
     */
    private function ping(string $host, bool $ipv6): ?array
    {
        $timeout = config('looking-glass.latency_ping_timeout_seconds', 5);
        $count = 4;

        // Build ping command (Linux-compatible)
        $cmd = sprintf(
            'ping %s -c %d -W %d -q %s 2>&1',
            $ipv6 ? '-6' : '-4',
            $count,
            $timeout,
            escapeshellarg($host)
        );

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        $text = implode("\n", $output);

        // Parse packet loss: "4 packets transmitted, 4 received, 0% packet loss"
        $lossPct = 100.0;
        if (preg_match('/(\d+(?:\.\d+)?)% packet loss/', $text, $m)) {
            $lossPct = (float) $m[1];
        }

        // Parse latency: "rtt min/avg/max/mdev = 1.234/5.678/9.012/3.456 ms"
        $avgMs = null;
        if (preg_match('/(?:rtt|round-trip)\s+min\/avg\/max\/(?:mdev|stddev)\s*=\s*([\d.]+)\/([\d.]+)\/([\d.]+)\/([\d.]+)/', $text, $m)) {
            $avgMs = (float) $m[2];
        }

        // If we got latency data, it's a success even with some packet loss
        if ($avgMs !== null && $lossPct < 100) {
            return [
                'avg_ms' => round($avgMs, 2),
                'loss_pct' => round($lossPct, 1),
            ];
        }

        return null;
    }
}
