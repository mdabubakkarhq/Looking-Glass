<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'severity',
        'source_ip',
        'visitor_hash',
        'node_id',
        'metadata',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
