<?php

namespace App\Features\HireTrainer\Services\Booking;

class PromoPriceCalculator implements PriceCalculator
{
    public function calculate(float $basePrice, float $durationHours, ?string $location = null): float
    {
        return $basePrice * $durationHours * 0.8;
    }
}
