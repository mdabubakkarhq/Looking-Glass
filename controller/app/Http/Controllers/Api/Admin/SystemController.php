<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\NetworkTest;
use App\Models\SystemUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SystemController extends Controller
{
    public function info(): JsonResponse
    {
        return response()->json([
            'data' => [
                'version' => config('app.version', '1.0.0'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
                'timezone' => config('app.timezone'),
                'database_driver' => config('database.default'),
                'queue_connection' => config('queue.default'),
                'cache_driver' => config('cache.default'),
                'nodes_total' => Node::count(),
                'tests_total' => NetworkTest::count(),
                'last_update' => SystemUpdate::latest()->first(),
            ],
        ]);
    }

    public function health(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'nodes_online' => Node::where('status', 'online')->count(),
            'nodes_offline' => Node::where('status', 'offline')->count(),
        ];

        $healthy = !in_array('error', $checks);

        return response()->json([
            'healthy' => $healthy,
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    public function update(): JsonResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);

            return response()->json([
                'message' => 'Migrations completed successfully.',
                'output' => Artisan::output(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Update failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function rollback(): JsonResponse
    {
        try {
            Artisan::call('migrate:rollback', ['--force' => true]);

            return response()->json([
                'message' => 'Rollback completed successfully.',
                'output' => Artisan::output(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Rollback failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
                return 'ok'; // Not using Redis in dev/test mode
            }
            Redis::ping();
            return 'ok';
        } catch (\Exception) {
            return 'error';
        }
    }

    private function checkQueue(): string
    {
        try {
            if (config('cache.default') === 'array' || config('cache.default') === 'file') {
                return 'ok'; // Not using Redis in dev/test mode
            }
            $pending = Redis::llen('queues:default');
            return $pending > 1000 ? 'backlog' : 'ok';
        } catch (\Exception) {
            return 'unknown';
        }
    }
}
