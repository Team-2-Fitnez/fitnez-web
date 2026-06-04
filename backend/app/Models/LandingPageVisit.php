<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageVisit extends Model
{
    protected $fillable = [
        'visitor_uuid',
        'session_uuid',
        'visit_date',
        'user_id',
        'ip_address',
        'user_agent_hash',
        'user_agent',
        'browser_name',
        'browser_version',
        'os_name',
        'device_type',
        'referrer',
        'landing_url',
        'route_path',
        'query_params',
        'locale',
        'timezone',
        'screen_width',
        'screen_height',
        'viewport_width',
        'viewport_height',
        'page_view_count',
        'visited_at',
        'last_seen_at',
	'browser_context',
        'browser_context_label',
	'client_browser_name',
	'client_browser_engine',
	'private_mode_detected',
	'private_mode_confidence',
	'private_mode_source',
    ];

    protected $casts = [
        'query_params' => 'array',
        'visit_date' => 'date',
        'visited_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'page_view_count' => 'integer',
        'screen_width' => 'integer',
        'screen_height' => 'integer',
        'viewport_width' => 'integer',
        'viewport_height' => 'integer',
	'private_mode_detected' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
