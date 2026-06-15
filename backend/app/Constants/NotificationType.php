<?php

namespace App\Constants;

class NotificationType
{
    const BOOKING_REQUEST = 'booking_request';
    const BOOKING_CONFIRMED = 'booking_confirmed';
    const BOOKING_CANCELLED = 'booking_cancelled';
    const BOOKING_COMPLETED = 'booking_completed';
    const PAYMENT_IN = 'payment_in';
    const PAYMENT_REMINDER = 'payment_reminder';
    const PAYMENT_REJECTED = 'payment_rejected';
    const CHAT_MESSAGE = 'chat_message';
    const EARNING_PAID = 'earning_paid';
    const APPLICATION_APPROVED = 'application_approved';
    const APPLICATION_REJECTED = 'application_rejected';
}
