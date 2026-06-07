<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CookieConsent extends Model
{
    protected $fillable = [
        'anonymous_id',
        'consent_version',
        'essential',
        'analytics',
        'marketing',
        'preferences',
        'consented_at',
        'last_updated_at',
        'ip_hash',
        'user_agent_hash',
    ];

    protected $casts = [
        'essential' => 'boolean',
        'analytics' => 'boolean',
        'marketing' => 'boolean',
        'preferences' => 'boolean',
        'consented_at' => 'datetime',
        'last_updated_at' => 'datetime',
    ];
}
