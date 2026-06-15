<?php

namespace App\Services\Booking;

class OnlinePriceCalculator implements PriceCalculator
{
    public function calculate(float $basePrice, float $durationHours, ?string $location = null): float
    {
        return $basePrice * $durationHours * 0.9;
    }
}
