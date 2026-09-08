<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodeHeartbeat extends Model
{
    use HasFactory;
    protected $fillable = [
        'node_id',
        'agent_version',
        'hostname',
        'os',
        'cpu_usage_percent',
        'memory_usage_percent',
        'disk_usage_percent',
        'active_tests',
        'uptime_seconds',
        'load_average',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'cpu_usage_percent' => 'integer',
            'memory_usage_percent' => 'integer',
            'disk_usage_percent' => 'integer',
            'active_tests' => 'integer',
            'uptime_seconds' => 'integer',
            'load_average' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
