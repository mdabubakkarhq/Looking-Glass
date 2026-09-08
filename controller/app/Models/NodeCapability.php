<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodeCapability extends Model
{
    use HasFactory;
    protected $fillable = [
        'node_id',
        'feature',
        'enabled',
        'max_concurrent',
        'timeout_seconds',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'max_concurrent' => 'integer',
            'timeout_seconds' => 'integer',
        ];
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
