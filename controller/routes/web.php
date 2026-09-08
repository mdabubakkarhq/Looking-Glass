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
