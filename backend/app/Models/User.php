<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'email',
        'password_hash',
        'full_name',
        'birth_date',
        'age',
        'phone',
        'profile_picture_url',
        'role_id',
        'last_login',
        'is_active',
        'email_verified_at',
        'membership_package_id',
        'membership_started_at',
        'membership_expires_at',
        'free_class_access',
        'renewal_package_id',
        'membership_renewal_starts_at',
        'membership_renewal_expires_at',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'birth_date' => 'date',
        'age' => 'integer',
        'created_at' => 'datetime',
        'last_login' => 'datetime',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'membership_started_at' => 'datetime',
        'membership_expires_at' => 'datetime',
        'membership_renewal_starts_at' => 'datetime',
        'membership_renewal_expires_at' => 'datetime',
        'free_class_access' => 'boolean',
    ];

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function membershipPackage()
    {
        return $this->belongsTo(MembershipPackage::class);
    }

    public function renewalPackage()
    {
        return $this->belongsTo(MembershipPackage::class, 'renewal_package_id');
    }

    public function trainerDetail()
    {
        return $this->hasOne(TrainerDetail::class);
    }

    public function trainerApplications()
    {
        return $this->hasMany(TrainerApplication::class);
    }

    public function latestTrainerApplication()
    {
        return $this->hasOne(TrainerApplication::class)->latestOfMany();
    }

    public function trainerBookingsAsMember()
    {
        return $this->hasMany(TrainerBooking::class, 'member_id');
    }

    public function trainerBookingsAsTrainer()
    {
        return $this->hasMany(TrainerBooking::class, 'trainer_id');
    }

    public function roleName(): ?string
    {
        return $this->role?->name;
    }

    public function isMember(): bool
    {
        return $this->roleName() === 'member';
    }

    public function isAdmin(): bool
    {
        return $this->roleName() === 'admin';
    }

    public function isTrainer(): bool
    {
        return $this->roleName() === 'trainer';
    }

    public function membershipStatus(): string
    {
        if (! $this->isMember()) {
            return 'not_applicable';
        }

        if (! $this->membership_expires_at) {
            return $this->membership_package_id ? 'active' : 'no_package';
        }

        if ($this->membership_expires_at->isPast()) {
            return $this->isPastMembershipRenewalGracePeriod() ? 'grace_expired' : 'expired';
        }

        return $this->membership_expires_at->diffInDays(now(), false) >= -3 ? 'expiring_soon' : 'active';
    }

    public function membershipDaysLeft(): ?int
    {
        if (! $this->membership_expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->membership_expires_at, false));
    }

    public function isMembershipExpired(): bool
    {
        return $this->isMember()
            && $this->membership_expires_at
            && $this->membership_expires_at->isPast();
    }

    public function isPastMembershipRenewalGracePeriod(): bool
    {
        return $this->isMembershipExpired()
            && now()->greaterThan($this->membership_expires_at->copy()->addMonth());
    }

    public function membershipRenewalDeadline(): ?\Illuminate\Support\Carbon
    {
        return $this->membership_expires_at?->copy()->addMonth();
    }

    public function activateDueMembershipRenewal(): void
    {
        if (! $this->renewal_package_id || ! $this->membership_renewal_starts_at) {
            return;
        }

        if ($this->membership_renewal_starts_at->isFuture()) {
            return;
        }

        $package = $this->renewalPackage()->first();

        $this->forceFill([
            'membership_package_id' => $this->renewal_package_id,
            'membership_started_at' => $this->membership_renewal_starts_at,
            'membership_expires_at' => $this->membership_renewal_expires_at,
            'free_class_access' => (bool) ($package?->free_class_access ?? false),
            'renewal_package_id' => null,
            'membership_renewal_starts_at' => null,
            'membership_renewal_expires_at' => null,
        ])->save();

        $this->unsetRelation('membershipPackage');
        $this->unsetRelation('renewalPackage');
    }
}
