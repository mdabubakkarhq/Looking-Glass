<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Iperf3Controller extends Controller
{
    public function __construct(private AgentService $agentService) {}

    /**
     * Create a short-lived iPerf3 session for the selected node.
     *
     * Rate limited to 1 session per minute per visitor IP.
     * Max 3 concurrent sessions per node.
     * Sessions expire after 5 minutes.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_slug' => 'required|string',
        ]);

        // Rate limit: 1 session per minute per visitor
        $visitorKey = 'iperf3:visitor:' . md5($request->ip());
        if (Cache::has($visitorKey)) {
            return response()->json([
                'message' => 'Rate limited — please wait before requesting another session.',
            ], 429);
        }
        Cache::put($visitorKey, true, 60);

        // Find the node
        $node = Node::where('slug', $validated['node_slug'])->first();
        if (!$node) {
            return response()->json(['message' => 'Node not found.'], 404);
        }

        if (!$node->iperf3_enabled) {
            return response()->json(['message' => 'iPerf3 is not enabled on this node.'], 422);
        }

        if ($node->maintenance) {
            return response()->json(['message' => 'Node is in maintenance mode.'], 422);
        }

        if ($node->iperf3_status !== 'available') {
            return response()->json([
                'message' => "iPerf3 is currently {$node->iperf3_status} on this node.",
            ], 422);
        }

        if (!$node->hostname) {
            return response()->json(['message' => 'Node has no hostname configured.'], 422);
        }

        // Concurrency limit: max 3 active sessions per node
        $nodeSessionsKey = 'iperf3:node_sessions:' . $node->slug;
        $activeSessions = (int) Cache::get($nodeSessionsKey, 0);
        if ($activeSessions >= 3) {
            return response()->json([
                'message' => 'Node is busy — too many concurrent iPerf3 sessions. Please try again shortly.',
            ], 429);
        }

        // Generate session
        $sessionId = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(16));
        $port = $node->iperf3_port ?? 5201;
        $expiresAt = now()->addMinutes(5);
        $duration = 10; // seconds

        $hostname = $node->hostname;

        // Generate commands
        $commands = $this->buildCommands($hostname, $port, $duration, $token, $node->ipv6_enabled && $node->ipv6);

        $sessionData = [
            'session_id' => $sessionId,
            'node_slug' => $node->slug,
            'node_name' => $node->name,
            'hostname' => $hostname,
            'port' => $port,
            'token' => $token,
            'expires_at' => $expiresAt->toISOString(),
            'duration' => $duration,
            'ip_family' => 'ipv4',
            'visitor_ip' => $request->ip(),
            'iperf3_status' => $node->iperf3_status,
            'ipv4_enabled' => $node->ipv4_enabled,
            'ipv6_enabled' => $node->ipv6_enabled && (bool) $node->ipv6,
            'commands' => $commands,
        ];

        // Store session in cache (5 min TTL)
        Cache::put("iperf3:session:{$sessionId}", $sessionData, 300);

        // Increment active sessions count
        Cache::increment($nodeSessionsKey);
        // Schedule cleanup of session count
        Cache::put("iperf3:session_expiry:{$sessionId}", $node->slug, 300);

        return response()->json(['data' => $sessionData]);
    }

    /**
     * Get the status of an iPerf3 session.
     */
    public function status(string $sessionId): JsonResponse
    {
        $session = Cache::get("iperf3:session:{$sessionId}");

        if (!$session) {
            return response()->json([
                'data' => [
                    'session_id' => $sessionId,
                    'status' => 'expired',
                    'remaining_seconds' => 0,
                ],
            ]);
        }

        $expiresAt = \Carbon\Carbon::parse($session['expires_at']);
        $remaining = max(0, now()->diffInSeconds($expiresAt, false));

        return response()->json([
            'data' => [
                'session_id' => $sessionId,
                'node_slug' => $session['node_slug'],
                'status' => $remaining > 0 ? 'active' : 'expired',
                'expires_at' => $session['expires_at'],
                'remaining_seconds' => (int) $remaining,
            ],
        ]);
    }

    /**
     * Generate the iPerf3 CLI commands for the session.
     */
    private function buildCommands(string $hostname, int $port, int $duration, string $token, bool $ipv6Available): array
    {
        $base = "iperf3 -c {$hostname} -p {$port} -t {$duration}";

        $commands = [
            'upload' => $base,
            'download' => $base . ' -R',
            'ipv6_upload' => null,
            'ipv6_download' => null,
        ];

        if ($ipv6Available) {
            $commands['ipv6_upload'] = $base . ' -6';
            $commands['ipv6_download'] = $base . ' -6 -R';
        }

        return $commands;
    }
}
