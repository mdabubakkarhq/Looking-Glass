<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodeCredential extends Model
{
    use HasFactory;
    protected $fillable = [
        'node_id',
        'node_key_id',
        'node_secret',
        'agent_ip',
        'last_authenticated_at',
        'active',
    ];

    protected $hidden = [
        'node_secret',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_authenticated_at' => 'datetime',
        ];
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
