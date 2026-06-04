<?php

namespace App\Services\Booking;

interface PriceCalculator
{
    public function calculate(float $basePrice, float $durationHours, ?string $location = null): float;
}
