<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BrowserTab extends Model {
    protected $fillable = ['user_id','device_uuid','tab_id','context_type','route','is_leader','last_heartbeat_at'];
    protected $casts = ['is_leader'=>'boolean','last_heartbeat_at'=>'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}
