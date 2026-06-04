<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'invoice_number',
        'user_id',
        'membership_package_id',
        'booking_id',
        'payment_type',
        'type',
        'amount',
        'payment_method',
        'payment_status',
        'status',
        'payment_date',
        'paid_at',
        'external_reference',
        'proof_path',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(TrainerBooking::class, 'booking_id');
    }
}
