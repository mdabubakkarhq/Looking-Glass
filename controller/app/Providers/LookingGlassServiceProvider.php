<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class LookingGlassServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(config('looking-glass.rate_limit_per_minute', 30))
                ->by($request->user()?->id ?: $request->ip())
                ->response(function ($request, $headers) {
                    return response()->json([
                        'message' => 'Too many requests. Please slow down.',
                        'error' => 'rate_limited',
                    ], 429, $headers);
                });
        });
    }
}
