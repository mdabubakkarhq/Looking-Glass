<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemUpdate extends Model
{
    use HasFactory;
    protected $fillable = [
        'version',
        'previous_version',
        'status',
        'notes',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Get the current installed version.
     */
    public static function currentVersion(): string
    {
        $latest = static::completed()->latest()->first();

        return $latest?->version ?? '0.0.0';
    }
}
