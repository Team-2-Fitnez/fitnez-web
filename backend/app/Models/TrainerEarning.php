<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerEarning extends Model
{
    protected $table = 'trainer_earnings';

    public $timestamps = false;

    protected $fillable = [
        'trainer_id',
        'payment_id',
        'booking_id',
        'commission_rate',
        'trainer_amount',
        'status',
        'disbursed_at',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'trainer_amount' => 'decimal:2',
        'disbursed_at' => 'datetime',
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
