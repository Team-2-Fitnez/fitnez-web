<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JwtSession extends Model
{
    protected $fillable = [
        'user_id',
        'jti',
        'ip_address',
        'user_agent_hash',
        'issued_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function isRevoked(): bool
    {
        return ! is_null($this->revoked_at);
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }
}
