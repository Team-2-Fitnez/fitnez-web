<?php

namespace App\Services\Chat;

interface MessageFormatter
{
    public function format(string $message, array $context = []): string;
}

