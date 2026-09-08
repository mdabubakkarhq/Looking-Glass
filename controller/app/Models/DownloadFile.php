<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadFile extends Model
{
    use HasFactory;
    protected $fillable = [
        'node_id',
        'name',
        'filename',
        'size_bytes',
        'size_label',
        'url',
        'enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
