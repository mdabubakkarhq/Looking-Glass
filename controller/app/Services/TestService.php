<?php

namespace App\Services;

use App\Models\NetworkTest;
use App\Models\Node;
use App\Models\RateLimitEvent;
use Illuminate\Support\Facades\Cache;

class TestService
{
    public function __construct(private AgentService $agentService) {}

    public function hashVisitor(string $ip): string
    {
        return hash('sha256', config('looking-glass.visitor_hash_salt', '') . ':' . $ip);
    }

    public function isRateLimited(string $visitorHash, ?string $nodeSlug = null): bool
    {
        $max = config('looking-glass.rate_limit_per_minute', 30);
        $key = "rate_limit:visitor:{$visitorHash}";
        $count = (int) Cache::get($key, 0);

        if ($count >= $max) {
            RateLimitEvent::create([
                'visitor_hash' => $visitorHash,
                'endpoint' => 'tests',
                'reason' => 'per_ip',
                'node_id' => $nodeSlug ? Node::where('slug', $nodeSlug)->value('id') : null,
            ]);
            return true;
        }

        Cache::put($key, $count + 1, 60);
        return false;
    }

    public function validateTarget(string $target): array
    {
        if (empty($target) || strlen($target) > 255) {
            return ['valid' => false, 'message' => 'Invalid target.'];
        }

        if (filter_var($target, FILTER_VALIDATE_IP) && $this->isBlockedIp($target)) {
            return ['valid' => false, 'message' => 'Target is in a private/reserved network range.'];
        }

        if (!filter_var($target, FILTER_VALIDATE_IP) &&
            !preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-\.]*[a-zA-Z0-9])?$/', $target)) {
            return ['valid' => false, 'message' => 'Invalid hostname format.'];
        }

        return ['valid' => true];
    }

    private function isBlockedIp(string $ip): bool
    {
        // Only check IPv4 addresses against IPv4 CIDRs
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        foreach (config('looking-glass.blocked_networks', []) as $cidr) {
            [$subnet, $mask] = explode('/', $cidr);
            // Skip IPv6 CIDRs
            if (!filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }
            if ((ip2long($ip) & ~((1 << (32 - (int) $mask)) - 1)) === ip2long($subnet)) {
                return true;
            }
        }
        return false;
    }

    public function createTest(
        string $nodeSlug,
        string $testType,
        string $target,
        string $ipFamily,
        string $visitorHash,
    ): NetworkTest {
        $node = Node::where('slug', $nodeSlug)->firstOrFail();

        throw_if($node->maintenance, new \RuntimeException('Node is in maintenance mode.'));
        throw_if(!$node->is_online, new \RuntimeException('Node is offline.'));

        $capability = $node->capabilities()
            ->where('feature', $testType)->where('enabled', true)->first();

        throw_if(!$capability, new \RuntimeException("Node does not support {$testType}."));

        $running = NetworkTest::where('node_id', $node->id)->where('status', 'running')->count();
        $max = $capability->max_concurrent ?? config("looking-glass.concurrency_limits.{$testType}", 20);

        throw_if($running >= $max, new \RuntimeException('Node at max concurrent capacity.'));

        $test = NetworkTest::create([
            'node_id' => $node->id,
            'test_type' => $testType,
            'target' => $target,
            'ip_family' => $ipFamily,
            'status' => 'pending',
            'visitor_hash' => $visitorHash,
        ]);

        $this->agentService->dispatchTest($test, $node);
        return $test;
    }
}
