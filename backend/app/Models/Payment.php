<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';

    public $timestamps = false;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'booking_id',
        'payment_type',
        'amount',
        'payment_method',
        'payment_status',
        'payment_date',
        'external_reference',
    ];

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
}
