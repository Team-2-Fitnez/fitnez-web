<?php

namespace App\Services\Booking;

class OfflinePriceCalculator implements PriceCalculator
{
    public function calculate(float $basePrice, float $durationHours, ?string $location = null): float
    {
        return $basePrice * $durationHours * 1.0;
    }
}
