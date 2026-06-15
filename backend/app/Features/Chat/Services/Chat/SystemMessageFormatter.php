<?php

namespace App\Features\Chat\Services\Chat;

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
