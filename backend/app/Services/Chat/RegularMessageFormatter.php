<?php

namespace App\Services\Chat;

class RegularMessageFormatter implements MessageFormatter
{
    public function format(string $message, array $context = []): string
    {
        return trim($message);
    }
}
