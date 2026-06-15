<?php

namespace App\Features\HireTrainer\Services\Trainer;

class ApplicationValidatorFactory
{
    public static function create(string $mode = 'standard'): ApplicationValidator
    {
        return match ($mode) {
            'strict'  => new StrictApplicationValidator(),
            default   => new StandardApplicationValidator(),
        };
    }
}
