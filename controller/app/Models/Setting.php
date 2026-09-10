<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;
    protected $fillable = [
        'group',
        'key',
        'label',
        'value',
        'type',
        'description',
        'public',
    ];

    protected function casts(): array
    {
        return [
            'public' => 'boolean',
        ];
    }

    /**
     * Get a setting value by key with caching.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = "setting:{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return match ($setting->type) {
                'integer' => (int) $setting->value,
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($setting->value, true),
                'text' => $setting->value,
                default => $setting->value,
            };
        });
    }

    /**
     * Set a setting value and clear its cache.
     */
    public static function setValue(string $key, mixed $value, string $type = 'string'): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $type,
            ]
        );

        Cache::forget("setting:{$key}");

        // Clear the SPA index cache so meta tags reflect the new value
        Cache::forget('spa_index_html');

        return $setting;
    }

    /**
     * Get all public settings.
     */
    public static function getPublicSettings(): array
    {
        return static::where('public', true)
            ->get()
            ->mapWithKeys(function (Setting $setting) {
                $value = match ($setting->type) {
                    'integer' => (int) $setting->value,
                    'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                    'json' => json_decode($setting->value, true),
                    default => $setting->value,
                };

                return [$setting->key => $value];
            })
            ->toArray();
    }
}
