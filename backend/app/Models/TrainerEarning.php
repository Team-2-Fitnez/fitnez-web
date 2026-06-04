<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerEarning extends Model
{
    protected $table = 'trainer_earnings';

    protected $fillable = [
        'trainer_id',
        'member_id',
        'payment_id',
        'booking_id',
        'commission_rate',
        'trainer_amount',
        'amount',
        'status',
        'earned_at',
        'paid_at',
        'disbursed_at',
        'description',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'trainer_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'earned_at' => 'date',
        'paid_at' => 'date',
        'disbursed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function booking()
    {
        return $this->belongsTo(TrainerBooking::class, 'booking_id');
    }
}
