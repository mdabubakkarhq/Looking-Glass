<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\NetworkTest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
    public function index(): JsonResponse
    {
        $cacheKey = 'public:status';
        $ttl = 30;

        $status = Cache::remember($cacheKey, $ttl, function () {
            return [
                'controller' => 'ok',
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
                'nodes' => [
                    'total' => Node::public()->count(),
                    'online' => Node::public()->online()->count(),
                    'offline' => Node::public()->where('status', 'offline')->count(),
                    'maintenance' => Node::public()->where('maintenance', true)->count(),
                ],
                'tests_today' => NetworkTest::whereDate('created_at', today())->count(),
                'version' => config('app.version', '1.0.0'),
                'generated_at' => now()->toISOString(),
            ];
        });

        return response()->json(['data' => $status]);
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
                return 'ok'; // Not using Redis in dev mode
            }
            \Illuminate\Support\Facades\Redis::ping();
            return 'ok';
        } catch (\Exception) {
            return 'error';
        }
    }
}
