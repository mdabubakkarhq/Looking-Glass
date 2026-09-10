<?php

namespace App\Providers;

use App\Services\AgentService;
use App\Services\LatencyService;
use App\Services\SseStreamService;
use App\Services\TestService;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentService::class);
        $this->app->singleton(LatencyService::class);
        $this->app->singleton(SseStreamService::class);
        $this->app->singleton(TestService::class);
    }

    public function boot(): void
    {
        // Skip Sanctum's built-in personal_access_tokens migration
        // because the project ships its own at database/migrations/2024_01_01_000045.
        Sanctum::ignoreMigrations();
    }
}
