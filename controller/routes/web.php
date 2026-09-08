<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| The SPA frontend handles all client-side routing.
| The controller only serves the API and this health check.
|
*/

Route::get('/up', function () {
    return response()->json(['status' => 'ok', 'version' => config('app.version', '1.0.0')]);
});

// SPA catch-all: serve the Vue frontend for all non-API routes
Route::fallback(function () {
    $indexPath = public_path('index.html');
    if (!file_exists($indexPath)) {
        abort(404, 'Frontend not built. Run: cd frontend && npm run build');
    }
    return response()->file($indexPath, [
        'Content-Type' => 'text/html',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
    ]);
});
