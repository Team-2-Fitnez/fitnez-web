<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OtpCode extends Model {
    protected $fillable = ['user_id','email','purpose','code_hash','expires_at','used_at','attempts','ip_address'];
    protected $casts = ['expires_at'=>'datetime','used_at'=>'datetime','attempts'=>'integer'];
    public function user() { return $this->belongsTo(User::class); }
    public function isExpired(): bool { return now()->greaterThan($this->expires_at); }
    public function isUsed(): bool { return !is_null($this->used_at); }
}
