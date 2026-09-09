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

        // Raw IP: check directly against blocked networks
        if (filter_var($target, FILTER_VALIDATE_IP)) {
            if ($this->isBlockedIp($target)) {
                return ['valid' => false, 'message' => 'Target is in a private/reserved network range.'];
            }
            return ['valid' => true];
        }

        // Hostname: validate format first
        if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-\.]*[a-zA-Z0-9])?$/', $target)) {
            return ['valid' => false, 'message' => 'Invalid hostname format.'];
        }

        // DNS rebind protection: resolve hostname and check all resolved IPs
        if (config('looking-glass.dns_rebind_protection', true)) {
            $resolvedIps = $this->resolveHost($target);

            if (empty($resolvedIps)) {
                return ['valid' => false, 'message' => 'Unable to resolve hostname.'];
            }

            foreach ($resolvedIps as $ip) {
                if ($this->isBlockedIp($ip)) {
                    return ['valid' => false, 'message' => 'Resolved IP is in a private/reserved network range.'];
                }
            }
        }

        return ['valid' => true];
    }

    /**
     * Resolve a hostname to an array of IP addresses.
     *
     * @return string[]
     */
    private function resolveHost(string $hostname): array
    {
        $ips = [];
        $records = @dns_get_record($hostname, DNS_A + DNS_AAAA);

        if (!$records) {
            // Fallback: try gethostbyname for A records only
            $ip = gethostbyname($hostname);
            if ($ip !== $hostname && filter_var($ip, FILTER_VALIDATE_IP)) {
                $ips[] = $ip;
            }
            return $ips;
        }

        foreach ($records as $record) {
            if (isset($record['ip'])) {
                $ips[] = $record['ip'];
            } elseif (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return array_unique($ips);
    }

    private function isBlockedIp(string $ip): bool
    {
        $blockedNetworks = config('looking-glass.blocked_networks', []);

        // Check IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            foreach ($blockedNetworks as $cidr) {
                [$subnet, $mask] = explode('/', $cidr);
                if (!filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    continue;
                }
                if ((ip2long($ip) & ~((1 << (32 - (int) $mask)) - 1)) === ip2long($subnet)) {
                    return true;
                }
            }
            return false;
        }

        // Check IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            foreach ($blockedNetworks as $cidr) {
                [$subnet, $mask] = explode('/', $cidr);
                if (!filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    continue;
                }
                if ($this->ipv6InCidr($ip, $subnet, (int) $mask)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if an IPv6 address falls within a CIDR range.
     */
    private function ipv6InCidr(string $ip, string $subnet, int $mask): bool
    {
        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        // Compare only the prefix bits
        $fullBytes = intdiv($mask, 8);
        $remainingBits = $mask % 8;

        if (strncmp($ipBin, $subnetBin, $fullBytes) !== 0) {
            return false;
        }

        if ($remainingBits > 0 && $fullBytes < 16) {
            $ipByte = ord($ipBin[$fullBytes]);
            $subnetByte = ord($subnetBin[$fullBytes]);
            $mask = 0xFF << (8 - $remainingBits) & 0xFF;

            if (($ipByte & $mask) !== ($subnetByte & $mask)) {
                return false;
            }
        }

        return true;
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
