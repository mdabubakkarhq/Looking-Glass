<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get();

        return response()->json(['data' => $settings]);
    }

    public function show(string $group): JsonResponse
    {
        $settings = Setting::where('group', $group)->orderBy('key')->get();

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.type' => 'sometimes|string|in:string,integer,boolean,json,text',
        ]);

        foreach ($validated['settings'] as $item) {
            $type = $item['type'] ?? 'string';
            Setting::setValue($item['key'], $item['value'], $type);
        }

        // Clear all settings cache
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(['settings'])->flush();
        } else {
            Cache::flush();
        }

        return response()->json(['message' => 'Settings updated']);
    }
}
