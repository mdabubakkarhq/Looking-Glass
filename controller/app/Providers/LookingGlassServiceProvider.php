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
        $this->applySecurityConfig();
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

    /**
     * Apply security settings from the database to Laravel's config
     * so that TestService and other code reads admin-configured values.
     */
    protected function applySecurityConfig(): void
    {
        try {
            $rateLimit = Setting::getValue('rate_limit_per_minute');
            if ($rateLimit !== null) {
                config(['looking-glass.rate_limit_per_minute' => (int) $rateLimit]);
            }

            $blockedNetworks = Setting::getValue('blocked_networks');
            if ($blockedNetworks !== null) {
                config(['looking-glass.blocked_networks' => $blockedNetworks]);
            }

            $dnsRebind = Setting::getValue('dns_rebind_protection');
            if ($dnsRebind !== null) {
                config(['looking-glass.dns_rebind_protection' => (bool) $dnsRebind]);
            }

            $retentionDays = Setting::getValue('log_retention_days');
            if ($retentionDays !== null) {
                config(['looking-glass.log_retention_days' => (int) $retentionDays]);
            }

            $maxOutput = Setting::getValue('max_output_bytes');
            if ($maxOutput !== null) {
                config(['looking-glass.max_output_bytes' => (int) $maxOutput]);
            }

            $loginMaxAttempts = Setting::getValue('login_max_attempts');
            if ($loginMaxAttempts !== null) {
                config(['looking-glass.login_max_attempts' => (int) $loginMaxAttempts]);
            }

            $loginBanMinutes = Setting::getValue('login_ban_minutes');
            if ($loginBanMinutes !== null) {
                config(['looking-glass.login_ban_minutes' => (int) $loginBanMinutes]);
            }
        } catch (\Throwable $e) {
            // Silently fall back to defaults if the DB is not available yet.
            \Illuminate\Support\Facades\Log::debug('LookingGlassServiceProvider: Could not apply security config: ' . $e->getMessage());
        }
    }
}
