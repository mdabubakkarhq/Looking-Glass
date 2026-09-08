<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NetworkTest;
use App\Models\Node;
use App\Models\RateLimitEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $hourAgo = $now->copy()->subHour();

        return response()->json([
            'data' => [
                'nodes' => [
                    'active' => Node::where('status', 'online')->count(),
                    'offline' => Node::where('status', 'offline')->count(),
                    'maintenance' => Node::where('maintenance', true)->count(),
                    'total' => Node::count(),
                ],
                'tests' => [
                    'today' => NetworkTest::where('created_at', '>=', $today)->count(),
                    'this_hour' => NetworkTest::where('created_at', '>=', $hourAgo)->count(),
                    'running' => NetworkTest::where('status', 'running')->count(),
                    'failed_today' => NetworkTest::where('status', 'failed')
                        ->where('created_at', '>=', $today)
                        ->count(),
                ],
                'rate_limits' => [
                    'today' => RateLimitEvent::where('created_at', '>=', $today)->count(),
                ],
                'top_targets' => NetworkTest::where('created_at', '>=', $today)
                    ->select('target', DB::raw('count(*) as test_count'))
                    ->groupBy('target')
                    ->orderByDesc('test_count')
                    ->limit(10)
                    ->get(),
                'system' => [
                    'database' => $this->checkDatabase(),
                    'redis' => $this->checkRedis(),
                    'queue' => $this->checkQueue(),
                ],
            ],
        ]);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();
            return 'ok';
        } catch (\Exception) {
            return 'error';
        }
    }

    private function checkRedis(): string
    {
        try {
            if (config('cache.default') === 'array' || config('cache.default') === 'file') {
                return 'ok';
            }
            \Illuminate\Support\Facades\Redis::ping();
            return 'ok';
        } catch (\Exception) {
            return 'error';
        }
    }

    private function checkQueue(): string
    {
        try {
            if (config('cache.default') === 'array' || config('cache.default') === 'file') {
                return 'ok';
            }
            $pending = \Illuminate\Support\Facades\Redis::llen('queues:default');
            return $pending > 1000 ? 'backlog' : 'ok';
        } catch (\Exception) {
            return 'unknown';
        }
    }
}
