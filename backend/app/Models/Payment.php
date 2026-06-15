<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $table = 'payments';

    public $timestamps = false;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'booking_id',
        'membership_package_id',
        'payment_type',
        'amount',
        'payment_method',
        'payment_status',
        'payment_date',
        'external_reference',
        'payment_proof_path',
    ];

    protected $appends = ['payment_proof_url'];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(TrainerBooking::class, 'booking_id');
    }

    public function membershipPackage()
    {
        return $this->belongsTo(MembershipPackage::class, 'membership_package_id');
    }

    public function getPaymentProofUrlAttribute()
    {
        return $this->payment_proof_path ? asset('storage/' . $this->payment_proof_path) : null;
    }
}
