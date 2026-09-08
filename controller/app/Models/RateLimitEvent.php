<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateLimitEvent extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'visitor_hash',
        'ip_family',
        'endpoint',
        'reason',
        'node_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
