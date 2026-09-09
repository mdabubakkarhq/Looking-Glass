<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class LookingGlassServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->applySmtpConfig();
    }

    /**
     * Apply SMTP settings from the database to Laravel's mail configuration
     * so that Mail::raw() / Mail::to() use the admin-configured SMTP server
     * on every request (not just when settings are saved).
     */
    protected function applySmtpConfig(): void
    {
        try {
            $host = Setting::getValue('smtp_host', '');
            if (empty($host)) {
                return; // SMTP not configured; keep Laravel defaults
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
        } catch (\Throwable $e) {
            // Silently fall back to defaults if the DB is not available yet
            // (e.g. during initial setup or migrations).
            \Illuminate\Support\Facades\Log::debug('LookingGlassServiceProvider: Could not apply SMTP config: ' . $e->getMessage());
        }
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
