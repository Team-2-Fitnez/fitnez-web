<?php

namespace App\Shared\Constants;

class BookingStatus
{
    const PENDING = 'pending';
    const PENDING_PAYMENT = 'pending_payment';
    const CONFIRMED = 'confirmed';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';
}
