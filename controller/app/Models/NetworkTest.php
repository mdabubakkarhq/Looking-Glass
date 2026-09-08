<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NetworkTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'node_id',
        'test_type',
        'target',
        'resolved_ip',
        'ip_family',
        'status',
        'visitor_hash',
        'started_at',
        'completed_at',
        'runtime_ms',
        'packet_loss',
        'latency_min',
        'latency_avg',
        'latency_max',
        'latency_stddev',
        'hop_count',
        'error_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'runtime_ms' => 'integer',
            'packet_loss' => 'float',
            'latency_min' => 'float',
            'latency_avg' => 'float',
            'latency_max' => 'float',
            'latency_stddev' => 'float',
            'hop_count' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (NetworkTest $test) {
            if (empty($test->uuid)) {
                $test->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function events()
    {
        return $this->hasMany(NetworkTestEvent::class, 'network_test_id');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    // ── Status Helpers ────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(array $stats = []): void
    {
        $this->update(array_merge($stats, [
            'status' => 'completed',
            'completed_at' => now(),
            'runtime_ms' => $this->started_at
                ? (int) (now()->diffInMilliseconds($this->started_at))
                : null,
        ]));
    }

    public function markFailed(string $code, string $message = ''): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_code' => $code,
            'error_message' => $message,
            'runtime_ms' => $this->started_at
                ? (int) (now()->diffInMilliseconds($this->started_at))
                : null,
        ]);
    }
}
