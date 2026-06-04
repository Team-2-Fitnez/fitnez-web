<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable {
    use Notifiable;
    protected $table='users'; const CREATED_AT='created_at'; const UPDATED_AT=null;
    protected $fillable=['email','password_hash','full_name','birth_date','phone','profile_picture_url','role_id','last_login','is_active','email_verified_at','membership_package_id','membership_started_at','membership_expires_at','free_class_access'];
    protected $hidden=['password_hash'];
    protected $casts=['birth_date'=>'date','created_at'=>'datetime','last_login'=>'datetime','is_active'=>'boolean','email_verified_at'=>'datetime','membership_started_at'=>'datetime','membership_expires_at'=>'datetime','free_class_access'=>'boolean'];
    public function getAuthPassword(): string { return (string)$this->password_hash; }
    public function role(){ return $this->belongsTo(Role::class); }
    public function membershipPackage(){ return $this->belongsTo(MembershipPackage::class); }
    public function trainerDetail(){ return $this->hasOne(TrainerDetail::class); }
    public function trainerApplications(){ return $this->hasMany(TrainerApplication::class); }
    public function latestTrainerApplication(){ return $this->hasOne(TrainerApplication::class)->latestOfMany(); }
    public function roleName(): ?string { return $this->role?->name; }
    public function isMember(): bool { return $this->roleName()==='member'; }
    public function isAdmin(): bool { return $this->roleName()==='admin'; }
    public function isTrainer(): bool { return $this->roleName()==='trainer'; }
}
