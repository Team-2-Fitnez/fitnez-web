<?php

namespace App\Features\Chat\Services\Chat;

class RegularMessageFormatter implements MessageFormatter
{
    public function format(string $message, array $context = []): string
    {
        return trim($message);
    }
}
