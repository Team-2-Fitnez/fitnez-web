<?php

namespace App\Features\Chat\Services\Chat;

interface MessageFormatter
{
    public function format(string $message, array $context = []): string;
}

