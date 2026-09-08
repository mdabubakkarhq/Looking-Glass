<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkTestEvent extends Model
{
    use HasFactory;
    protected $fillable = [
        'network_test_id',
        'event_type',
        'data',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'json',
            'occurred_at' => 'datetime',
        ];
    }

    public function networkTest()
    {
        return $this->belongsTo(NetworkTest::class);
    }
}
