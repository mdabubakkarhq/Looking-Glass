<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http as HttpFacade;

class LatencyProbeController extends Controller
{
    public function __construct(private AgentService $agentService) {}

    public function start(Request $request): JsonResponse
    {
        $visitorKey = 'latency:visitor:' . md5($request->ip());
        if (Cache::has($visitorKey)) {
            return response()->json(['message' => 'Rate limited — retry later.'], 429);
        }
        Cache::put($visitorKey, true, 15);

        $visitorIp = $this->resolveVisitorIp($request);
        if (!$this->isPingableIp($visitorIp)) {
            return response()->json(['message' => 'No public IP detected for latency measurement.'], 422);
        }

        $sessionId = bin2hex(random_bytes(16));
        $nodes = Node::public()->active()->where('latency_enabled', true)->ordered()->get();
        $ipFamily = filter_var($visitorIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'ipv6' : 'ipv4';

        $sessionData = [
            'visitor_ip' => $visitorIp, 'ip_family' => $ipFamily,
            'started_at' => now()->toISOString(), 'node_count' => $nodes->count(),
        ];

        $dispatched = 0;
        foreach ($nodes as $node) {
            $key = "latency:result:{$sessionId}:{$node->slug}";
            Cache::put($key, [
                'node_slug' => $node->slug, 'node_name' => $node->name,
                'status' => 'measuring', 'visitor_ip' => $visitorIp,
                'ip_family' => $ipFamily, 'started_at' => now()->toISOString(),
            ], 120);
            try {
                $this->dispatchProbe($node, $visitorIp, $sessionId);
                $dispatched++;
            } catch (\Exception $e) {
                Cache::put($key, [
                    'node_slug' => $node->slug, 'node_name' => $node->name,
                    'status' => 'error', 'error_message' => $e->getMessage(),
                ], 120);
            }
        }

        $sessionData['dispatched_count'] = $dispatched;
        Cache::put("latency:session:{$sessionId}", $sessionData, 120);
        return response()->json(['data' => array_merge($sessionData, ['session_id' => $sessionId])]);
    }

    public function results(string $sessionId): JsonResponse
    {
        $sessionData = Cache::get("latency:session:{$sessionId}");
        if (!$sessionData) {
            return response()->json(['message' => 'Session not found or expired.'], 404);
        }

        $nodes = Node::public()->active()->where('latency_enabled', true)->ordered()->get();
        $results = [];
        $allComplete = true;

        foreach ($nodes as $node) {
            $nodeResult = Cache::get("latency:result:{$sessionId}:{$node->slug}");
            if ($nodeResult) {
                $results[] = $nodeResult;
                if (in_array($nodeResult['status'], ['measuring', 'pending'])) {
                    $allComplete = false;
                }
            } else {
                $results[] = ['node_slug' => $node->slug, 'node_name' => $node->name, 'status' => 'pending'];
                $allComplete = false;
            }
        }

        return response()->json(['data' => [
            'session_id' => $sessionId, 'visitor_ip' => $sessionData['visitor_ip'],
            'ip_family' => $sessionData['ip_family'], 'started_at' => $sessionData['started_at'],
            'complete' => $allComplete, 'nodes' => $results,
        ]]);
    }

    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string|size:32',
            'node_slug' => 'required|string',
            'status' => 'required|in:completed,timeout,blocked,error',
            'latency_avg_ms' => 'nullable|numeric|min:0',
            'latency_min_ms' => 'nullable|numeric|min:0',
            'latency_max_ms' => 'nullable|numeric|min:0',
            'jitter_ms' => 'nullable|numeric|min:0',
            'packet_loss_percent' => 'nullable|numeric|between:0,100',
            'packets_sent' => 'nullable|integer|min:0',
            'packets_received' => 'nullable|integer|min:0',
            'ip_family' => 'nullable|in:ipv4,ipv6',
            'error_message' => 'nullable|string|max:500',
        ]);

        if (!Cache::has("latency:session:{$validated['session_id']}")) {
            return response()->json(['message' => 'Session expired.'], 404);
        }

        $key = "latency:result:{$validated['session_id']}:{$validated['node_slug']}";
        Cache::put($key, array_merge($validated, ['completed_at' => now()->toISOString()]), 120);
        return response()->json(['status' => 'ok']);
    }

    private function resolveVisitorIp(Request $request): string
    {
        $clientIp = $request->ip();
        $forwardedFor = $request->header('X-Forwarded-For');
        if ($forwardedFor && !$this->isPublicIp($clientIp)) {
            foreach (array_map('trim', explode(',', $forwardedFor)) as $ip) {
                if ($this->isPublicIp($ip)) return $ip;
            }
        }
        return $clientIp;
    }

    private function dispatchProbe(Node $node, string $targetIp, string $sessionId): void
    {
        $credential = $node->credentials;
        if (!$credential || !$credential->active) {
            Cache::put("latency:result:{$sessionId}:{$node->slug}", [
                'node_slug' => $node->slug, 'node_name' => $node->name,
                'status' => 'error', 'error_message' => 'No active credentials.',
            ], 120);
            return;
        }

        $payload = [
            'session_id' => $sessionId, 'probe_type' => 'latency', 'target' => $targetIp,
            'packet_count' => 5, 'timeout_seconds' => 10,
            'report_url' => config('app.url') . '/api/v1/latency/report',
            'timestamp' => now()->toISOString(),
        ];
        $signature = $this->agentService->signPayload($payload, $credential->node_secret);

        HttpFacade::timeout(5)->withHeaders([
            'X-Node-Key' => $credential->node_key_id,
            'X-Signature' => $signature,
            'X-Timestamp' => $payload['timestamp'],
        ])->post("http://{$node->ipv4}:8443/api/probes", $payload);
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function isPingableIp(string $ip): bool
    {
        if (!$this->isPublicIp($ip)) return false;
        $packed = @inet_pton($ip);
        if ($packed === false) return false;
        if (strlen($packed) === 4) {
            $first = ord($packed[0]);
            if ($first >= 224 && $first <= 239) return false;
        } elseif (strlen($packed) === 16) {
            if (ord($packed[0]) === 0xff) return false;
        }
        return true;
    }
}
