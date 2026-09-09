<?php

use App\Http\Controllers\Api;
use App\Http\Controllers\Api\Admin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Public API routes are accessible without authentication.
| Admin routes require Sanctum authentication.
| Agent routes use HMAC-based authentication.
|
*/

Route::prefix('v1')->group(function () {

    // ── Public Configuration ──────────────────────────────────

    Route::get('/config', [Api\ConfigController::class, 'index']);

    // ── Public Nodes ──────────────────────────────────────────

    Route::get('/nodes', [Api\NodeController::class, 'index']);
    Route::get('/nodes/latency', [Api\NodeController::class, 'latency']);
    Route::get('/nodes/{node:slug}', [Api\NodeController::class, 'show']);

    // ── Public Tests ──────────────────────────────────────────

    Route::post('/tests', [Api\TestController::class, 'store']);
    Route::get('/tests/{test:uuid}', [Api\TestController::class, 'show']);
    Route::get('/tests/{test:uuid}/stream', [Api\TestController::class, 'stream']);

    // ── Public Downloads ──────────────────────────────────────

    Route::get('/downloads', [Api\DownloadController::class, 'index']);

    // ── Public Network Info ───────────────────────────────────

    Route::get('/network', [Api\NetworkController::class, 'index']);

    // ── Public Status ─────────────────────────────────────────

    Route::get('/status', [Api\StatusController::class, 'index']);

    // ── Public Connection Info ─────────────────────────────────

    Route::get('/connection', [Api\ConnectionController::class, 'index']);

    // ── Public Latency Probes ──────────────────────────────────

    Route::post('/latency/start', [Api\LatencyProbeController::class, 'start']);
    Route::get('/latency/{sessionId}', [Api\LatencyProbeController::class, 'results']);
    Route::post('/latency/report', [Api\LatencyProbeController::class, 'report']);

    // ── Public iPerf3 Sessions ──────────────────────────────────

    Route::post('/iperf3/session', [Api\Iperf3Controller::class, 'create']);
    Route::get('/iperf3/session/{sessionId}', [Api\Iperf3Controller::class, 'status']);

    // ── Agent Endpoints (HMAC Auth) ───────────────────────────

    Route::post('/agent/register', [Api\AgentController::class, 'register']);

    Route::middleware('agent.auth')->prefix('agent')->group(function () {
        Route::post('/heartbeat', [Api\AgentController::class, 'heartbeat']);
        Route::post('/tests/{test:uuid}/events', [Api\AgentController::class, 'testEvent']);
        Route::post('/tests/{test:uuid}/complete', [Api\AgentController::class, 'testComplete']);
    });

    // ── Admin Authentication ────────────────────────────────────

    Route::prefix('admin')->group(function () {
        Route::post('/login', [Admin\AuthController::class, 'login']);
        Route::post('/forgot-password', [Admin\AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [Admin\AuthController::class, 'resetPassword']);
    });

    // ── Admin Endpoints (Sanctum Auth) ────────────────────────

    Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {

        // Auth
        Route::get('/me', [Admin\AuthController::class, 'me']);
        Route::post('/logout', [Admin\AuthController::class, 'logout']);

        // Dashboard
        Route::get('/dashboard', [Admin\DashboardController::class, 'index']);

        // Nodes
        Route::apiResource('nodes', Admin\NodeController::class);
        Route::post('/nodes/{node}/generate-token', [Admin\NodeController::class, 'generateToken']);
        Route::post('/nodes/{node}/maintenance', [Admin\NodeController::class, 'toggleMaintenance']);
        Route::post('/nodes/{node}/rotate-credentials', [Admin\NodeController::class, 'rotateCredentials']);

        // Tests
        Route::get('/tests', [Admin\TestController::class, 'index']);
        Route::get('/tests/{test:uuid}', [Admin\TestController::class, 'show']);
        Route::delete('/tests/{test:uuid}', [Admin\TestController::class, 'destroy']);
        Route::post('/tests/purge', [Admin\TestController::class, 'purge']);

        // Settings
        Route::get('/settings', [Admin\SettingController::class, 'index']);
        Route::put('/settings', [Admin\SettingController::class, 'update']);
        Route::get('/settings/{group}', [Admin\SettingController::class, 'show']);

        // Menu Items
        Route::apiResource('menu-items', Admin\MenuItemController::class)->parameters([
            'menu-items' => 'menuItem',
        ]);
        Route::post('/menu-items/reorder', [Admin\MenuItemController::class, 'reorder']);

        // Footer Sections & Links
        Route::get('/footer', [Admin\FooterController::class, 'index']);
        Route::post('/footer/sections', [Admin\FooterController::class, 'storeSection']);
        Route::put('/footer/sections/{footerSection}', [Admin\FooterController::class, 'updateSection']);
        Route::delete('/footer/sections/{footerSection}', [Admin\FooterController::class, 'destroySection']);
        Route::post('/footer/links', [Admin\FooterController::class, 'storeLink']);
        Route::put('/footer/links/{footerLink}', [Admin\FooterController::class, 'updateLink']);
        Route::delete('/footer/links/{footerLink}', [Admin\FooterController::class, 'destroyLink']);

        // Media Upload
        Route::post('/media/upload', [Admin\MediaController::class, 'store']);

        // Downloads
        Route::apiResource('downloads', Admin\DownloadController::class);

        // API Keys
        Route::apiResource('api-keys', Admin\ApiKeyController::class)->parameters([
            'api-keys' => 'apiKey',
        ]);

        // Users
        Route::apiResource('users', Admin\UserController::class);

        // Security Events
        Route::get('/security-events', [Admin\SecurityEventController::class, 'index']);
        Route::get('/security-events/{securityEvent}', [Admin\SecurityEventController::class, 'show']);

        // Logs
        Route::get('/logs/rate-limits', [Admin\LogController::class, 'rateLimits']);
        Route::get('/logs/security', [Admin\LogController::class, 'security']);

        // System
        Route::get('/system/info', [Admin\SystemController::class, 'info']);
        Route::get('/system/health', [Admin\SystemController::class, 'health']);
        Route::post('/system/update', [Admin\SystemController::class, 'update']);
        Route::post('/system/rollback', [Admin\SystemController::class, 'rollback']);

        // SMTP Test
        Route::post('/settings/smtp/test', [Admin\AuthController::class, 'testSmtp']);

        // DNS Resolution (admin-only, for form suggestions)
        Route::post('/dns/resolve', [Api\DnsController::class, 'resolve']);
    });
});
