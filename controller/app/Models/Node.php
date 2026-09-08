<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Node extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'city',
        'country_code',
        'provider',
        'asn',
        'ipv4',
        'ipv6',
        'latitude',
        'longitude',
        'uplink_mbps',
        'status',
        'maintenance',
        'public',
        'sort_order',
        'download_host',
        'agent_version',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'uplink_mbps' => 'integer',
            'maintenance' => 'boolean',
            'public' => 'boolean',
            'sort_order' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Node $node) {
            if (empty($node->uuid)) {
                $node->uuid = (string) Str::uuid();
            }
            if (empty($node->slug)) {
                $node->slug = Str::slug($node->name);
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────

    public function credentials()
    {
        return $this->hasOne(NodeCredential::class);
    }

    public function heartbeats()
    {
        return $this->hasMany(NodeHeartbeat::class);
    }

    public function latestHeartbeat()
    {
        return $this->hasOne(NodeHeartbeat::class)->latestOfMany();
    }

    public function capabilities()
    {
        return $this->hasMany(NodeCapability::class);
    }

    public function networkTests()
    {
        return $this->hasMany(NetworkTest::class);
    }

    public function downloadFiles()
    {
        return $this->hasMany(DownloadFile::class);
    }

    public function registrationTokens()
    {
        return $this->hasMany(RegistrationToken::class);
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopePublic($query)
    {
        return $query->where('public', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeActive($query)
    {
        return $query->where('maintenance', false)
                     ->where('status', '!=', 'offline');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ── Accessors ─────────────────────────────────────────────

    public function getIsOnlineAttribute(): bool
    {
        if ($this->status !== 'online') {
            return false;
        }

        $staleThreshold = now()->subSeconds(
            config('looking-glass.heartbeat_stale_threshold_seconds', 90)
        );

        return $this->last_seen_at && $this->last_seen_at->gte($staleThreshold);
    }

    public function getLocationAttribute(): string
    {
        return collect([$this->city, $this->country_code])
            ->filter()
            ->implode(', ');
    }
}
