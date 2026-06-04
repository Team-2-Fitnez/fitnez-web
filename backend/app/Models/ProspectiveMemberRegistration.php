<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class ProspectiveMemberRegistration extends Model {
    protected $fillable=['membership_package_id','manual_payment_method_id','user_id','admin_id','registration_code','full_name','email','phone','password_hash','amount','status','payment_proof_path','payment_submitted_at','approved_at','rejected_at','rejection_reason','account_created_at'];
    protected $hidden=['password_hash'];
    protected $casts=['amount'=>'integer','payment_submitted_at'=>'datetime','approved_at'=>'datetime','rejected_at'=>'datetime','account_created_at'=>'datetime'];
    protected $appends=['payment_proof_url'];
    public function package(){ return $this->belongsTo(MembershipPackage::class,'membership_package_id'); }
    public function paymentMethod(){ return $this->belongsTo(ManualPaymentMethod::class,'manual_payment_method_id'); }
    public function user(){ return $this->belongsTo(User::class); }
    public function admin(){ return $this->belongsTo(User::class,'admin_id'); }
    public function getPaymentProofUrlAttribute(): ?string { return $this->payment_proof_path ? Storage::disk('public')->url($this->payment_proof_path) : null; }
}
