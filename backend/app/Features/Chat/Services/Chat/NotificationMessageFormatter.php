<?php

namespace App\Features\Chat\Services\Chat;

class NotificationMessageFormatter implements MessageFormatter
{
    public function format(string $message, array $context = []): string
    {
        $type = $context['type'] ?? 'info';
        $emoji = match ($type) {
            'payment' => '💰',
            'schedule' => '📅',
            'booking' => '📋',
            default => '🔔',
        };
        return "$emoji " . trim($message);
    }
}
