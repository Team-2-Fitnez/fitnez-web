<?php

namespace App\Services\Chat;

interface MessageFormatter
{
    public function format(string $message, array $context = []): string;
}

class RegularMessageFormatter implements MessageFormatter
{
    public function format(string $message, array $context = []): string
    {
        return trim($message);
    }
}

class SystemMessageFormatter implements MessageFormatter
{
    public function format(string $message, array $context = []): string
    {
        $prefix = $context['prefix'] ?? '[System]';
        return str_starts_with(trim($message), $prefix)
            ? trim($message)
            : "$prefix " . trim($message);
    }
}

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

class MessageFormatterFactory
{
    public static function create(string $type = 'regular'): MessageFormatter
    {
        return match ($type) {
            'system'       => new SystemMessageFormatter(),
            'notification' => new NotificationMessageFormatter(),
            default        => new RegularMessageFormatter(),
        };
    }
}
