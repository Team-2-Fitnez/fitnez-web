<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainerBooking extends Model
{
    use HasFactory;
    protected $table = 'trainer_bookings';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED = 'rejected';

    const STATUS_TRANSITIONS = [
        self::STATUS_PENDING   => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED, self::STATUS_REJECTED],
        self::STATUS_CONFIRMED => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [],
        self::STATUS_REJECTED  => [],
    ];

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    protected $fillable = [
        'member_id',
        'trainer_id',
        'booking_date',
        'start_time',
        'end_time',
        'session_type',
        'location',
        'member_notes',
        'status',
        'total_price',
    ];

    protected $casts = [
        'booking_date' => 'date:Y-m-d',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /**
     * Bookings visible to a given user (as member or trainer).
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('member_id', $userId)
              ->orWhere('trainer_id', $userId);
        });
    }

    /**
     * Active bookings (pending or confirmed — not cancelled/rejected/completed).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }
}
