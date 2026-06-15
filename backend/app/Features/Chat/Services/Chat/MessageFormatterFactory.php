<?php

namespace App\Features\Chat\Services\Chat;

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
