<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RegistrationToken extends Model
{
    use HasFactory;
    protected $fillable = [
        'node_id',
        'token',
        'expires_at',
        'used_at',
        'used_by_ip',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (RegistrationToken $token) {
            if (empty($token->token)) {
                $token->token = Str::random(
                    config('looking-glass.registration_token_length', 64)
                );
            }
            if (empty($token->expires_at)) {
                $token->expires_at = now()->addHours(24);
            }
        });
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed();
    }

    public function markUsed(string $ip): void
    {
        $this->update([
            'used_at' => now(),
            'used_by_ip' => $ip,
        ]);
    }
}
