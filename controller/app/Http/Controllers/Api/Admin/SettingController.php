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

        // Apply SMTP settings to Laravel mail config in real-time
        $this->applySmtpConfig();

        // Clear all settings cache
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(['settings'])->flush();
        } else {
            Cache::flush();
        }

        return response()->json(['message' => 'Settings updated']);
    }

    /**
     * Apply SMTP settings from DB to Laravel's mail configuration.
     */
    private function applySmtpConfig(): void
    {
        $host = Setting::getValue('smtp_host', '');
        if (empty($host)) {
            return; // SMTP not configured, don't override
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) Setting::getValue('smtp_port', 587),
            'mail.mailers.smtp.username' => Setting::getValue('smtp_username', ''),
            'mail.mailers.smtp.password' => Setting::getValue('smtp_password', ''),
            'mail.mailers.smtp.encryption' => Setting::getValue('smtp_encryption', 'tls'),
        ]);

        $fromAddress = Setting::getValue('smtp_from_address', '');
        $fromName = Setting::getValue('smtp_from_name', '');
        if ($fromAddress) {
            config([
                'mail.from.address' => $fromAddress,
                'mail.from.name' => $fromName ?: config('app.name'),
            ]);
        }
    }
}
