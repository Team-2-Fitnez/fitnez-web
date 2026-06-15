<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TrainerBooking extends Model
{
    use HasFactory;
    protected $table = 'trainer_bookings';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    const STATUS_PENDING = 'pending';
    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const STATUS_TRANSITIONS = [
        self::STATUS_PENDING          => [self::STATUS_PENDING_PAYMENT, self::STATUS_CANCELLED],
        self::STATUS_PENDING_PAYMENT  => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED        => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_COMPLETED        => [],
        self::STATUS_CANCELLED        => [],
    ];

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    protected $fillable = [
        'member_id',
        'trainer_id',
        'start_date',
        'end_date',
        'sessions_per_week',
        'session_days',
        'session_time',
        'member_notes',
        'base_price_per_session',
        'member_price_per_session',
        'total_member_price',
        'total_trainer_price',
        'total_sessions',
        'payment_proof_path',
        'paid_at',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'session_days' => 'array',
        'base_price_per_session' => 'decimal:2',
        'member_price_per_session' => 'decimal:2',
        'total_member_price' => 'decimal:2',
        'total_trainer_price' => 'decimal:2',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $appends = ['payment_proof_url'];

    public function getPaymentProofUrlAttribute(): ?string
    {
        if (!$this->payment_proof_path) {
            return null;
        }
        return Storage::disk('public')->url($this->payment_proof_path);
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('member_id', $userId)
              ->orWhere('trainer_id', $userId);
        });
    }

    public function scopePendingPayment(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_PAYMENT);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function generateSessionDates(): array
    {
        $dates = [];
        $days = $this->session_days ?? [];
        $current = $this->start_date->copy();
        $end = $this->end_date->copy();
        $dayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $targetNumbers = array_map(fn($d) => $dayMap[strtolower($d)] ?? -1, $days);

        while ($current->lte($end)) {
            if (in_array($current->dayOfWeek, $targetNumbers)) {
                $dates[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }
        return $dates;
    }
}
