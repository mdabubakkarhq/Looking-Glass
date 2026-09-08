<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    /**
     * Return public configuration for the frontend.
     */
    public function index(): JsonResponse
    {
        $config = [
            'site_name' => config('looking-glass.site_name'),
            'organization' => config('looking-glass.organization'),
            'test_types' => config('looking-glass.test_types'),
            'ip_families' => config('looking-glass.ip_families'),
            'supported_features' => config('looking-glass.supported_features'),
            'download_sizes' => array_keys(config('looking-glass.download_sizes', [])),
        ];

        // Merge in public settings from the database
        $dbSettings = Setting::getPublicSettings();
        $config = array_merge($config, $dbSettings);

        return response()->json($config);
    }
}
